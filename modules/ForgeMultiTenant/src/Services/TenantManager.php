<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use App\Modules\ForgeMultiTenant\DTO\Tenant;
use App\Modules\ForgeMultiTenant\Enums\Strategy;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use ReflectionException;
use RuntimeException;

#[Service]
final class TenantManager
{
    private ?Tenant $current = null;

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function resolveByDomain(string $host): ?Tenant
    {
        if (CentralDomain::isLocal($host)) {
            return null;
        }

        if ($host === CentralDomain::get()) {
            return null;
        }

        $rows = $this->fetchFromDb();
        $source = $rows ?: [];

        foreach ($source as $tenant) {
            $fullHost = $tenant['subdomain']
                ? "{$tenant['subdomain']}.{$tenant['domain']}"
                : $tenant['domain'];

            if ($host === $fullHost) {
                $dto = $this->arrayToDto($tenant);
                $this->current = $dto;
                return $dto;
            }
        }
        return null;
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    private function fetchFromDb(): array
    {
        $rows = $this->container->get(QueryBuilderInterface::class)
            ->setTable('tenants')
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['id']] = $row;
        }
        return $out;
    }

    private function arrayToDto(array $row): Tenant
    {
        return new Tenant(
            id: $row['id'],
            domain: $row['domain'],
            subdomain: $row['subdomain'] ?? null,
            strategy: Strategy::from($row['strategy'] ?? 'column'),
            dbName: $row['db_name'] ?? null,
            connection: $row['connection'] ?? null
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
        try {
            $rows = $this->fetchFromDb() ?: [];
            return array_map(fn(array $t) => $this->arrayToDto($t), $rows);
        } catch (MissingServiceException|ResolveParameterException|ReflectionException $e) {

        }
        return [];
    }

    /** @throws RuntimeException if not found */
    public function find(string $id): Tenant
    {
        try {
            $rows = $this->fetchFromDb() ?: [];
        } catch (MissingServiceException|ResolveParameterException|ReflectionException $e) {

        }
        if (!isset($rows[$id])) {
            throw new RuntimeException("Tenant [$id] not found.");
        }
        return $this->arrayToDto($rows[$id]);
    }
}