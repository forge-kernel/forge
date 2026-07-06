<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Exceptions;

use Forge\Exceptions\BaseException;

final class AuthorizationException extends BaseException
{
    public function __construct(string $message = 'Insufficient permissions')
    {
        parent::__construct($message);
    }
}
