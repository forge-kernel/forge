<?php

declare(strict_types=1);

namespace Modules\AppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class RegisterValidation
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
                    "identifier" => ["required", "min:3", "max:90", "unique:users,identifier"],
                    //"password" => ["required", "min:6", "max:128", "same:confirm_password"]
                ],
                messages: [
                    "required" => "The :field field is required!",
                    "min" => "The :field field must be at least :value characters.",
                    "unique" => "The :field is already taken."
                ]
            )
        );
    }
}
