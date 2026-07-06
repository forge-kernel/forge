<?php

declare(strict_types=1);

namespace Modules\AppAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class UserNotFoundException extends BaseException
{
    public function __construct()
    {
        parent::__construct("The user was not found");
    }
}
