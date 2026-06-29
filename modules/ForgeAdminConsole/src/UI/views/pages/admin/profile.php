<?php
/**
 * @var array<string, mixed> $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 */
$currentUser = $data['currentUser'] ?? null;

use Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition;
use Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\SidebarDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition;
use Modules\ForgeComponents\Definitions\Admin\NavItemDefinition;
use Modules\ForgeComponents\Definitions\Admin\IconDefinition;
use Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;
use Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;
use Modules\ForgeComponents\Definitions\InputDefinition;
use Modules\ForgeComponents\Definitions\TextareaDefinition;
use Modules\ForgeComponents\Definitions\ButtonDefinition;
use Modules\ForgeComponents\Enums\InputType;
use Modules\ForgeComponents\Enums\ButtonVariant;

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') .
        "\n" . '<link rel="stylesheet" href="/assets/modules/forge-admin-console/css/admin-console.css">',
    'breadcrumbs' => component(name: 'ForgeComponents:admin/breadcrumbs', props: new BreadcrumbsDefinition(items: [
        new BreadcrumbItemDefinition(label: 'Admin', href: '/admin'),
        new BreadcrumbItemDefinition(label: 'Profile', active: true),
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
      Profile
    </h1>
    <p style="font-family: var(--fc-font-sans); font-size: var(--fc-font-size-sm); color: var(--fc-color-text-secondary); margin: 0;">
      Update your public profile information.
    </p>
  </div>

  <div class="fc-admin-card" style="max-width: 32rem;">
    <div class="fc-admin-card__body">
      <?= form_open(attrs: ['class' => 'fc-stack fc-stack--md']) ?>
        <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
            type: InputType::TEXT,
            name: 'identifier',
            id: 'identifier',
            label: 'Identifier',
            value: $currentUser?->getIdentifier() ?? '',
            required: true,
        )) ?>

        <?= component(name: 'ForgeComponents:textarea', props: new TextareaDefinition(
            name: 'bio',
            id: 'bio',
            label: 'Bio',
            rows: 4,
            placeholder: 'Tell us about yourself...',
        )) ?>

        <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
            variant: ButtonVariant::PRIMARY,
        ), slots: ['children' => 'Save profile']) ?>
      <?= form_close() ?>
    </div>
  </div>
</div>
