<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Traits;

use Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;

trait TenantScopedTrait
{
    protected function newQuery(): QueryBuilderInterface
    {
        $builder = parent::newQuery();
        $rewriter = Container::getInstance()->get(TenantQueryRewriter::class);
        return $rewriter->scope($builder);
    }
}