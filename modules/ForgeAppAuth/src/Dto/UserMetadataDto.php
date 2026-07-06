<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Dto;

use Modules\ForgeSqlOrm\Dto\BaseDto;

final class UserMetadataDto extends BaseDto
{
    public function __construct(
        public ?string $registered_via = null,
    ) {
    }
}
