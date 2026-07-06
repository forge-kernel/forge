<?php

declare(strict_types=1);

namespace Modules\AppAuth\Dto;

use Modules\ForgeSqlOrm\Dto\BaseDto;

final class UserMetadataDto extends BaseDto
{
    public function __construct(
        public ?int $referal_code = null,
        public ?string $registered_via = null,
        public ?UserNotificationDto $notifications = null,
    ) {
        //
    }
}
