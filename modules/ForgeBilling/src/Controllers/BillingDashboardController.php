<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingPortalService;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

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
