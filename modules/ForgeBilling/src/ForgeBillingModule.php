<?php

declare(strict_types=1);

namespace Modules\ForgeBilling;

use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeBilling\Contracts\BillableResolverInterface;
use Modules\ForgeBilling\Events\GenerateInvoiceEvent;
use Modules\ForgeBilling\Services\BillableResolver;
use Modules\ForgeBilling\Services\BillingPlanService;
use Modules\ForgeBilling\Services\BillingPortalService;
use Modules\ForgeBilling\Services\BillingSubscriptionService;
use Modules\ForgeBilling\Services\InvoiceService;
use Modules\ForgeBilling\Services\ManualPaymentProvider;
use Modules\ForgeBilling\Services\PaymentMethodService;
use Modules\ForgeBilling\Services\PaymentProviderRegistry;
use Modules\ForgeBilling\Services\PaymentService;
use Forge\Core\Contracts\Database\CentralQueryBuilderInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\Module\LifecycleHookName;

#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/UI/views',
    'components' => 'src/UI/views/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
    'languages' => 'src/Languages',
])]
#[Module(
    name: 'ForgeBilling',
    version: '0.2.6',
    description: 'Billing portal with plans, invoices, and payment provider support',
    order: 5,
    author: 'Forge Team',
    license: 'MIT',
    tags: ['billing', 'invoices', 'payments', 'plans'],
)]
#[Requires(module: "forge-database-sql")]
#[Requires(module: "forge-sql-orm")]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-view")]
#[Requires(module: "forge-components")]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: ['forge_billing' => []])]
#[PostInstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeBilling'])]
#[PostInstall(command: 'db:seed', args: ['--type=', 'module', '--module=', 'ForgeBilling'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=ForgeBilling'])]
final class ForgeBillingModule
{
    public function register(Container $container): void
    {
        $container->singleton(BillableResolverInterface::class, fn() => new BillableResolver($container));

        $container->singleton(PaymentProviderRegistry::class, fn() => new PaymentProviderRegistry());

        $container->singleton(BillingPlanService::class, function () use ($container) {
            return new BillingPlanService(
                $container->get(CentralQueryBuilderInterface::class),
            );
        });

        $container->singleton(InvoiceService::class, function () use ($container) {
            return new InvoiceService(
                $container->get(CentralQueryBuilderInterface::class),
            );
        });

        $container->singleton(BillingSubscriptionService::class, function () use ($container) {
            return new BillingSubscriptionService(
                $container->get(CentralQueryBuilderInterface::class),
                $container->get(BillingPlanService::class),
            );
        });

        $container->singleton(PaymentMethodService::class, function () use ($container) {
            return new PaymentMethodService(
                $container->get(CentralQueryBuilderInterface::class),
            );
        });

        $container->singleton(PaymentService::class, function () use ($container) {
            return new PaymentService(
                $container->get(PaymentProviderRegistry::class),
                $container->get(CentralQueryBuilderInterface::class),
                $container->get(InvoiceService::class),
            );
        });

        $container->singleton(BillingPortalService::class, function () use ($container) {
            return new BillingPortalService(
                $container->get(BillingPlanService::class),
                $container->get(BillingSubscriptionService::class),
                $container->get(InvoiceService::class),
            );
        });

        $this->registerBuiltInProviders($container);
    }

    private function registerBuiltInProviders(Container $container): void
    {
        $registry = $container->get(PaymentProviderRegistry::class);
        $registry->register(new ManualPaymentProvider());
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_LOAD)]
    public function onAfterModuleLoad(): void
    {
        $container = Container::getInstance();
        $dispatcher = $container->get(\Modules\ForgeEvents\Services\EventDispatcher::class);

        $dispatcher->addListener(
            GenerateInvoiceEvent::class,
            function (GenerateInvoiceEvent $event) use ($container): void {
                $subscriptionService = $container->get(BillingSubscriptionService::class);
                $planService = $container->get(BillingPlanService::class);
                $invoiceService = $container->get(InvoiceService::class);
                $qb = $container->get(CentralQueryBuilderInterface::class);

                $plan = $planService->getById($event->planId);
                if (!$plan) {
                    return;
                }

                $sub = $subscriptionService->forTenant($event->tenantId)->current();
                if (!$sub || $sub->id !== $event->subscriptionId) {
                    return;
                }

                $invoiceService->create(
                    tenantId: $event->tenantId,
                    subscriptionId: $event->subscriptionId,
                    amount: $event->planAmount,
                    currency: $event->planCurrency,
                    items: [
                        [
                            'description' => $plan->name . ' - ' . $plan->interval,
                            'amount' => $event->planAmount,
                            'currency' => $event->planCurrency,
                            'quantity' => 1,
                        ],
                    ],
                );

                $nextPeriodEnd = $this->nextPeriodEnd($event->planInterval);
                $qb->setTable('billing_subscriptions')
                    ->where('id', '=', $event->subscriptionId)
                    ->update([
                        'current_period_ends_at' => $nextPeriodEnd->format('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        );
    }

    private function nextPeriodEnd(string $interval): \DateTimeImmutable
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
