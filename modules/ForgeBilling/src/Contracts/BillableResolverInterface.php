<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Contracts;

interface BillableResolverInterface
{
    public function resolve(): ?string;
}
