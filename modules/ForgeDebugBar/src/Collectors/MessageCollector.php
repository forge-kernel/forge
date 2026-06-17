<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class MessageCollector implements CollectorInterface
{
  private array $messages = [];
  private ?float $startTime = null;

  public function __construct()
  {
    $this->startTime = microtime(true);
  }

  public static function collect(...$args): array
  {
    $instance = self::instance();
    $startTime = $args[0] ?? $instance->startTime ?? microtime(true);

    $messages = $instance->messages;
    foreach ($messages as &$message) {
      if (!isset($message['relative_time'])) {
        $message['relative_time'] = ($message['time'] - $startTime) * 1000;
      }
    }
    unset($message);

    return $messages;
  }

  public static function instance(): self
  {
    static $instance = null;
    if (null === $instance) {
      $instance = new self();
    }
    return $instance;
  }

  public function addMessage(mixed $message, string $label = 'info'): void
  {
    $currentTime = microtime(true);
    $this->messages[] = [
      'message' => $message,
      'label' => $label,
      'time' => $currentTime,
      'relative_time' => $this->startTime ? ($currentTime - $this->startTime) * 1000 : 0,
    ];
  }

  public function setStartTime(float $startTime): void
  {
    $this->startTime = $startTime;
    foreach ($this->messages as &$message) {
      $message['relative_time'] = ($message['time'] - $startTime) * 1000;
    }
    unset($message);
  }
}
