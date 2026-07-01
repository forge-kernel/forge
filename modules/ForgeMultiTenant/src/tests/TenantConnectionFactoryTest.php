<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;

#[Group('multi-tenant')]
final class TenantConnectionFactoryTest extends TestCase
{
    private Container $container;
    private TenantConnectionFactory $factory;

    #[BeforeEach]
    public function setup(): void
    {
        $this->container = Container::getInstance();
        $this->factory = new TenantConnectionFactory($this->container);
    }

    #[Test('COLUMN strategy returns central connection from container')]
    public function column_returns_central(): void
    {
        $stub = new DatabaseConnectionStub();
        $this->container->setInstance(DatabaseConnectionInterface::class, $stub);

        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $connection = $this->factory->forTenant($tenant);

        $this->assertSame($stub, $connection);
    }
}

final class DatabaseConnectionStub implements DatabaseConnectionInterface
{
    public function getPdo(): \PDO { throw new \RuntimeException('Not implemented in stub'); }
    public function exec(string $statement): int|false { return 0; }
    public function prepare(string $statement): \PDOStatement { throw new \RuntimeException('Not implemented in stub'); }
    public function query(string $statement): \PDOStatement { throw new \RuntimeException('Not implemented in stub'); }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollBack(): bool { return true; }
    public function getDriver(): string { return 'mysql'; }
}
