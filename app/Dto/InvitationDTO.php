<?php

declare(strict_types=1);

namespace App\Dto;

final class InvitationDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $inviterName,
        public readonly string $workspaceName,
        public readonly string $inviteUrl,
    ) {
    }
}
