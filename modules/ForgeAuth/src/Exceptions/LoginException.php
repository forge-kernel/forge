<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class LoginException extends BaseException
{
    public function __construct(string $message = "Invalid credentials")
    {
        parent::__construct($message);
    }
}
