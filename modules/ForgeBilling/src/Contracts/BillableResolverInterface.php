<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Contracts;

interface BillableResolverInterface
{
    public function resolve(): ?string;
}
