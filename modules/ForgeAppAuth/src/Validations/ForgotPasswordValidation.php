<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class ForgotPasswordValidation
{
    use ValidatorHelper;

    public static function validate(array $data): void
    {
        static::validateData(
            new ValidationDefinition(
                data: $data,
                rules: [
                    'email' => ['required', 'email'],
                ],
                messages: [
                    'required' => 'The :field field is required!',
                    'email' => 'Please provide a valid email address.',
                ],
            )
        );
    }
}
