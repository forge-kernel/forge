<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Models;

use DateTimeImmutable;

final class LogEntry
{
    public readonly DateTimeImmutable $date;
    public readonly string $level;
    public readonly string $message;
    public readonly array $context;
    public readonly ?string $requestId;
    public readonly ?string $fingerprint;
    public readonly ?string $file;
    public readonly ?int $line;
    public readonly array $trace;
    public readonly ?string $module;
    public readonly ?string $exception;

    public function __construct(
        DateTimeImmutable $date,
        string $level,
        string $message,
        array $context = [],
        ?string $requestId = null,
        ?string $fingerprint = null,
        ?string $file = null,
        ?int $line = null,
        array $trace = [],
        ?string $module = null,
        ?string $exception = null,
    ) {
        $this->date = $date;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->requestId = $requestId;
        $this->fingerprint = $fingerprint;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
        $this->module = $module;
        $this->exception = $exception;
    }

    public static function fromString(string $logLine): self
    {
        $logLine = trim($logLine);

        if ($logLine === '') {
            throw new \InvalidArgumentException('Empty log line');
        }

        return self::tryErrorHandlerFormat($logLine)
            ?? self::tryStandardFormat($logLine)
            ?? self::trySimpleFormat($logLine)
            ?? new self(
                date: new DateTimeImmutable('now'),
                level: 'INFO',
                message: $logLine,
            );
    }

    private static function tryErrorHandlerFormat(string $logLine): ?self
    {
        $pattern = '/^\[(?<date>[^\]]+)\]\s+(?<requestId>\S+)\s+\[(?<fingerprint>[^\]]+)\]\s+(?<exception>\S+)\s+\x{2013}\s+(?<file>[^\s:]+):(?<line>\d+)\s*\|\s*(?<trace>.+?)\s*\|\s*(?<message>.+)$/u';

        if (!preg_match($pattern, $logLine, $matches)) {
            return null;
        }

        $filePath = $matches['file'];
        $traceFrames = self::parseStackTrace($matches['trace']);

        return new self(
            date: self::parseDate($matches['date']),
            level: 'ERROR',
            message: $matches['message'],
            context: ['exception' => $matches['exception']],
            requestId: $matches['requestId'],
            fingerprint: $matches['fingerprint'],
            file: $filePath,
            line: (int) $matches['line'],
            trace: $traceFrames,
            module: self::extractModule($filePath),
            exception: $matches['exception'],
        );
    }

    private static function tryStandardFormat(string $logLine): ?self
    {
        $pattern = '/^\[(?<date>[^\]]+)\]\s+\[(?<level>[^\]]+)\]\s+(?<message>.*?)(?:\s+(?<context>\{.*\}))?$/';

        if (!preg_match($pattern, $logLine, $matches)) {
            return null;
        }

        $level = strtoupper(trim($matches['level']));
        $message = trim($matches['message']);
        $context = self::parseContext($matches['context'] ?? null);

        $file = $context['exception']['file'] ?? $context['file'] ?? null;
        $line = isset($context['exception']['line']) ? (int) $context['exception']['line'] : ($context['line'] ?? null);
        $traceStr = $context['exception']['trace'] ?? $context['trace'] ?? null;
        $traceFrames = $traceStr ? self::parseStackTrace($traceStr) : [];

        return new self(
            date: self::parseDate($matches['date']),
            level: $level,
            message: $message,
            context: $context,
            file: $file,
            line: $line,
            trace: $traceFrames,
            module: $file ? self::extractModule($file) : null,
            exception: is_string($context['exception'] ?? null) ? $context['exception'] : null,
        );
    }

    private static function trySimpleFormat(string $logLine): ?self
    {
        $pattern = '/^\[(?<date>[^\]]+)\]\s+(?<message>.+)$/';

        if (!preg_match($pattern, $logLine, $matches)) {
            return null;
        }

        $rawMessage = trim($matches['message']);
        $parts = explode(' | ', $rawMessage, 2);
        $message = $parts[0];
        $contextStr = $parts[1] ?? null;

        $level = 'INFO';
        if (preg_match('/\b(ERROR|WARNING|WARN|INFO|DEBUG|CRITICAL)\b/i', $message, $levelMatch)) {
            $level = strtoupper($levelMatch[1]) === 'WARN' ? 'WARNING' : strtoupper($levelMatch[1]);
        }

        $context = [];
        if ($contextStr) {
            $decoded = json_decode($contextStr, true);
            $context = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? $decoded
                : ['raw' => $contextStr];
        }

        return new self(
            date: self::parseDate($matches['date']),
            level: $level,
            message: $message,
            context: $context,
        );
    }

    private static function parseDate(string $dateStr): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($dateStr);
        } catch (\Exception) {
            return new DateTimeImmutable('now');
        }
    }

    private static function parseContext(?string $contextString): array
    {
        if (!$contextString) {
            return [];
        }

        $decoded = json_decode($contextString, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return ['raw' => $contextString];
    }

    private static function parseStackTrace(string $traceStr): array
    {
        $frames = [];
        $parts = preg_split('/\s+(?=#)/', $traceStr);

        foreach ($parts as $frame) {
            $frame = trim($frame);
            if ($frame === '') {
                continue;
            }

            $frameData = ['raw' => $frame];

            if (preg_match('/^#(\d+)\s+(.+)$/', $frame, $matches)) {
                $frameData['index'] = (int) $matches[1];
                $call = $matches[2];

                if (preg_match('/\(([^:]+):(\d+)\)/', $call, $fileMatch)) {
                    $frameData['file'] = $fileMatch[1];
                    $frameData['line'] = (int) $fileMatch[2];
                    $call = preg_replace('/\([^)]+\)/', '()', $call, 1);
                }

                $frameData['call'] = $call;
            }

            $frames[] = $frameData;
        }

        return $frames;
    }

    private static function extractModule(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        if (preg_match('#modules[/\\\\]([^/\\\\]+)#i', $filePath, $matches)) {
            return $matches[1];
        }

        if (preg_match('#^(Forge|App|Kernel)[/\\\\]#i', $filePath, $matches)) {
            return ucfirst(strtolower($matches[1]));
        }

        return null;
    }
}
