<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Tests;

use App\Modules\ForgeDatabaseSQL\DB\DatabaseConfig;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use InvalidArgumentException;

#[Group("forgedatabase-config")]
final class DatabaseConfigTest extends TestCase
{
    #[Test("sqlite DSN uses sqlite: prefix with path")]
    public function sqlite_dsn(): void
    {
        $config = new DatabaseConfig('sqlite', '/tmp/test.db');
        $this->assertStringContainsString('sqlite:/tmp/test.db', $config->getDsn());
    }

    #[Test("mysql DSN contains host, port, dbname, charset")]
    public function mysql_dsn(): void
    {
        $config = new DatabaseConfig('mysql', 'forge', 'db.example.com', 'root', 'secret', 3307, 'utf8');
        $dsn = $config->getDsn();
        $this->assertStringContainsString('mysql:', $dsn);
        $this->assertStringContainsString('host=db.example.com', $dsn);
        $this->assertStringContainsString('port=3307', $dsn);
        $this->assertStringContainsString('dbname=forge', $dsn);
        $this->assertStringContainsString('charset=utf8', $dsn);
    }

    #[Test("pgsql DSN contains host, port, dbname")]
    public function pgsql_dsn(): void
    {
        $config = new DatabaseConfig('pgsql', 'forge', 'pg.example.com', 'admin', 'pass', 5433);
        $dsn = $config->getDsn();
        $this->assertStringContainsString('pgsql:', $dsn);
        $this->assertStringContainsString('host=pg.example.com', $dsn);
        $this->assertStringContainsString('port=5433', $dsn);
        $this->assertStringContainsString('dbname=forge', $dsn);
        $this->assertStringNotContainsString('charset', $dsn);
    }

    #[Test("mysql default port is 3306")]
    public function mysql_default_port(): void
    {
        $config = new DatabaseConfig('mysql', 'forge');
        $this->assertSame(3306, $config->getPort());
    }

    #[Test("pgsql default port is 5432")]
    public function pgsql_default_port(): void
    {
        $config = new DatabaseConfig('pgsql', 'forge');
        $this->assertSame(5432, $config->getPort());
    }

    #[Test("custom port overrides driver default")]
    public function custom_port(): void
    {
        $config = new DatabaseConfig('mysql', 'forge', port: 3308);
        $this->assertSame(3308, $config->getPort());
    }

    #[Test("invalid driver throws InvalidArgumentException")]
    public function invalid_driver(): void
    {
        $threw = false;
        try {
            new DatabaseConfig('mongodb', 'test');
        } catch (InvalidArgumentException $e) {
            $threw = true;
        }
        $this->assertTrue($threw);
    }

    #[Test("mysql options include persistent connection and init command")]
    public function mysql_options(): void
    {
        $config = new DatabaseConfig('mysql', 'forge');
        $options = $config->getOptions();
        $this->assertArrayHasKey(constant('PDO::ATTR_PERSISTENT'), $options);
        $this->assertArrayHasKey(constant('PDO::MYSQL_ATTR_INIT_COMMAND'), $options);
    }

    #[Test("getter returns stored values")]
    public function getters(): void
    {
        $config = new DatabaseConfig('pgsql', 'mydb', 'myhost', 'myuser', 'mypass', 5432, 'utf8');
        $this->assertSame('pgsql', $config->getDriver());
        $this->assertSame('mydb', $config->getDatabase());
        $this->assertSame('myhost', $config->getHost());
        $this->assertSame('myuser', $config->getUsername());
        $this->assertSame('mypass', $config->getPassword());
        $this->assertSame(5432, $config->getPort());
        $this->assertSame('utf8', $config->getCharset());
    }
}
