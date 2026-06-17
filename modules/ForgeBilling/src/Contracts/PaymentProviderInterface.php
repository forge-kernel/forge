<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Contracts;

use App\Modules\ForgeBilling\Dto\ChargeRequest;
use App\Modules\ForgeBilling\Dto\ChargeResult;
use App\Modules\ForgeBilling\Dto\RefundResult;

interface PaymentProviderInterface
{
    public function charge(ChargeRequest $request): ChargeResult;

    public function refund(string $transactionId, ?int $amount = null): RefundResult;

    public function tokenize(array $cardDetails): string;

    public function name(): string;
}
