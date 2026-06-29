<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class JwtTokenInvalidException extends BaseException
{
    public function __construct(string $message = "Unauthorized: Invalid token")
    {
        parent::__construct($message);
    }
}

