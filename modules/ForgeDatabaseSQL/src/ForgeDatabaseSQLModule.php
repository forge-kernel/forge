<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL;

use Forge\Core\Module\Attributes\Provides;
use Modules\ForgeDatabaseSQL\DB\DatabaseSetup;
use Forge\Core\Config\Environment;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\Session\Drivers\DatabaseSessionDriver;
use Forge\Core\Session\Session;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeDatabaseSQL',
    version: '0.9.17',
    description: 'SQL database support (SQLite, MySQL, PostgreSQL)',
    order: 0,
    author: 'Forge Team',
    license: 'MIT',
    type: 'core',
    tags: ['database', 'sql', 'sqlite', 'mysql', 'postgresql']
)]
#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/UI/views',
    'components' => 'src/UI/views/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
])]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Provides(interface: DatabaseConnectionInterface::class, version: '0.9.17')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_database_sql" => []
])]
final class ForgeDatabaseSQLModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $env = Environment::getInstance();
        DatabaseSetup::setup($container, $env);
        $this->setupDatabaseSessionDriver($container, $env);
    }

    private function setupDatabaseSessionDriver(Container $container, Environment $env): void
    {
        $driverName = strtolower($env->get('SESSION_DRIVER', 'file'));
        if ($driverName === 'database') {
            try {
                if ($container->has(DatabaseConnectionInterface::class)) {
                    $driver = new DatabaseSessionDriver();
                    $session = new Session($driver);
                    $container->setInstance(SessionInterface::class, $session);
                }
            } catch (\Throwable $e) {
                trigger_error(
                    "Failed to setup database session driver: " . $e->getMessage(),
                    E_USER_WARNING
                );
            }
        }
    }

}
