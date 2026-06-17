<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Exceptions;

use Forge\Exceptions\BaseException;

final class EventException extends BaseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
