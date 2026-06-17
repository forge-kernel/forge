<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Exceptions;

use Forge\Exceptions\BaseException;

final class UserRegistrationException extends BaseException
{
    public function __construct()
    {
        parent::__construct("There was an error creating your user.");
    }
}
