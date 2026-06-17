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
    'bodyClass' => 'fc-admin fc-admin--slim',
]);
?>
<div class="fc-admin__layout">
  <div class="fc-admin__main">
    <?= component(name: 'ForgeComponents:admin/topbar', slots: [
        'user' => isset($layoutProps['user'])
            ? component(name: 'ForgeComponents:admin/user-dropdown', props: $layoutProps['user'])
            : '',
    ]) ?>

    <?php if (isset($layoutSlots['breadcrumbs'])): ?>
                      <div class="fc-admin__breadcrumbs">
                        <?= raw($layoutSlots['breadcrumbs']) ?>
                      </div>
    <?php endif; ?>

    <main class="fc-admin__content fc-admin__content--compact">
      <?= $content ?>
    </main>

    <?php if (isset($layoutProps['footer'])): ?>
            <footer class="fc-admin__footer">
                <?= $layoutProps['footer'] ?>
            </footer>
    <?php endif; ?>
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
