<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Dto;

use Forge\Core\Dto\BaseDto;

final class UserMetadataDto extends BaseDto
{
    public function __construct(
        public ?string $registered_via = null,
    ) {
    }
}
