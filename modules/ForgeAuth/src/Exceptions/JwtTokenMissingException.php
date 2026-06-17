<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class JwtTokenMissingException extends BaseException
{
    public function __construct(string $message = "Unauthorized: Missing token")
    {
        parent::__construct($message);
    }
}

