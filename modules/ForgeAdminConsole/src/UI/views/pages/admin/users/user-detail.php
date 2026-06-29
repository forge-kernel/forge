<?php
/**
 * @var array<string, mixed> $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 */
$user = $data['user'] ?? [];
$id = $user['id'] ?? '';
$currentUser = $data['currentUser'] ?? null;

use Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition;
use Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\SidebarDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\IconDefinition;
use Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;
use Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') .
        "\n" . '<link rel="stylesheet" href="/assets/modules/forge-admin-console/css/admin-console.css">',
    'breadcrumbs' => component(name: 'ForgeComponents:admin/breadcrumbs', props: new BreadcrumbsDefinition(items: [
        new BreadcrumbItemDefinition(label: 'Admin', href: '/admin'),
        new BreadcrumbItemDefinition(label: 'Users', href: '/admin/users'),
        new BreadcrumbItemDefinition(label: '#' . $id, active: true),
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
      User Details
    </h1>
  </div>

  <div class="fc-admin-card" style="max-width: 32rem;">
    <div class="fc-admin-card__body">
      <dl class="fc-stack fc-stack--md" style="font-family: var(--fc-font-sans); font-size: var(--fc-font-size-sm);">
        <div>
          <dt style="font-weight: 600; color: var(--fc-color-text);">ID</dt>
          <dd style="color: var(--fc-color-text-secondary); margin: 0;"><?= e($user['id'] ?? '') ?></dd>
        </div>
        <div>
          <dt style="font-weight: 600; color: var(--fc-color-text);">Identifier</dt>
          <dd style="color: var(--fc-color-text-secondary); margin: 0;"><?= e($user['identifier'] ?? '') ?></dd>
        </div>
        <div>
          <dt style="font-weight: 600; color: var(--fc-color-text);">Email</dt>
          <dd style="color: var(--fc-color-text-secondary); margin: 0;"><?= e($user['email'] ?? '') ?></dd>
        </div>
      </dl>
    </div>
  </div>

  <div>
    <a href="/admin/users" class="fc-btn fc-btn--secondary fc-btn--sm">Back to Users</a>
  </div>
</div>
