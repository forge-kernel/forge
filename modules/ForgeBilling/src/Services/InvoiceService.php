<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Services;

use Modules\ForgeBilling\Dto\Invoice;
use Modules\ForgeBilling\Dto\InvoiceItem;
use Modules\ForgeBilling\Enums\InvoiceStatus;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;
use Forge\Core\DI\Attributes\Service;

final class InvoiceService
{
    public function __construct(
        private readonly QueryBuilderInterface $centralQueryBuilder,
    ) {
    }

    public function getForTenant(string $tenantId): array
    {
        $rows = $this->centralQueryBuilder->setTable('invoices')
            ->where('tenant_id', '=', $tenantId)
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn(array $row) => $this->toDto($row), $rows);
    }

    public function getById(string $id): ?Invoice
    {
        $row = $this->centralQueryBuilder->setTable('invoices')
            ->where('id', '=', $id)
            ->first();
        return $row ? $this->toDto($row) : null;
    }

    public function latestForTenant(string $tenantId): ?Invoice
    {
        $row = $this->centralQueryBuilder->setTable('invoices')
            ->where('tenant_id', '=', $tenantId)
            ->orderBy('created_at', 'DESC')
            ->first();
        return $row ? $this->toDto($row) : null;
    }

    public function create(
        string $tenantId,
        string $subscriptionId,
        float $amount,
        string $currency,
        array $items = [],
        ?\DateTimeImmutable $dueDate = null,
    ): Invoice {
        $id = UUID::generate();
        $number = 'INV-' . strtoupper(substr($id, 0, 8));

        $data = [
            'id' => $id,
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'number' => $number,
            'amount' => $amount,
            'currency' => $currency,
            'status' => InvoiceStatus::PENDING->value,
            'paid_at' => null,
            'due_date' => $dueDate?->format('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->centralQueryBuilder->setTable('invoices')->insert($data);

        foreach ($items as $item) {
            $this->centralQueryBuilder->setTable('invoice_items')->insert([
                'id' => UUID::generate(),
                'invoice_id' => $id,
                'description' => $item['description'] ?? '',
                'amount' => $item['amount'] ?? 0,
                'currency' => $item['currency'] ?? $currency,
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }

        return $this->toDto($data);
    }

    public function markAsPaid(string $id): bool
    {
        return (bool) $this->centralQueryBuilder->setTable('invoices')
            ->where('id', '=', $id)
            ->update([
                'status' => InvoiceStatus::PAID->value,
                'paid_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getItems(string $invoiceId): array
    {
        $rows = $this->centralQueryBuilder->setTable('invoice_items')
            ->where('invoice_id', '=', $invoiceId)
            ->get();
        return array_map(fn(array $row) => new InvoiceItem(
            id: $row['id'],
            invoiceId: $row['invoice_id'],
            description: $row['description'],
            amount: (float) $row['amount'],
            currency: $row['currency'],
            quantity: (int) ($row['quantity'] ?? 1),
        ), $rows);
    }

    private function toDto(array $row): Invoice
    {
        return new Invoice(
            id: $row['id'],
            tenantId: $row['tenant_id'],
            subscriptionId: $row['subscription_id'],
            number: $row['number'],
            amount: (float) $row['amount'],
            currency: $row['currency'],
            status: InvoiceStatus::from($row['status']),
            paidAt: $row['paid_at'] ? new \DateTimeImmutable($row['paid_at']) : null,
            dueDate: $row['due_date'] ? new \DateTimeImmutable($row['due_date']) : null,
            createdAt: isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
        );
    }
}
