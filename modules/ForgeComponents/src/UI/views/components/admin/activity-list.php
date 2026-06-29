<?php
use Modules\ForgeComponents\Definitions\Admin\ActivityItemDefinition;
/** @var array<string, mixed> $props */
$activities = $props['activities'] ?? [];
?>
<?php if (!empty($activities)): ?>
<ul class="fc-activity-list">
  <?php foreach ($activities as $activity):
    $a = is_array($activity) ? new ActivityItemDefinition(
      title: $activity['title'] ?? '',
      time: $activity['time'] ?? '',
      icon: $activity['icon'] ?? null,
      variant: $activity['variant'] ?? 'default',
    ) : $activity;
  ?>
  <li class="fc-activity-item fc-activity-item--<?= $a->variant ?>">
    <?php if ($a->icon): ?>
          <span class="fc-activity-item__icon">
            <?php if (str_starts_with($a->icon, '<svg')): ?>
              <?= $a->icon ?>
            <?php else: ?>
              <?= component(name: 'ForgeComponents:admin/icon', props: new \Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: $a->icon)) ?>
            <?php endif; ?>
          </span>
    <?php endif; ?>
    <div class="fc-activity-item__content">
      <span class="fc-activity-item__title"><?= e($a->title) ?></span>
      <span class="fc-activity-item__time"><?= e($a->time) ?></span>
    </div>
  </li>
  <?php endforeach; ?>
</ul>
<?php else: ?>
<p class="fc-activity-list fc-activity-list--empty">No recent activity</p>
<?php endif; ?>
