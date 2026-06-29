<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

use Modules\ForgeBilling\Enums\PaymentMethodType;

final readonly class PaymentMethod
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public PaymentMethodType $type,
        public string $providerName,
        public string $token,
        public string $lastFour = '',
        public bool $isDefault = false,
    ) {
    }
}
