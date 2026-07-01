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
use Forge\Core\DI\Container;

#[Group('multi-tenant')]
final class TenantScopedTraitTest extends TestCase
{
    private TenantQueryRewriter $rewriter;

    #[BeforeEach]
    public function setup(): void
    {
        $this->rewriter = new TenantQueryRewriter();

        Container::getInstance()->setInstance(TenantQueryRewriter::class, $this->rewriter);
    }

    #[Test('newQuery calls TenantQueryRewriter::scope via container')]
    public function trait_calls_scope(): void
    {
        $model = new class extends ModelStub {
            protected function newQuery(): QueryBuilderInterface
            {
                $builder = new QueryBuilderFake();
                $rewriter = Container::getInstance()->get(TenantQueryRewriter::class);
                return $rewriter->scope($builder);
            }
        };

        $qb = $model->buildQuery();

        $this->assertInstanceOf(QueryBuilderFake::class, $qb);
        $this->assertNull($qb->lastWhereRaw, 'No tenant set, no WHERE clause');
    }

    #[Test('newQuery adds WHERE tenant_id = ? when tenant is set')]
    public function trait_adds_where_with_tenant(): void
    {
        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant);

        $model = new class extends ModelStub {
            protected function newQuery(): QueryBuilderInterface
            {
                $builder = new QueryBuilderFake();
                $rewriter = Container::getInstance()->get(TenantQueryRewriter::class);
                return $rewriter->scope($builder);
            }
        };

        $qb = $model->buildQuery();

        $this->assertSame('`tenant_id` = ?', $qb->lastWhereRaw);
        $this->assertSame(['tenant-alpha'], $qb->lastWhereParams);
    }

    #[Test('multiple models use same rewriter instance')]
    public function multiple_models_share_rewriter(): void
    {
        $tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->rewriter->setTenant($tenant);

        $modelA = new class extends ModelStub {
            protected function newQuery(): QueryBuilderInterface
            {
                $builder = new QueryBuilderFake();
                return Container::getInstance()->get(TenantQueryRewriter::class)->scope($builder);
            }
        };

        $modelB = new class extends ModelStub {
            protected function newQuery(): QueryBuilderInterface
            {
                $builder = new QueryBuilderFake();
                return Container::getInstance()->get(TenantQueryRewriter::class)->scope($builder);
            }
        };

        $qbA = $modelA->buildQuery();
        $qbB = $modelB->buildQuery();

        $this->assertSame('`tenant_id` = ?', $qbA->lastWhereRaw);
        $this->assertSame(['tenant-alpha'], $qbA->lastWhereParams);
        $this->assertSame('`tenant_id` = ?', $qbB->lastWhereRaw);
        $this->assertSame(['tenant-alpha'], $qbB->lastWhereParams);
    }
}

class ModelStub
{
    public function buildQuery(): QueryBuilderInterface
    {
        return $this->newQuery();
    }

    protected function newQuery(): QueryBuilderInterface
    {
        return new QueryBuilderFake();
    }
}
