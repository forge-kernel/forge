<?php

/** @var \Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition $props */

?>
<nav aria-label="Breadcrumb">
  <ol class="fc-breadcrumbs">
    <?php foreach ($props->items as $index => $item): ?>
      <li class="fc-breadcrumbs__item<?= $item->active ? ' fc-breadcrumbs__item--active' : '' ?>">
        <?php if ($index > 0): ?>
          <span class="fc-breadcrumbs__separator" aria-hidden="true">/</span>
        <?php endif; ?>
        <?php if ($item->href && !$item->active): ?>
          <a href="<?= e($item->href) ?>" class="fc-breadcrumbs__link"><?= e($item->label) ?></a>
        <?php else: ?>
          <span class="fc-breadcrumbs__link"><?= e($item->label) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
