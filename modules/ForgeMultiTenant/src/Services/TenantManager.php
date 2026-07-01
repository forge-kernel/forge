<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Exceptions\TenantNotFoundException;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Injectable;

#[Injectable]
final class TenantManager
{
    private ?Tenant $current = null;

    private ?array $tenantsById = null;
    private ?array $tenantsByHost = null;

    public function __construct(
        private readonly ?QueryBuilderInterface $queryBuilder = null,
        private readonly ?\Closure $dataCallback = null,
    ) {
    }

    public function resolveByDomain(string $host): ?Tenant
    {
        $this->current = null;

        if (CentralDomain::isLocal($host)) {
            return null;
        }

        if ($host === CentralDomain::get()) {
            return null;
        }

        $this->loadTenants();

        if (isset($this->tenantsByHost[$host])) {
            $this->current = $dto = $this->tenantsByHost[$host];
            return $dto;
        }

        return null;
    }

    public function clearCache(): void
    {
        $this->tenantsById = null;
        $this->tenantsByHost = null;
    }

    private function loadTenants(): array
    {
        if ($this->tenantsById !== null) {
            return $this->tenantsById;
        }

        $rows = $this->dataCallback
            ? ($this->dataCallback)()
            : ($this->queryBuilder
                ? $this->queryBuilder
                    ->setTable('tenants')
                    ->select('id', 'domain', 'subdomain', 'strategy', 'db_name', 'connection')
                    ->get()
                : []);

        $byId = [];
        $byHost = [];

        foreach ($rows as $row) {
            $dto = $this->arrayToDto($row);
            $byId[$dto->id] = $dto;

            $fullHost = $dto->subdomain
                ? "{$dto->subdomain}.{$dto->domain}"
                : $dto->domain;

            $byHost[$fullHost] = $dto;
        }

        $this->tenantsById = $byId;
        $this->tenantsByHost = $byHost;

        return $byId;
    }

    private function arrayToDto(array $row): Tenant
    {
        $strategy = Strategy::tryFrom($row['strategy'] ?? 'column') ?? Strategy::COLUMN;

        return new Tenant(
            id: $row['id'],
            domain: $row['domain'],
            subdomain: $row['subdomain'] ?? null,
            strategy: $strategy,
            dbName: $row['db_name'] ?? null,
            connection: $row['connection'] ?? null,
        );
    }

    public function current(): ?Tenant
    {
        return $this->current;
    }

    public function tenantId(): ?string
    {
        return $this->current?->id;
    }

    /** @return Tenant[] */
    public function all(): array
    {
        return array_values($this->loadTenants());
    }

    /** @throws TenantNotFoundException */
    public function find(string $id): Tenant
    {
        $dto = ($this->loadTenants())[$id] ?? throw new TenantNotFoundException($id);
        return $dto;
    }
}
