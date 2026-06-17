<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Services;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Forge\Core\DI\Container;

final class BillableResolver implements BillableResolverInterface
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(): ?string
    {
        if (function_exists('tenant')) {
            $t = tenant();
            if ($t !== null) {
                return $t->id;
            }
        }

        if (function_exists('getCurrentUser')) {
            $user = getCurrentUser();
            if ($user !== null && isset($user->id)) {
                return $user->id;
            }
        }

        return null;
    }
}
