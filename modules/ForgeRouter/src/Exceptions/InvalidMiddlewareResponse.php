<?php
declare(strict_types=1);

namespace Modules\ForgeRouter\Exceptions;

use Forge\Exceptions\BaseException;

final class InvalidMiddlewareResponse extends BaseException
{
    public function __construct()
    {
        parent::__construct("Middleware did not return a Response object.");
    }
}
