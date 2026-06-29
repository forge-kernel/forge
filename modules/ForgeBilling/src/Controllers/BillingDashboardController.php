<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Controllers;

use Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Modules\ForgeBilling\Services\BillingPortalService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/billing')]
#[UseMiddleware('web')]
final class BillingDashboardController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly BillingPortalService $billingPortalService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Endpoint]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $data = [];
        if (!$tenantId) {
            $data = [
                'title' => 'Billing Overview',
                'subscription' => null,
                'latestInvoice' => null,
                'invoices' => [],
                'plans' => [],
                'isActive' => false,
                'onTrial' => false,
            ];
            return $this->view(view: "billing/dashboard", data: $data);
        }

        $dataOverview = $this->billingPortalService->overview($tenantId);

        $data = [
            'title' => 'Billing Overview',
            'data' => $dataOverview,
        ];
        return $this->view(view: "billing/dashboard", data: $data);
    }
}
