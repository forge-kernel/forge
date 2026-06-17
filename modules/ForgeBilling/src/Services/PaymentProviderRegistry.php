<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Services;

use App\Modules\ForgeBilling\Contracts\PaymentProviderInterface;
use Forge\Core\DI\Attributes\Service;
use RuntimeException;

final class PaymentProviderRegistry
{
    private array $providers = [];

    public function register(PaymentProviderInterface $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function get(string $name): PaymentProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new RuntimeException("Payment provider '{$name}' is not registered.");
        }
        return $this->providers[$name];
    }

    public function all(): array
    {
        return array_values($this->providers);
    }
}
