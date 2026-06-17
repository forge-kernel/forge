<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class LoginValidation
{
    use ValidatorHelper;

    /**
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): void
    {
        static::validateData(
            new ValidationDefinition(
                data: $data,
                rules: [
                    'identifier' => ["required"],
                    "password" => ["required"]
                ]
            )
        );
    }
}
