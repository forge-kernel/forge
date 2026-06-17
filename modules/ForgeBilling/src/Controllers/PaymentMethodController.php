<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\PaymentMethodService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class PaymentMethodController
{
    use ControllerHelper;

    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/payment-methods')]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $methods = $tenantId ? $this->paymentMethodService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Payment Methods',
            'methods' => $methods,
        ];

        return $this->view(view: "pages/billing/payment-methods", data: $data);
    }

    #[Route('/billing/payment-methods', 'POST')]
    public function store(Request $request): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/payment-methods');
        }

        $this->paymentMethodService->create($tenantId, $request->postData);

        Flash::set('success', 'Payment method added.');
        return Redirect::to('/billing/payment-methods');
    }

    #[Route('/billing/payment-methods/{id}/delete', 'POST')]
    public function destroy(string $id): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/payment-methods');
        }

        $this->paymentMethodService->delete($id, $tenantId);

        Flash::set('success', 'Payment method removed.');
        return Redirect::to('/billing/payment-methods');
    }
}
