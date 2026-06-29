<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Layouts;

use Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition;
use Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\IconDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\SidebarDefinition;
use Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;

final class AdminLayout
{
    /**
     * Build the layout props and slots for ForgeComponents:admin-default layout.
     *
     * @param string $activeItem One of: overview, traces, slow-queries, or module paths like /hub/modules.
     * @param array<int, array{label: string, href?: string, active?: bool}> $breadcrumbs
     * @param object|null $user Current user object with identifier and email properties.
     * @return array{layoutProps: array<string, mixed>, layoutSlots: array<string, mixed>}
     */
    public static function build(string $activeItem, array $breadcrumbs, ?object $user): array
    {
        $layoutProps = [
            'sidebar' => new SidebarDefinition(
                brand: 'ForgeHub',
                brandHref: '/hub',
                tagline: 'Administration',
                groups: [
                    new NavGroupDefinition(
                        heading: 'Platform',
                        items: [
                            new NavItemDefinition(label: 'Dashboard', href: '/hub', icon: new IconDefinition(name: 'home'), active: $activeItem === '/hub'),
                            new NavItemDefinition(label: 'Modules', href: '/hub/modules', icon: new IconDefinition(name: 'cube'), active: $activeItem === '/hub/modules'),
                            new NavItemDefinition(label: 'Logs', href: '/hub/logs', icon: new IconDefinition(name: 'document-text'), active: $activeItem === '/hub/logs'),
                            new NavItemDefinition(label: 'Commands', href: '/hub/commands', icon: new IconDefinition(name: 'document-text'), active: $activeItem === '/hub/commands'),
                            new NavItemDefinition(label: 'Cache', href: '/hub/cache', icon: new IconDefinition(name: 'clock'), active: $activeItem === '/hub/cache'),
                            new NavItemDefinition(label: 'Queues', href: '/hub/queues', icon: new IconDefinition(name: 'document-text'), active: $activeItem === '/hub/queues'),
                        ],
                    ),
                    new NavGroupDefinition(
                        heading: 'Observability',
                        items: [
                            new NavItemDefinition(label: 'Overview', href: '/hub/observability', icon: new IconDefinition(name: 'chart-bar'), active: $activeItem === 'overview'),
                            new NavItemDefinition(label: 'Traces', href: '/hub/observability/traces', icon: new IconDefinition(name: 'clock'), active: $activeItem === 'traces'),
                            new NavItemDefinition(label: 'Slow Queries', href: '/hub/observability/slow-queries', icon: new IconDefinition(name: 'database'), active: $activeItem === 'slow-queries'),
                        ],
                    ),
                ],
                statusOnline: true,
                statusLabel: 'Monitoring active',
            ),
            'user' => new UserDropdownDefinition(
                name: $user?->identifier ?? 'Admin',
                email: $user?->email ?? '',
                items: [
                    new DropdownItemDefinition(label: 'Profile', href: '/hub/profile', icon: new IconDefinition(name: 'user')),
                    new DropdownItemDefinition(label: 'Settings', href: '/hub/settings', icon: new IconDefinition(name: 'cog-6-tooth')),
                    new DropdownItemDefinition(label: 'Sign out', href: '/auth/logout', icon: new IconDefinition(name: 'arrow-right-on-rectangle')),
                ],
            ),
        ];

        $breadcrumbDefinitions = [];
        foreach ($breadcrumbs as $item) {
            $breadcrumbDefinitions[] = new BreadcrumbItemDefinition(
                label: $item['label'],
                href: $item['href'] ?? '',
                active: $item['active'] ?? false,
            );
        }

        $layoutSlots = [
            'breadcrumbs' => component(name: 'ForgeComponents:admin/breadcrumbs', props: new BreadcrumbsDefinition(items: $breadcrumbDefinitions)),
        ];

        return [
            'layoutProps' => $layoutProps,
            'layoutSlots' => $layoutSlots,
        ];
    }
}
