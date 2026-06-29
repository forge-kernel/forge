<?php

declare(strict_types=1);

namespace Modules\AppAuth\Dto;

final class CreateUserData
{
    public function __construct(
        public string $identifier,
        public string $email,
        public string $password,
        public string $status = 'active',
        public array|UserMetadataDto|null $metadata = null,
    ) {
        if (is_array($this->metadata)) {
            $this->metadata = !empty($this->metadata) ? UserMetadataDto::from($this->metadata) : null;
        }
    }
}
