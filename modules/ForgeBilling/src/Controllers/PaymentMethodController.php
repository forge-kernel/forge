<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\PaymentMethodService;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/billing')]
#[UseMiddleware('web')]
final class PaymentMethodController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Endpoint('/payment-methods')]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $methods = $tenantId ? $this->paymentMethodService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Payment Methods',
            'methods' => $methods,
        ];

        return $this->view(view: "billing/payment-methods", data: $data);
    }

    #[Endpoint('/payment-methods', 'POST')]
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

    #[Endpoint('/payment-methods/{id}/delete', 'POST')]
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
