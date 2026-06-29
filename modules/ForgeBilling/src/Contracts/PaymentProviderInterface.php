<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Contracts;

use Modules\ForgeBilling\Dto\ChargeRequest;
use Modules\ForgeBilling\Dto\ChargeResult;
use Modules\ForgeBilling\Dto\RefundResult;

interface PaymentProviderInterface
{
    public function charge(ChargeRequest $request): ChargeResult;

    public function refund(string $transactionId, ?int $amount = null): RefundResult;

    public function tokenize(array $cardDetails): string;

    public function name(): string;
}
