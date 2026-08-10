<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Services;

use Modules\ForgeEvents\Attributes\Event;
use Modules\ForgeEvents\Attributes\EventListener;
use Modules\ForgeEvents\Contracts\Queueinterface;
use Modules\ForgeEvents\Enums\QueuePriority;
use Modules\ForgeEvents\Exceptions\EventException;
use Modules\ForgeEvents\Queues\DatabaseQueue;
use Modules\ForgeEvents\Queues\FileQueue;
use Modules\ForgeEvents\Queues\InMemoryQueue;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Core\Config\Config;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Contracts\EventDispatcherInterface;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use Forge\Traits\TimeTrait;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;

#[Injectable(singleton: true)]
#[Provides(EventDispatcher::class, version: '0.2.1')]
#[Provides(EventDispatcherInterface::class, version: '0.2.1')]
#[NoCache(reason: 'Contains unserializable database connections')]
final class EventDispatcher implements EventDispatcherInterface
{
    use OutputHelper;
    use TimeTrait;

    private array $listeners = [];
    private array $resolvedListeners = [];
    private Queueinterface $queue;
    private Container $container;
    private QueryBuilderInterface $queryBuilder;
    private ?int $currentJobId = null;

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function __construct(private Config $config)
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilderInterface::class);
        $this->container = Container::getInstance();
        $this->queue = $this->driverSetup();
    }

    private function driverSetup(): Queueinterface
    {
        $driver = $this->config->get('forge_events.queue_driver', 'database');
        $adapter = match ($driver) {
            'file' => new FileQueue("forge_events"),
            'in-memory' => new InMemoryQueue(),
            'database' => new DatabaseQueue($this->queryBuilder),
            default => throw new RuntimeException('Unsupported driver')
        };

        return $adapter;
    }

    public function addListener(string $eventClass, array|callable $handler): void
    {
        $this->listeners[$eventClass][] = $handler;
    }

    /**
     * @throws EventException
     */
    #[EventListener(Event::class)]
    public function dispatch(object $event): void
    {
        $eventMetadata = $this->eventMetadata($event);
        $delayMilliseconds = $this->toMilliseconds($eventMetadata->delay) ?? 0;

        $this->pushEvent($event, $eventMetadata, $delayMilliseconds);
    }

    /**
     * Dispatch an event with an explicit delay (in milliseconds) instead of the
     * one declared on the #[Event] attribute. Used by self-rescheduling jobs
     * (e.g. the maintenance/cron runner) to schedule the next run.
     */
    public function dispatchDelayed(object $event, int $delayMilliseconds): void
    {
        $eventMetadata = $this->eventMetadata($event);

        $this->pushEvent($event, $eventMetadata, max(0, $delayMilliseconds));
    }

    /**
     * The id of the queue job currently being handled, or null when not
     * processing a job. Lets listeners persist diagnostics (e.g. maintenance
     * errors) onto the live job row.
     */
    public function currentJobId(): ?int
    {
        return $this->currentJobId;
    }

    /**
     * Merge metadata onto a queue job's row. The metadata column is JSON.
     */
    public function writeJobMetadata(int $jobId, array $metadata): void
    {
        $current = $this->queryBuilder
            ->reset()
            ->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->first();

        if ($current === null) {
            return;
        }

        $existing = $current['metadata'] ?? null;
        if (is_string($existing)) {
            $existing = json_decode($existing, true);
        }
        if (!is_array($existing)) {
            $existing = [];
        }

        $merged = array_replace_recursive($existing, $metadata);

        $this->queryBuilder
            ->reset()
            ->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->update(['metadata' => json_encode($merged, JSON_UNESCAPED_SLASHES)]);
    }

    /**
     * Mark a job as failed while keeping its row (with any metadata written on
     * it) as a diagnostic trail. Unlike the retry path, the row is not deleted.
     */
    public function failJob(int $jobId): void
    {
        $this->markJobAsFailed($jobId);
    }

    private function eventMetadata(object $event): object
    {
        $eventReflection = new ReflectionClass($event);
        $eventAttribute = $eventReflection->getAttributes(Event::class)[0] ?? null;

        if (!$eventAttribute) {
            throw new EventException("Event missing #[Event] attribute");
        }

        return $eventAttribute->newInstance();
    }

    private function pushEvent(object $event, object $eventMetadata, int $delayMilliseconds): void
    {
        $eventReflection = new ReflectionClass($event);

        $this->queue->push($this->serializeEvent($event, $eventReflection, $eventMetadata), $eventMetadata->priority->value, $delayMilliseconds, $eventMetadata->maxRetries, $eventMetadata->queue);
    }

    public function getNextJobDelay(string $queue = 'default'): ?float
    {
        return $this->queue->getNextJobDelay($queue);
    }

    /**
     * Safely serialize an event for queue storage
     */
    private function serializeEvent(object $event, ReflectionClass $eventReflection, object $eventMetadata): string
    {
        try {
            // First try direct serialization
            return serialize([
                'event' => $event,
                'class' => $eventReflection->getName(),
                'metadata' => $eventMetadata,
                'attempts' => 0
            ]);
        } catch (\Throwable $e) {
            // If direct serialization fails, try to extract serializable data
            return serialize([
                'event' => $this->extractSerializableData($event),
                'class' => $eventReflection->getName(),
                'metadata' => $eventMetadata,
                'attempts' => 0
            ]);
        }
    }

    /**
     * Extract serializable data from an event object
     */
    private function extractSerializableData(object $event): array
    {
        $data = [];
        $reflection = new ReflectionClass($event);

        // Extract public properties
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();

            if ($property->isInitialized($event)) {
                $value = $property->getValue($event);

                // Skip unserializable resources
                if ($this->isSerializable($value)) {
                    $data[$propertyName] = $value;
                } else {
                    // For unserializable values, try to get a string representation
                    $data[$propertyName] = $this->safeStringify($value);
                }
            }
        }

        // Try using toArray() method if available
        if (method_exists($event, 'toArray')) {
            $toArray = $event->toArray();
            if (is_array($toArray)) {
                $data = array_merge($data, $toArray);
            }
        }

        return $data;
    }

    /**
     * Check if a value is safely serializable
     */
    private function isSerializable(mixed $value): bool
    {
        // Skip resources and unserializable objects
        if (is_resource($value)) {
            return false;
        }

        if (is_object($value)) {
            // Skip common unserializable types
            if ($value instanceof \PDO) {
                return false;
            }

            if ($value instanceof \Closure) {
                return false;
            }

            // Try to serialize to see if it works
            try {
                @serialize($value);
                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert unserializable values to safe string representations
     */
    private function safeStringify(mixed $value): string
    {
        if (is_object($value)) {
            return '[Object: ' . get_class($value) . ']';
        }

        if (is_resource($value)) {
            return '[Resource: ' . get_resource_type($value) . ']';
        }

        if (is_array($value)) {
            return '[Array: ' . count($value) . ' items]';
        }

        return '[Unknown]';
    }

    public function processNextEvent(string $queue = 'default'): string
    {
        $job = $this->queue->pop($queue);
        if (!$job) {
            return '';
        }

        $payload = unserialize($job['payload']);

        $jobId = $job['id'] ?? null;
        $this->handleEvent($payload, $jobId);
        return (string) $jobId;
    }

    private function handleEvent(array $payload, ?int $jobId): void
    {
        $now = date('Y-m-d H:i:s');
        $eventClass = $payload['class'];
        $payload['jobId'] = $jobId;
        $this->currentJobId = $jobId;
        $this->comment("Handling event: {$eventClass}");

        try {
            if (!isset($this->listeners[$eventClass])) {
                $this->warning("No listeners for event: {$eventClass}");
                if ($jobId !== null) {
                    $this->deleteJob($jobId);
                }
                return;
            }

            $this->info("Processing event: {$eventClass} at: {$now}");

            // Reconstruct the event object
            $event = $this->reconstructEvent($payload['event'], $eventClass);

            foreach ($this->listeners[$eventClass] as $handler) {
                try {
                    if (is_array($handler) && is_string($handler[0])) {
                        $listener = $this->resolvedListeners[$handler[0]] ??= $this->container->make($handler[0]);
                        call_user_func([$listener, $handler[1]], $event);
                    } else {
                        call_user_func($handler, $event);
                    }
                    if ($jobId !== null && !$this->isFailed($jobId)) {
                        $this->deleteJob($jobId);
                    }
                } catch (Throwable $e) {
                    $this->handleFailure($payload, $e, $jobId);
                }
            }
        } finally {
            $this->currentJobId = null;
        }
    }

    /**
     * Whether the job row has been explicitly failed (failed_at set) by its
     * handler — such rows are kept as a diagnostic trail, not deleted.
     */
    private function isFailed(int $jobId): bool
    {
        $row = $this->queryBuilder
            ->reset()
            ->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->first();

        return $row !== null && ($row['failed_at'] ?? null) !== null;
    }

    /**
     * Reconstruct an event from serialized data
     */
    private function reconstructEvent(mixed $eventData, string $eventClass): object
    {
        // If it's already an event object, return it
        if (is_object($eventData)) {
            return $eventData;
        }

        // If it's an array, reconstruct the event
        if (is_array($eventData)) {
            try {
                // Try to create new instance with the array data
                $reflection = new ReflectionClass($eventClass);

                if ($reflection->isReadOnly()) {
                    // For readonly classes, we need to use reflection
                    return $reflection->newInstanceWithoutConstructor();
                } else {
                    // For regular classes, try constructor
                    $constructor = $reflection->getConstructor();
                    if ($constructor) {
                        // Map constructor parameters from array data
                        $params = [];
                        foreach ($constructor->getParameters() as $param) {
                            $paramName = $param->getName();
                            if (isset($eventData[$paramName])) {
                                $params[] = $eventData[$paramName];
                            } else {
                                $params[] = $param->getDefaultValue();
                            }
                        }
                        return $reflection->newInstanceArgs($params);
                    } else {
                        return $reflection->newInstanceWithoutConstructor();
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Failed to reconstruct event {$eventClass}: " . $e->getMessage());
                // Return a minimal fallback
                return new class ($eventData) {
                    public function __construct(public array $data)
                    {}
                };
            }
        }

        throw new \RuntimeException("Cannot reconstruct event from data of type: " . gettype($eventData));
    }

    private function deleteJob(?int $jobId): void
    {
        if ($jobId !== null) {
            $this->queryBuilder->reset()->setTable('queue_jobs')->where('id', '=', $jobId)->delete();
        }
    }

    private function handleFailure(array $payload, Throwable $e, ?int $jobId): void
    {
        $this->error("Metadata: " . print_r($payload['metadata'], true));
        $retries = $payload['metadata']->maxRetries ?? 3;
        $attempts = $payload['attempts'] ?? 0;

        $this->error("Event {$payload['class']} failed. Attempt: " . ($attempts + 1));

        if ($jobId !== null) {
            $this->writeJobMetadata($jobId, [
                'error' => sprintf(
                    '[%s] %s: %s',
                    date('Y-m-d H:i:s'),
                    get_class($e),
                    $e->getMessage(),
                ),
            ]);
        }

        if ($attempts < $retries) {
            $this->retryEvent($payload, $attempts);
            if ($jobId !== null) {
                $this->deleteJob($jobId);
            }
        } else {
            if ($jobId !== null) {
                // Keep the failed job row (with its metadata.error) as a
                // diagnostic trail instead of silently deleting it.
                $this->markJobAsFailed($jobId);
            }
        }
    }

    private function retryEvent(array $payload, int $attempts): void
    {
        $payload['attempts'] = $attempts + 1;

        $retryDelaySeconds = ($payload['metadata']->retryDelay ?? 0) / 100;
        $retryProcessAfter = microtime(true) + $retryDelaySeconds;

        $this->queue->push(serialize([
            'event' => $payload['event'],
            'class' => $payload['class'],
            'metadata' => $payload['metadata'],
            'processAfter' => $retryProcessAfter,
            'attempts' => $payload['attempts'],
            $payload['metadata']->queue
        ]), QueuePriority::LOW->value, (int) ($retryDelaySeconds * 1000));

        $this->warning("Retrying event {$payload['class']} (attempt {$payload['attempts']})");
    }

    private function markJobAsFailed(?int $jobId): void
    {
        if ($jobId !== null) {
            $this->queryBuilder->reset()->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->update([
                    'failed_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function release(int $jobId, ?int $delay = 0): void
    {
        $this->queue->release($jobId, $delay);
    }
}
