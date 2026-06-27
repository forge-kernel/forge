<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class MessageCollector implements CollectorInterface
{
  private array $messages = [];
  private float $startTime;

  public function __construct()
  {
    $this->startTime = microtime(true);
  }

  public static function collect(...$args): array
  {
    return self::instance()->messages;
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
    $now = microtime(true);
    $this->messages[] = [
      'message' => $message,
      'label' => $label,
      'time' => $now,
      'relative_time' => round(($now - $this->startTime) * 1000, 2),
    ];
  }

  public function setStartTime(float $startTime): void
  {
    $this->startTime = $startTime;
    foreach ($this->messages as &$message) {
      if (isset($message['time'])) {
        $message['relative_time'] = round(($message['time'] - $startTime) * 1000, 2);
      }
    }
    unset($message);
  }
}
