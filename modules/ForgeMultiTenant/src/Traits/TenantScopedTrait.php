<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Traits;

use App\Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

trait TenantScopedTrait
{
    protected function newQuery(): QueryBuilderInterface
    {
        $builder = parent::newQuery();
        return TenantQueryRewriter::scope($builder);
    }
}