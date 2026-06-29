<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Services;

use Modules\ForgeBilling\Dto\ChargeRequest;
use Modules\ForgeBilling\Dto\ChargeResult;
use Modules\ForgeBilling\Dto\RefundResult;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;
use Forge\Core\DI\Attributes\Service;

final class PaymentService
{
    public function __construct(
        private readonly PaymentProviderRegistry $providerRegistry,
        private readonly QueryBuilderInterface $centralQueryBuilder,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function charge(
        string $tenantId,
        string $invoiceId,
        float $amount,
        string $currency,
        string $providerName = 'manual',
        array $options = [],
    ): ChargeResult {
        $provider = $this->providerRegistry->get($providerName);

        $request = new ChargeRequest(
            tenantId: $tenantId,
            amount: $amount,
            currency: $currency,
            description: "Invoice payment {$invoiceId}",
            metadata: array_merge(['invoice_id' => $invoiceId], $options),
        );

        $result = $provider->charge($request);

        $this->centralQueryBuilder->setTable('transactions')->insert([
            'id' => UUID::generate(),
            'invoice_id' => $invoiceId,
            'tenant_id' => $tenantId,
            'provider_transaction_id' => $result->transactionId,
            'amount' => $result->amountCharged,
            'currency' => $result->currency,
            'status' => $result->success ? 'completed' : 'failed',
            'provider_response' => json_encode($result->providerResponse),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result->success) {
            $this->invoiceService->markAsPaid($invoiceId);
        }

        return $result;
    }

    public function refund(string $transactionId, ?int $amount = null): RefundResult
    {
        $tx = $this->centralQueryBuilder->setTable('transactions')
            ->where('id', '=', $transactionId)
            ->first();

        if (!$tx) {
            return new RefundResult(
                success: false,
                refundId: '',
                amountRefunded: 0,
                currency: 'USD',
                errorMessage: 'Transaction not found',
            );
        }

        $provider = $this->providerRegistry->get($tx['provider_transaction_id'] ?? 'manual');
        return $provider->refund($tx['provider_transaction_id'], $amount);
    }
}
