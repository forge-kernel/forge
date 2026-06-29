<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\DTO;

use Modules\ForgeMultiTenant\Enums\Strategy;

final readonly class Tenant
{
    public function __construct(
        public string  $id,
        public string  $domain,
        public ?string $subdomain,
        public Strategy $strategy,
        public ?string $dbName      = null,
        public ?string $connection  = null
    ) {}
}