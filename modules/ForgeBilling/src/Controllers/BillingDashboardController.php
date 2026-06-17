<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\BillingPortalService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class BillingDashboardController
{
    use ControllerHelper;

    public function __construct(
        private readonly BillingPortalService $billingPortalService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing')]
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
            return $this->view(view: "pages/billing/dashboard", data: $data);
        }

        $dataOverview = $this->billingPortalService->overview($tenantId);

        $data = [
            'title' => 'Billing Overview',
            'data' => $dataOverview,
        ];
        return $this->view(view: "pages/billing/dashboard", data: $data);
    }
}
