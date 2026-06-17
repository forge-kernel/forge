<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\DTO;

use App\Modules\ForgeMultiTenant\Enums\Strategy;

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