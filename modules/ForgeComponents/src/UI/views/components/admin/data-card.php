<?php
/** @var array<string, mixed> $props */
$title = $props['title'] ?? '';
?>
<div class="fc-admin-card">
  <?php if ($title): ?>
        <div class="fc-admin-card__header"><h3 class="fc-admin-card__title"><?= e($title) ?></h3></div>
  <?php endif; ?>
  <div class="fc-admin-card__body">
    <?= slot('default') ?>
    <?= $props['slots']['default'] ?? '' ?>
  </div>
</div>
