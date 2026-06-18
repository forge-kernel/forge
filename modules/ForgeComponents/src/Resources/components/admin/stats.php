<?php
use App\Modules\ForgeComponents\Definitions\Admin\StatCardDefinition;
/** @var array<string, mixed> $props */
$stats = $props['stats'] ?? [];
$columns = $props['columns'] ?? 4;
?>
<?php if (!empty($stats)): ?>
<div class="fc-stats fc-stats--<?= $columns ?>">
  <?php foreach ($stats as $stat):
    $s = is_array($stat) ? new StatCardDefinition(
      label: $stat['label'] ?? '',
      value: $stat['value'] ?? '',
      icon: $stat['icon'] ?? null,
      variant: $stat['variant'] ?? 'default',
      trend: $stat['trend'] ?? null,
    ) : $stat;
  ?>
  <div class="fc-stat-card fc-stat-card--<?= $s->variant ?>">
    <?php if ($s->icon): ?>
          <div class="fc-stat-card__icon"><?= component(name: 'ForgeComponents:admin/icon', props: new \App\Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: $s->icon)) ?></div>
    <?php endif; ?>
    <div class="fc-stat-card__content">
      <span class="fc-stat-card__value"><?= e($s->value) ?></span>
      <span class="fc-stat-card__label"><?= e($s->label) ?></span>
      <?php if ($s->trend): ?>
            <span class="fc-stat-card__trend"><?= e($s->trend) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
