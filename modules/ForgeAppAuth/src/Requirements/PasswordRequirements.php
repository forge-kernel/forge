<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Requirements;

use Forge\Exceptions\ValidationException;

final class PasswordRequirements
{
    public static function validate(string $password): void
    {
        self::minimumLength($password);
        self::maximumLength($password);
        self::requiresUppercase($password);
        self::requiresNumber($password);
        self::requiresSymbol($password);
    }

    private static function minimumLength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new ValidationException([
                'password' => ['Password must be at least 8 characters.'],
            ]);
        }
    }

    private static function maximumLength(string $password): void
    {
        if (strlen($password) > 128) {
            throw new ValidationException([
                'password' => ['Password cannot exceed 128 characters.'],
            ]);
        }
    }

    private static function requiresUppercase(string $password): void
    {
        if (!preg_match('/[A-Z]/', $password)) {
            throw new ValidationException([
                'password' => ['Password must contain at least one uppercase letter.'],
            ]);
        }
    }

    private static function requiresNumber(string $password): void
    {
        if (!preg_match('/[0-9]/', $password)) {
            throw new ValidationException([
                'password' => ['Password must contain at least one number.'],
            ]);
        }
    }

    private static function requiresSymbol(string $password): void
    {
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new ValidationException([
                'password' => ['Password must contain at least one special character.'],
            ]);
        }
    }
}
