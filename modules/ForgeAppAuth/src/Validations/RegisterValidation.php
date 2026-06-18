<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class RegisterValidation
{
    use ValidatorHelper;

    public static function validate(array $data): void
    {
        static::validateData(
            new ValidationDefinition(
                data: $data,
                rules: [
                    'identifier' => ['required', 'min:3', 'max:90', 'unique:users,identifier'],
                    'email' => ['required', 'email', 'unique:users,email'],
                    'password' => ['required'],
                ],
                messages: [
                    'required' => 'The :field field is required!',
                    'min' => 'The :field field must be at least :value characters.',
                    'max' => 'The :field field must not exceed :value characters.',
                    'unique' => 'The :field is already taken.',
                    'email' => 'The :field must be a valid email address.',
                ],
            )
        );
    }
}
