<?php
declare(strict_types=1);

namespace App\Modules\AppAuth\Requeriments;

use Forge\Exceptions\ValidationException;

final class PasswordRequeriments
{
    public static function validate(string $password): void
    {
        static::minimumLength($password);
        static::maximumLength($password);
        static::requiresUppercase($password);
        static::requiresNumber($password);
        static::requiresSymbol($password);
    }

    private static function minimumLength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new ValidationException([
                'password' => [
                    'Password must be at least 8 characters.'
                ]
            ]);
        }

    }
    private static function maximumLength(string $password): void
    {
        if (strlen($password) > 128) {
            throw new ValidationException([
                'password' => [
                    'Password cannot exceed 128 characters.'
                ]
            ]);
        }
    }

    private static function requiresUppercase(string $password): void
    {
        if (!preg_match('/[A-Z]/', $password)) {
            throw new ValidationException([
                'password' => [
                    'Password must contain at least one uppercase letter.'
                ]
            ]);
        }
    }

    private static function requiresNumber(string $password): void
    {
        if (!preg_match('/[0-9]/', $password)) {
            throw new ValidationException([
                'password' => [
                    'Password must contain at least one number.'
                ]
            ]);
        }
    }

    private static function requiresSymbol(string $password): void
    {
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new ValidationException([
                'password' => [
                    'Password must contain at least one special character.'
                ]
            ]);
        }
    }
}
