<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Controllers;

use Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Modules\ForgeBilling\Services\PaymentMethodService;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
