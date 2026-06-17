<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class JwtRefreshTokenInvalidException extends BaseException
{
    public function __construct(string $message = "Unauthorized: Invalid refresh token")
    {
        parent::__construct($message);
    }
}

