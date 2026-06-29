<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Controllers;

use Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
