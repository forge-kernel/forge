<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Dto;

use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;
use Forge\Traits\HasMetadataToJson;

final class ProfileDto extends BaseDto
{
    use DTOHelper;
    use HasMetadataToJson;

    public function __construct(
        public int $id,
        public int $user_id,
        public string $firstName,
        public ?string $lastName,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
        public ?\DateTimeImmutable $deleted_at = null,
    ) {
    }
}
