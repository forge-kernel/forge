<?php

/** @var \App\Modules\ForgeComponents\Definitions\Admin\NavGroupDefinition $props */

?>
<div class="fc-admin-nav-group">
  <?php if ($props->heading): ?>
    <h3 class="fc-admin-nav-group__heading"><?= e($props->heading) ?></h3>
  <?php endif; ?>
  <ul class="fc-admin-nav-group__list">
    <?php foreach ($props->items as $item): ?>
      <?= component(name: 'ForgeComponents:admin/nav-item', props: $item) ?>
    <?php endforeach; ?>
  </ul>
</div>
