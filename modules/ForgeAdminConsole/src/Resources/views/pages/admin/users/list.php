<?php
/**
 * @var array<string, mixed> $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 */
$columns = $data['columns'] ?? [];
$rows = $data['rows'] ?? [];
$currentUser = $data['currentUser'] ?? null;

use App\Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\SidebarDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\NavItemDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\IconDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') .
        "\n" . '<link rel="stylesheet" href="/assets/modules/forge-admin-console/css/admin-console.css">',
    'breadcrumbs' => component(name: 'ForgeComponents:admin/breadcrumbs', props: new BreadcrumbsDefinition(items: [
        new BreadcrumbItemDefinition(label: 'Admin', href: '/admin'),
        new BreadcrumbItemDefinition(label: 'Users', active: true),
    ])),
]);

$layoutProps = array_merge($layoutProps ?? [], [
    'sidebar' => new SidebarDefinition(
        brand: 'Admin',
        brandHref: '/admin',
        groups: [
            new NavGroupDefinition(items: [
                new NavItemDefinition(label: 'Dashboard', href: '/admin', icon: new IconDefinition(name: 'home'), active: is_link_active('/admin')),
                new NavItemDefinition(label: 'Account', href: '/admin/account', icon: new IconDefinition(name: 'cog-6-tooth'), active: is_link_active('/admin/account')),
                new NavItemDefinition(label: 'Profile', href: '/admin/profile', icon: new IconDefinition(name: 'user'), active: is_link_active('/admin/profile')),
                new NavItemDefinition(label: 'Users', href: '/admin/users', icon: new IconDefinition(name: 'users'), active: is_link_active('/admin/users')),
            ]),
        ],
    ),
    'userDropdown' => new UserDropdownDefinition(
        name: $currentUser?->getIdentifier() ?? 'User',
        email: $currentUser?->getEmail() ?? '',
        items: [
            new DropdownItemDefinition(label: 'Profile', icon: new IconDefinition(name: 'user'), href: '/admin/profile'),
            new DropdownItemDefinition(label: 'Account', icon: new IconDefinition(name: 'cog-6-tooth'), href: '/admin/account'),
            new DropdownItemDefinition(divider: true),
            new DropdownItemDefinition(label: 'Logout', icon: new IconDefinition(name: 'arrow-right-on-rectangle'), href: '/auth/logout', method: 'POST'),
        ],
    ),
]);
?>

<div class="fc-admin-stack">
  <?= component('ForgeComponents:alert') ?>

  <div>
    <h1 style="font-family: var(--fc-font-sans); font-size: var(--fc-font-size-2xl); font-weight: 700; color: var(--fc-color-text); margin: 0 0 var(--fc-spacing-1);">
      Users
    </h1>
    <p style="font-family: var(--fc-font-sans); font-size: var(--fc-font-size-sm); color: var(--fc-color-text-secondary); margin: 0;">
      Manage your application users.
    </p>
  </div>

  <div class="fc-admin-card">
    <div class="fc-admin-card__body">
      <?= component('ForgeComponents:admin/table', [
          'columns' => $columns,
          'rows' => $rows,
          'emptyMessage' => 'No users found.',
      ]) ?>
    </div>
  </div>
</div>
