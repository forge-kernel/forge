<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Contracts;

enum LogLevel: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';

    public function priority(): int
    {
        return match ($this) {
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
        };
    }

    public static function fromString(string $level): self
    {
        return self::tryFrom(strtoupper($level)) ?? self::DEBUG;
    }
}
