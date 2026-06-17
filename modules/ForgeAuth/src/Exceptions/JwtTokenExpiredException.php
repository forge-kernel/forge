<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class JwtTokenExpiredException extends BaseException
{
    public function __construct(string $message = "Unauthorized: Token expired")
    {
        parent::__construct($message);
    }
}

