<?php

/** @var \Modules\ForgeComponents\Definitions\Admin\NavItemDefinition $props */

$hasChildren = !empty($props->children);

?>
<li class="fc-admin-nav-item<?= $props->active ? ' fc-admin-nav-item--active' : '' ?>">
  <a href="<?= $props->href ?>" class="fc-admin-nav-item__link">
    <?php if ($props->icon): ?>
      <span class="fc-admin-nav-item__icon"><?= component(name: 'ForgeComponents:admin/icon', props: $props->icon) ?></span>
    <?php endif; ?>
    <span class="fc-admin-nav-item__label"><?= e($props->label) ?></span>
    <?php if ($props->badge): ?>
      <span class="fc-admin-nav-item__badge"><?= e($props->badge) ?></span>
    <?php endif; ?>
  </a>
  <?php if ($hasChildren): ?>
    <ul class="fc-admin-nav-group__list" style="padding-left: var(--fc-spacing-5); margin-top: var(--fc-spacing-1);">
      <?php foreach ($props->children as $child): ?>
        <?= component(name: 'ForgeComponents:admin/nav-item', props: $child) ?>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</li>
