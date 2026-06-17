<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB;

use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Config\Environment;
use Forge\Core\Contracts\Database\CentralQueryBuilderInterface;
use Forge\Core\Contracts\Database\DatabaseConfigInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;

final class DatabaseSetup
{
    public static function setup(Container $container, Environment $env): void
    {
        Metrics::start('db_resolution');
        self::ensureDatabaseDirectoryExists($env);
        Metrics::start('db_connection_resolution');
        self::initConnection($container, $env);
        Metrics::stop('db_connection_resolution');
        Metrics::stop('db_resolution');
    }

    private static function ensureDatabaseDirectoryExists(Environment $env): void
    {
        $dbPath = BASE_PATH . $env->get('SQLITE_PATH', '/storage/database');

        if (!is_dir($dbPath)) {
            mkdir($dbPath, 0755, true);
        }

        $sqliteFile = $dbPath . $env->get('SQLITE_DB', '/database.sqlite');

        if (!file_exists($sqliteFile)) {
            touch($sqliteFile);
        }
    }

    private static function initConnection(
        Container $container,
        Environment $env
    ): void {
        $container->singleton(DatabaseConfigInterface::class, function () use ($env) {
            return new DatabaseConfig(
                driver: $env->get("DB_DRIVER", 'sqlite'),
                database: $env->get("DB_DRIVER") === "sqlite"
                ? BASE_PATH . $env->get('SQLITE_PATH', '/storage/database') . $env->get('SQLITE_DB', '/database.sqlite')
                : $env->get("DB_NAME", 'forge'),
                host: $env->get("DB_HOST", 'localhost'),
                username: $env->get("DB_USER", 'root'),
                password: $env->get("DB_PASS", ''),
                port: $env->get("DB_PORT", 3306)
            );
        });

        $container->bind(DatabaseConnectionInterface::class, function () use ($container) {
            $config = $container->get(DatabaseConfigInterface::class);
            return new Connection($config);
        });

        $container->singleton(Migrator::class, function () use ($container) {
            return new Migrator($container->get(DatabaseConnectionInterface::class), $container);
        });

        $container->singleton(CentralQueryBuilderInterface::class, function () use ($container) {
            $config = $container->get(DatabaseConfigInterface::class);
            return new QueryBuilder(new Connection($config));
        });
    }

}
