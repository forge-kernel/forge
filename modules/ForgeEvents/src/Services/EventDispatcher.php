<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Services;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Attributes\EventListener;
use App\Modules\ForgeEvents\Contracts\Queueinterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Queues\DatabaseQueue;
use App\Modules\ForgeEvents\Queues\FileQueue;
use App\Modules\ForgeEvents\Queues\InMemoryQueue;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Core\Config\Environment;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use Forge\Traits\TimeTrait;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;

#[Service(singleton: true)]
#[Provides(EventDispatcher::class, version: '0.2.1')]
#[NoCache(reason: 'Contains unserializable database connections')]
final class EventDispatcher
{
    use OutputHelper;
    use TimeTrait;

    private array $listeners = [];
    private Queueinterface $queue;
    private Container $container;
    private QueryBuilderInterface $queryBuilder;

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilderInterface::class);
        $this->container = Container::getInstance();
        $this->queue = $this->driverSetup();
    }

    private function driverSetup(): Queueinterface
    {
        $driver = Environment::getInstance()->get('QUEUE_DRIVER', 'file');
        $adapter = match ($driver) {
            'file' => new FileQueue("forge_events"),
            'in-memory' => new InMemoryQueue(),
            'database' => new DatabaseQueue($this->queryBuilder),
            default => throw new RuntimeException('Unsupported driver')
        };

        return $adapter;
    }

    public function addListener(string $eventClass, callable $handler): void
    {
        $this->listeners[$eventClass][] = $handler;
    }

    /**
     * @throws EventException
     */
    #[EventListener(Event::class)]
    public function dispatch(object $event): void
    {
        $eventReflection = new ReflectionClass($event);
        $eventAttribute = $eventReflection->getAttributes(Event::class)[0] ?? null;

        if (!$eventAttribute) {
            throw new EventException("Event missing #[Event] attribute");
        }

        $eventMetadata = $eventAttribute->newInstance();

        $delayMilliseconds = $this->toMilliseconds($eventMetadata->delay) ?? 0;

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
        $this->comment("Handling event: {$eventClass}");

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
                call_user_func($handler, $event);
                if ($jobId !== null) {
                    $this->deleteJob($jobId);
                }
            } catch (Throwable $e) {
                $this->handleFailure($payload, $e, $jobId);
            }
        }
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

        if ($attempts < $retries) {
            $this->retryEvent($payload, $attempts);
            if ($jobId !== null) {
                $this->deleteJob($jobId);
            }
        } else {
            if ($jobId !== null) {
                $this->markJobAsFailed($jobId);
                $this->deleteJob($jobId);
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
