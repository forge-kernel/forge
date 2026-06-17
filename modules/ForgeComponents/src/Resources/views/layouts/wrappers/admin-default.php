<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 * @var string $parentLayout
 */

$parentLayout = 'ForgeComponents:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'fc-admin',
]);

use App\Modules\ForgeComponents\Definitions\Admin\SidebarDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\NavItemDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\IconDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;

$sidebar = new SidebarDefinition(
    brand: 'ForgeHub',
    tagline: 'Business Management Platform',
    groups: [
        new NavGroupDefinition(items: [
            new NavItemDefinition(label: 'Dashboard', href: '/dashboard', icon: new IconDefinition(name: 'home'), active: is_link_active('/dashboard')),
            new NavItemDefinition(label: 'Products', href: '/hub/products', icon: new IconDefinition(name: 'cube'), active: is_link_active('/products')),
            new NavItemDefinition(label: 'Restock List', href: '/hub/restock', icon: new IconDefinition(name: 'arrow-trending-up')),
            new NavItemDefinition(label: 'Finance', href: '/hub/finance', icon: new IconDefinition(name: 'currency-dollar')),
            new NavItemDefinition(label: 'Contacts', href: '/hub/contacts', icon: new IconDefinition(name: 'users')),
            new NavItemDefinition(label: 'Reports', href: '/hub/reports', icon: new IconDefinition(name: 'chart-bar')),
            new NavItemDefinition(label: 'Settings', href: '/hub/settings', icon: new IconDefinition(name: 'cog-6-tooth')),
        ]),
    ],
);

$user = new UserDropdownDefinition(
    name: 'John Doe',
    email: 'john@example.com',
    items: [
        new DropdownItemDefinition(label: 'Profile', icon: new IconDefinition(name: 'user'), href: '/hub/profile'),
        new DropdownItemDefinition(label: 'Settings', icon: new IconDefinition(name: 'cog-6-tooth'), href: '/hub/settings'),
        new DropdownItemDefinition(divider: true),
        new DropdownItemDefinition(label: 'Logout', icon: new IconDefinition(name: 'arrow-right-on-rectangle'), href: '/auth/logout', method: 'POST'),
    ],
);
?>
<div class="fc-admin__layout">
  <?= component(name: 'ForgeComponents:admin/sidebar', props: $sidebar) ?>

  <div class="fc-admin__main">
    <?= component(name: 'ForgeComponents:admin/topbar', slots: [
        'user' => component(name: 'ForgeComponents:admin/user-dropdown', props: $user),
    ]) ?>

    <?php if (isset($layoutSections['breadcrumbs'])): ?>
                  <div class="fc-admin__breadcrumbs">
                    <?= raw($layoutSections['breadcrumbs']) ?>
                  </div>
    <?php endif; ?>

    <main class="fc-admin__content fc-admin__content--compact">
      <?= $content ?>
    </main>
  </div>
</div>

<script>
function fcToggleSidebar() {
  var sidebar = document.getElementById('fc-sidebar');
  var overlay = document.getElementById('fc-sidebar-overlay');
  if (sidebar) { sidebar.classList.toggle('fc-admin__sidebar--open'); }
  if (overlay) { overlay.classList.toggle('fc-admin__sidebar-overlay--visible'); }
}
function fcToggleUserMenu(event) {
  event.stopPropagation();
  var dropdown = event.currentTarget.closest('.fc-user-dropdown');
  if (dropdown) { dropdown.classList.toggle('fc-user-dropdown--open'); }
}
document.addEventListener('click', function (event) {
  var dropdowns = document.querySelectorAll('.fc-user-dropdown');
  for (var i = 0; i < dropdowns.length; i++) {
    if (!dropdowns[i].contains(event.target)) {
      dropdowns[i].classList.remove('fc-user-dropdown--open');
    }
  }
});
</script>
