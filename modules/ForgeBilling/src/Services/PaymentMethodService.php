<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Services;

use Modules\ForgeBilling\Dto\PaymentMethod;
use Modules\ForgeBilling\Enums\PaymentMethodType;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;

final class PaymentMethodService
{
    public function __construct(
        private readonly QueryBuilderInterface $centralQueryBuilder,
    ) {
    }

    public function getForTenant(string $tenantId): array
    {
        $rows = $this->centralQueryBuilder->setTable('payment_methods')
            ->where('tenant_id', '=', $tenantId)
            ->get();

        return array_map(fn(array $row) => $this->toDto($row), $rows);
    }

    public function create(string $tenantId, array $data): PaymentMethod
    {
        $id = UUID::generate();
        $row = [
            'id' => $id,
            'tenant_id' => $tenantId,
            'type' => $data['type'] ?? 'card',
            'provider_name' => $data['provider_name'] ?? 'manual',
            'token' => $data['token'] ?? '',
            'last_four' => $data['last_four'] ?? '',
            'is_default' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->centralQueryBuilder->setTable('payment_methods')->insert($row);
        return $this->toDto($row);
    }

    public function delete(string $id, string $tenantId): bool
    {
        return (bool) $this->centralQueryBuilder->setTable('payment_methods')
            ->where('id', '=', $id)
            ->where('tenant_id', '=', $tenantId)
            ->delete();
    }

    private function toDto(array $row): PaymentMethod
    {
        return new PaymentMethod(
            id: $row['id'],
            tenantId: $row['tenant_id'],
            type: PaymentMethodType::from($row['type']),
            providerName: $row['provider_name'],
            token: $row['token'],
            lastFour: $row['last_four'] ?? '',
            isDefault: (bool) ($row['is_default'] ?? false),
        );
    }
}
