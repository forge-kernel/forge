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

$sidebar = $layoutProps['sidebar'] ?? new SidebarDefinition(
    brand: 'Admin',
    brandHref: '/admin',
    groups: [
        new NavGroupDefinition(items: [
            new NavItemDefinition(label: 'Dashboard', href: '/admin', icon: new IconDefinition(name: 'home'), active: is_link_active('/admin')),
        ]),
    ],
);

$user = $layoutProps['userDropdown'] ?? new UserDropdownDefinition(
    name: 'User',
    email: '',
    items: [
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

    <main class="fc-admin__content fc-admin__content">
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
