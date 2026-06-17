<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Services\InvoiceService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class InvoiceController
{
    use ControllerHelper;

    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/invoices')]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $invoices = $tenantId ? $this->invoiceService->getForTenant($tenantId) : [];

        $data = [
            'title' => 'Invoices',
            'invoices' => $invoices,
        ];

        return $this->view(view: "pages/billing/invoices", data: $data);
    }

    #[Route('/billing/invoices/{id}')]
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

        return $this->view(view: "pages/billing/invoice-detail", data: $data);
    }
}
