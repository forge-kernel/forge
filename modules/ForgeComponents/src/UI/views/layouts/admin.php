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

use App\Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\DropdownItemDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\IconDefinition;
?>
<div class="fc-admin__layout">
  <div class="fc-admin__main">
    <?= component(name: 'ForgeComponents:admin/topbar', slots: [
        'user' => component(name: 'ForgeComponents:admin/user-dropdown', props: new UserDropdownDefinition(
            name: 'Admin',
            email: 'admin@example.com',
            items: [
                new DropdownItemDefinition(label: 'Profile', icon: new IconDefinition(name: 'user'), href: '#'),
                new DropdownItemDefinition(divider: true),
                new DropdownItemDefinition(label: 'Logout', icon: new IconDefinition(name: 'arrow-right-on-rectangle'), href: '/auth/logout', method: 'POST'),
            ],
        )),
    ]) ?>

    <main class="fc-admin__content">
      <?= $content ?>
    </main>
  </div>
</div>

<script>
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
