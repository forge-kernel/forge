<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm;

use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeSqlOrm\ORM\Cache\QueryCache;
use Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeSqlOrm', version: '0.6.11', description: 'SQL ORM Support (SQLite, MySQL, PostgreSQL)', order: 1, author: 'Forge Team', license: 'MIT', type: 'core', tags: ['database', 'sql', 'orm'])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(interface: DatabaseConnectionInterface::class, version: ">=0.1.0")]
#[Provides(interface: QueryBuilderInterface::class, version: "0.6.11")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_sql_orm" => []
])]
final class ForgeSqlOrmModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        Metrics::start('query_builder_resolution');
        $container->bind(id: QueryBuilderInterface::class, concrete: function ($c) {
            return new QueryBuilder($c->get(DatabaseConnectionInterface::class));
        });
        Metrics::stop('query_builder_resolution');

        $container->singleton(QueryCache::class, function () {
            return new QueryCache(3600);
        });
    }

}
