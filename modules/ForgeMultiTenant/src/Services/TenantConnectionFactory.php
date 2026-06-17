<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use App\Modules\ForgeDatabaseSQL\DB\Connection;
use App\Modules\ForgeDatabaseSQL\DB\DatabaseConfig;
use App\Modules\ForgeMultiTenant\DTO\Tenant;
use App\Modules\ForgeMultiTenant\Enums\Strategy;
use Forge\Core\Contracts\Database\DatabaseConfigInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use ReflectionException;

final class TenantConnectionFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the correct Connection for the current tenant.
     * COLUMN  → same PDO (already cached)
     * VIEW    → same PDO + run SET once
     * DB      → new PDO (cached per tenant)
     */
    public function forTenant(Tenant $tenant): DatabaseConnectionInterface
    {
        if ($tenant->strategy === Strategy::COLUMN) {
            $key = 'tenant.conn.' . $tenant->id;
            try {
                return $this->container->get($key) ??
                    $this->container->singleton($key, fn() => $this->build($tenant));
            } catch (MissingServiceException | ResolveParameterException | ReflectionException $e) {

            }
        }

        return $this->build($tenant);
    }

    /**
     * @throws ResolveParameterException
     * @throws ReflectionException
     * @throws MissingServiceException
     */
    private function build(Tenant $tenant): DatabaseConnectionInterface
    {
        return match ($tenant->strategy) {
            Strategy::COLUMN => $this->container->get(DatabaseConnectionInterface::class),
            Strategy::VIEW => $this->viewConnection($tenant),
            Strategy::DB => $this->dbConnection($tenant),
        };
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    private function viewConnection(Tenant $tenant): Connection
    {
        $conn = $this->container->get(Connection::class);
        $conn->exec("SET app.tenant = " . $conn->getPdo()->quote($tenant->id));
        return $conn;
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    private function dbConnection(Tenant $tenant): Connection
    {
        /** @var DatabaseConfigInterface $base */
        $base = $this->container->get(DatabaseConfigInterface::class);

        if ($base->getDriver() === 'sqlite' && $tenant->dbName !== null) {
            $dbFile = BASE_PATH . '/storage/database/' . $tenant->dbName . '.sqlite';
            if (!file_exists($dbFile)) {
                touch($dbFile);
            }
            $config = new DatabaseConfig(
                driver: 'sqlite',
                database: $dbFile,
                host: $base->getHost(),
                username: $base->getUsername(),
                password: $base->getPassword(),
                port: $base->getPort(),
                charset: $base->getCharset()
            );
            return new Connection($config);
        }

        // MySQL / PostgreSQL
        $config = new DatabaseConfig(
            driver: $base->getDriver(),
            database: $tenant->dbName,
            host: $base->getHost(),
            username: $base->getUsername(),
            password: $base->getPassword(),
            port: $base->getPort(),
            charset: $base->getCharset()
        );
        return new Connection($config);
    }
}
