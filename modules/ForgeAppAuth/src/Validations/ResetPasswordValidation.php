<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class ResetPasswordValidation
{
    use ValidatorHelper;

    public static function validate(array $data): void
    {
        static::validateData(
            new ValidationDefinition(
                data: $data,
                rules: [
                    'token' => ['required'],
                    'password' => ['required'],
                ],
                messages: [
                    'required' => 'The :field field is required!',
                ],
            )
        );
    }
}
