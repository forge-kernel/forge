<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\InvoiceService;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

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
