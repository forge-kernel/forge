<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Validations;

use Forge\Traits\ValidatorHelper;
use Forge\Core\Validation\ValidationDefinition;

final class LoginValidation
{
    use ValidatorHelper;

    public static function validate(array $data): void
    {
        static::validateData(
            new ValidationDefinition(
                data: $data,
                rules: [
                    'identifier' => ['required'],
                    'password' => ['required'],
                ],
            )
        );
    }
}
