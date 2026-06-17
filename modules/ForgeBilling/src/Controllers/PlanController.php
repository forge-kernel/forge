<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Controllers;

use App\Modules\ForgeBilling\Contracts\BillableResolverInterface;
use App\Modules\ForgeBilling\Enums\SubscriptionStatus;
use App\Modules\ForgeBilling\Services\BillingPlanService;
use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
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
final class PlanController
{
    use ControllerHelper;

    public function __construct(
        private readonly BillingPlanService $billingPlanService,
        private readonly BillingSubscriptionService $billingSubscriptionService,
        private readonly PaymentMethodService $paymentMethodService,
        private readonly BillableResolverInterface $billableResolver,
    ) {
    }

    #[Route('/billing/plans')]
    #[Layout('ForgeBilling:billing')]
    public function index(): Response
    {
        $tenantId = $this->billableResolver->resolve();
        $plans = $this->billingPlanService->getAll();
        $subscription = $tenantId ? $this->billingSubscriptionService->forTenant($tenantId)->current() : null;

        $data = [
            'title' => 'Pricing Plans',
            'plans' => $plans,
            'subscription' => $subscription,
        ];

        return $this->view(view: "pages/billing/plans", data: $data);
    }

    #[Route('/billing/plans/{id}/subscribe', 'POST')]
    public function subscribe(Request $request, string $id): Response
    {
        $tenantId = $this->billableResolver->resolve();

        if (!$tenantId) {
            Flash::set('error', 'Unable to identify billing entity.');
            return Redirect::to('/billing/plans');
        }

        $plan = $this->billingPlanService->getById($id);
        if (!$plan || !$plan->isActive) {
            Flash::set('error', 'Plan not found or unavailable.');
            return Redirect::to('/billing/plans');
        }

        $methods = $this->paymentMethodService->getForTenant($tenantId);
        if (empty($methods)) {
            Flash::set('error', 'Please add a payment method before subscribing.');
            return Redirect::to('/billing/payment-methods');
        }

        $periodEnd = $this->calculatePeriodEnd($plan->interval);

        $this->billingSubscriptionService->assign(
            tenantId: $tenantId,
            planId: $id,
            status: SubscriptionStatus::ACTIVE,
            currentPeriodEndsAt: $periodEnd,
        );

        Flash::set('success', 'Subscribed to ' . $plan->name . ' successfully.');
        return Redirect::to('/billing');
    }

    private function calculatePeriodEnd(string $interval): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();
        return match ($interval) {
            'month', 'monthly' => $now->modify('+1 month'),
            'year', 'yearly' => $now->modify('+1 year'),
            'week', 'weekly' => $now->modify('+1 week'),
            'day', 'daily' => $now->modify('+1 day'),
            'one_time' => $now->modify('+100 years'),
            default => $now->modify('+1 month'),
        };
    }
}
