<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class SubscriptionController
{
    use ControllerHelper;

    public function __construct(
        private readonly BillingSubscriptionService $billingSubscriptionService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/subscription')]
    #[Layout('ForgeBilling:billing')]
    public function show(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $subscription = $tenantId ? $this->billingSubscriptionService->forTenant($tenantId)->current() : null;
        $data = [
            'title' => 'Subscription',
            'subscription' => $subscription,
        ];

        return $this->view(view: "pages/billing/subscription", data: $data);
    }

    #[Route('/billing/subscription/cancel', 'POST')]
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
