<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Models;

use DateTimeImmutable;

final class LogEntry
{
    public readonly DateTimeImmutable $date;
    public readonly string $level;
    public readonly string $message;
    public readonly array $context;

    public function __construct(DateTimeImmutable $date, string $level, string $message, array $context)
    {
        $this->date = $date;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    public static function fromString(string $logLine): self
    {
        $logLine = trim($logLine);

        // Pattern for: [2024-01-01 12:00:00] [LEVEL] message
        // Or: [2024-01-01 12:00:00] [LEVEL] message {"context":"data"}
        $pattern = '/^\[(?<date>[^\]]+)\]\s+\[(?<level>[^\]]+)\]\s+(?<message>.*?)(?:\s+(?<context>\{.*\}))?$/';

        if (!preg_match($pattern, $logLine, $matches)) {
            // Fallback: try to parse as simple format or return as-is
            $dateTime = new DateTimeImmutable('now');
            $level = 'INFO';
            $message = $logLine;
            $context = [];

            // Try to extract date from beginning if present
            if (preg_match('/^\[([^\]]+)\]/', $logLine, $dateMatch)) {
                try {
                    $dateTime = new DateTimeImmutable($dateMatch[1]);
                } catch (\Exception $e) {
                    // Keep default
                }
            }

            // Try to extract level
            if (preg_match('/\[([A-Z]+)\]/', $logLine, $levelMatch)) {
                $level = $levelMatch[1];
            }

            return new self($dateTime, $level, $message, $context);
        }

        $dateString = $matches['date'] ?? null;
        $level = trim($matches['level'] ?? 'INFO');
        $message = trim($matches['message'] ?? $logLine);
        $contextString = $matches['context'] ?? null;

        try {
            $dateTime = $dateString ? new DateTimeImmutable($dateString) : new DateTimeImmutable('now');
            $context = [];

            if ($contextString) {
                $decoded = json_decode($contextString, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $context = $decoded;
                }
            }
        } catch (\Exception $e) {
            $dateTime = new DateTimeImmutable('now');
            $context = [];
            $message = "Error parsing log line: " . $logLine;
            $level = 'ERROR';
        }

        return new self(
            $dateTime,
            $level,
            $message,
            $context
        );
    }
}
