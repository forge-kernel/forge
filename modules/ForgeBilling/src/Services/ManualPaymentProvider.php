<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Services;

use App\Modules\ForgeBilling\Contracts\PaymentProviderInterface;
use App\Modules\ForgeBilling\Dto\ChargeRequest;
use App\Modules\ForgeBilling\Dto\ChargeResult;
use App\Modules\ForgeBilling\Dto\RefundResult;

final class ManualPaymentProvider implements PaymentProviderInterface
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        return new ChargeResult(
            success: true,
            transactionId: 'manual_' . bin2hex(random_bytes(16)),
            amountCharged: $request->amount,
            currency: $request->currency,
            providerResponse: ['provider' => 'manual', 'status' => 'approved'],
        );
    }

    public function refund(string $transactionId, ?int $amount = null): RefundResult
    {
        return new RefundResult(
            success: true,
            refundId: 'refund_' . bin2hex(random_bytes(16)),
            amountRefunded: (float) ($amount ?? 0),
            currency: 'USD',
            providerResponse: ['provider' => 'manual', 'status' => 'refunded'],
        );
    }

    public function tokenize(array $cardDetails): string
    {
        return 'manual_tok_' . bin2hex(random_bytes(16));
    }

    public function name(): string
    {
        return 'manual';
    }
}
