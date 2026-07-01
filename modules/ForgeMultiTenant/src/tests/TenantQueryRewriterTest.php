<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

#[Group('multi-tenant')]
final class TenantQueryRewriterTest extends TestCase
{
    private TenantQueryRewriter $rewriter;

    #[BeforeEach]
    public function setup(): void
    {
        $this->rewriter = new TenantQueryRewriter();
    }

    #[Test('scope returns QB unchanged when no tenant is set')]
    public function scope_no_tenant_passthrough(): void
    {
        $qb = new QueryBuilderFake();
        $result = $this->rewriter->scope($qb);

        $this->assertSame($qb, $result);
        $this->assertNull($qb->lastWhereRaw);
    }

    #[Test('scope adds WHERE tenant_id = ? for COLUMN strategy')]
    public function scope_column_adds_where(): void
    {
        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant);

        $qb = new QueryBuilderFake();
        $this->rewriter->scope($qb);

        $this->assertSame('`tenant_id` = ?', $qb->lastWhereRaw);
        $this->assertSame(['tenant-alpha'], $qb->lastWhereParams);
    }

    #[Test('scope returns QB unchanged for DATABASE strategy')]
    public function scope_database_passthrough(): void
    {
        $tenant = new Tenant('tenant-beta', 'forge-v3.test', 'customer1', Strategy::DB);
        $this->rewriter->setTenant($tenant);

        $qb = new QueryBuilderFake();
        $result = $this->rewriter->scope($qb);

        $this->assertSame($qb, $result);
        $this->assertNull($qb->lastWhereRaw);
    }

    #[Test('scope returns QB unchanged for VIEW strategy')]
    public function scope_view_passthrough(): void
    {
        $tenant = new Tenant('tenant-gamma', 'forge-v3.test', 'customer2', Strategy::VIEW);
        $this->rewriter->setTenant($tenant);

        $qb = new QueryBuilderFake();
        $result = $this->rewriter->scope($qb);

        $this->assertSame($qb, $result);
        $this->assertNull($qb->lastWhereRaw);
    }

    #[Test('reset clears tenant state')]
    public function reset_clears_state(): void
    {
        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant);
        $this->rewriter->reset();

        $qb = new QueryBuilderFake();
        $this->rewriter->scope($qb);

        $this->assertNull($qb->lastWhereRaw, 'After reset, scope should not add WHERE');
    }

    #[Test('multiple instances do not interfere')]
    public function multiple_instances_independent(): void
    {
        $alpha = new Tenant('tenant-alpha', 'alpha.com', null, Strategy::COLUMN);
        $beta = new Tenant('tenant-beta', 'beta.com', null, Strategy::DB);

        $rewriterA = new TenantQueryRewriter();
        $rewriterB = new TenantQueryRewriter();

        $rewriterA->setTenant($alpha);
        $rewriterB->setTenant($beta);

        $qbA = new QueryBuilderFake();
        $rewriterA->scope($qbA);
        $this->assertSame('`tenant_id` = ?', $qbA->lastWhereRaw);
        $this->assertSame(['tenant-alpha'], $qbA->lastWhereParams);

        $qbB = new QueryBuilderFake();
        $rewriterB->scope($qbB);
        $this->assertNull($qbB->lastWhereRaw, 'DATABASE strategy should not add WHERE');
    }

    #[Test('setTenant after reset works correctly')]
    public function set_tenant_after_reset(): void
    {
        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant);
        $this->rewriter->reset();

        $tenant2 = new Tenant('tenant-beta', 'beta.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant2);

        $qb = new QueryBuilderFake();
        $this->rewriter->scope($qb);

        $this->assertSame(['tenant-beta'], $qb->lastWhereParams);
    }
}

final class QueryBuilderFake implements QueryBuilderInterface
{
    public ?string $lastWhereRaw = null;
    public ?array $lastWhereParams = null;

    public function whereRaw(string $sql, array $params = []): self
    {
        $this->lastWhereRaw = $sql;
        $this->lastWhereParams = $params;
        return $this;
    }

    public function lockForUpdate(): self { return $this; }
    public function getConnection(): \Forge\Core\Contracts\Database\DatabaseConnectionInterface { throw new \RuntimeException('not implemented'); }
    public function setTable(string $table): self { return $this; }
    public function select(string ...$columns): self { return $this; }
    public function selectRaw(string $expression, array $params = []): self { return $this; }
    public function whereNull(string $column): self { return $this; }
    public function whereNotNull(string $column): self { return $this; }
    public function orderBy(string $column, string $direction = "ASC"): self { return $this; }
    public function limit(int $count): self { return $this; }
    public function offset(int $count): self { return $this; }
    public function createTableFromAttributes(string $table, array $columns, array $indexes = []): string { return ''; }
    public function get(): array { return []; }
    public function execute(string $sql): void {}
    public function getRaw(): array { return []; }
    public function insert(array $data): int { return 0; }
    public function insertGetId(array $data): int { return 0; }
    public function insertMany(array $rows): int { return 0; }
    public function update(array $data): int { return 0; }
    public function delete(): int { return 0; }
    public function find(int $id): ?array { return null; }
    public function where(string $column, string $operator, mixed $value): self { return $this; }
    public function whereIn(string $column, array $values): self { return $this; }
    public function whereNotIn(string $column, array $values): self { return $this; }
    public function first(): ?array { return null; }
    public function leftJoin(string $table, string $first, string $operator, string $second): self { return $this; }
    public function join(string $table, string $first, string $operator, string $second, string $type = "INNER"): self { return $this; }
    public function rightJoin(string $table, string $first, string $operator, string $second): self { return $this; }
    public function groupBy(string ...$columns): self { return $this; }
    public function having(string $column, string $operator, mixed $value): self { return $this; }
    public function exists(): bool { return false; }
    public function reset(): self { return $this; }
    public function transaction(callable $callback): mixed { return $callback($this); }
    public function beginTransaction(): self { return $this; }
    public function inTransaction(): bool { return false; }
    public function commit(): self { return $this; }
    public function rollback(): self { return $this; }
    public function count(string $column = "*"): int { return 0; }
    public function sum(string $column): float { return 0.0; }
    public function avg(string $column): float { return 0.0; }
    public function min(string $column): float { return 0.0; }
    public function max(string $column): float { return 0.0; }
    public function table(?string $name): self { return $this; }
    public function createTable(string $tableName, array $columns, bool $ifNotExists = false): string { return ''; }
    public function createIndex(string $indexName, array $columns, bool $unique = false): string { return ''; }
    public function dropTable(string $tableName): string { return ''; }
    public function getSql(): string { return ''; }
}
