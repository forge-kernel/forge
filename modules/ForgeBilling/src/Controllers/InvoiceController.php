<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Controllers;

use Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Modules\ForgeBilling\Services\InvoiceService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/billing')]
#[UseMiddleware('web')]
final class InvoiceController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Endpoint('/invoices')]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $invoices = $tenantId ? $this->invoiceService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Invoices',
            'invoices' => $invoices,
        ];

        return $this->view(view: "billing/invoices", data: $data);
    }

    #[Endpoint('/invoices/{id}')]
    #[Layout('ForgeBilling:billing')]
    public function show(string $id): Response
    {
        $invoice = $this->invoiceService->getById($id);
        $items = $invoice ? $this->invoiceService->getItems($id) : [];

        $data = [
            'title' => $invoice ? 'Invoice ' . $invoice->number : 'Invoice Not Found',
            'invoice' => $invoice,
            'items' => $items,
        ];

        return $this->view(view: "billing/invoice-detail", data: $data);
    }
}
