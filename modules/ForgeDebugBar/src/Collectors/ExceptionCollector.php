<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class ExceptionCollector implements CollectorInterface
{
    private array $exceptions = [];

    public static function collect(...$args): array
    {
        return self::instance()->exceptions;
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function addException(\Throwable $exception): void
    {
        $this->exceptions[] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => BASE_PATH . $exception->getFile() . ':' . $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }
}
