<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/billing')]
#[UseMiddleware('web')]
final class SubscriptionController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly BillingSubscriptionService $billingSubscriptionService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Endpoint('/subscription')]
    #[Layout('ForgeBilling:billing')]
    public function show(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $subscription = $tenantId ? $this->billingSubscriptionService->forTenant($tenantId)->current() : null;
        $data = [
            'title' => 'Subscription',
            'subscription' => $subscription,
        ];

        return $this->view(view: "billing/subscription", data: $data);
    }

    #[Endpoint('/subscription/cancel', 'POST')]
    public function cancel(): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/subscription');
        }

        $this->billingSubscriptionService->cancel($tenantId);
        Flash::set('success', 'Subscription cancelled.');
        return Redirect::to('/billing/subscription');
    }
}
