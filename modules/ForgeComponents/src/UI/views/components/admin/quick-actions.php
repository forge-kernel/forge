<?php
use Modules\ForgeComponents\Definitions\Admin\QuickActionDefinition;
/** @var array<string, mixed> $props */
$actions = $props['actions'] ?? [];
$columns = $props['columns'] ?? 2;
?>
<?php if (!empty($actions)): ?>
<div class="fc-quick-actions fc-quick-actions--<?= $columns ?>">
  <?php foreach ($actions as $action):
    $a = is_array($action) ? new QuickActionDefinition(
      label: $action['label'] ?? '',
      href: $action['href'] ?? '#',
      variant: $action['variant'] ?? 'primary',
      icon: $action['icon'] ?? null,
    ) : $action;
  ?>
  <a href="<?= e($a->href) ?>" class="fc-btn fc-btn--<?= $a->variant ?> fc-quick-action">
    <?php if ($a->icon): ?>
          <?= component(name: 'ForgeComponents:admin/icon', props: new \Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: $a->icon)) ?>
    <?php endif; ?>
    <span><?= e($a->label) ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
