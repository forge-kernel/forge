<?php

/** @var \App\Modules\ForgeComponents\Definitions\Admin\UserDropdownDefinition $props */

$initials = '';
if ($props->name) {
  $parts = explode(' ', $props->name, 2);
  $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

?>
<div class="fc-user-dropdown">
  <button type="button" class="fc-user-dropdown__trigger" onclick="fcToggleUserMenu(event)">
    <div class="fc-user-dropdown__avatar">
      <?php if ($props->avatar): ?>
        <img src="<?= e($props->avatar) ?>" alt="" class="fc-user-dropdown__avatar-img">
      <?php else: ?>
        <?= e($initials ?: '?') ?>
      <?php endif; ?>
    </div>
    <div class="fc-user-dropdown__info">
      <span class="fc-user-dropdown__name"><?= e($props->name) ?></span>
      <span class="fc-user-dropdown__email"><?= e($props->email) ?></span>
    </div>
    <span class="fc-user-dropdown__chevron"><?= component(name: 'ForgeComponents:admin/icon', props: new \App\Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: 'chevron-down')) ?></span>
  </button>

  <div class="fc-user-dropdown__menu">
    <div class="fc-user-dropdown__menu-header">
      <div class="fc-user-dropdown__menu-header-name"><?= e($props->name) ?></div>
      <div class="fc-user-dropdown__menu-header-email"><?= e($props->email) ?></div>
    </div>
    <div class="fc-user-dropdown__menu-body">
      <?php foreach ($props->items as $item): ?>
        <?php if ($item->divider): ?>
          <div class="fc-user-dropdown__menu-divider"></div>
        <?php elseif ($item->method === 'POST'): ?>
          <form method="POST" action="<?= e($item->href) ?>" style="display: contents;">
            <?= raw(csrf_input()) ?>
            <button type="submit" class="fc-user-dropdown__menu-item">
              <?php if ($item->icon): ?>
                <span class="fc-user-dropdown__menu-item-icon"><?= component(name: 'ForgeComponents:admin/icon', props: $item->icon) ?></span>
              <?php endif; ?>
              <?= e($item->label) ?>
            </button>
          </form>
        <?php else: ?>
          <a href="<?= e($item->href) ?>" class="fc-user-dropdown__menu-item">
            <?php if ($item->icon): ?>
              <span class="fc-user-dropdown__menu-item-icon"><?= component(name: 'ForgeComponents:admin/icon', props: $item->icon) ?></span>
            <?php endif; ?>
            <?= e($item->label) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
