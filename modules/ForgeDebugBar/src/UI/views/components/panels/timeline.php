<?php
/** @var array<string, mixed> $props */
$events = $props['timeline'] ?? [];

if (empty($events)): ?>
  <div class="fdb-panel__empty">No timeline events recorded.</div>
<?php return; endif;

$labelColors = [
  'lifecycle' => 'info',
  'warning'   => 'warning',
  'error'     => 'error',
];
?>
<div class="fdb-timeline">
  <div class="fdb-timeline__header">
    <span class="fdb-timeline__total"><?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?></span>
    <?php if (!empty($events)): ?>
      <span class="fdb-timeline__span"><?= number_format($events[array_key_last($events)]['relative_time'] ?? 0, 1) ?>ms total</span>
    <?php endif; ?>
  </div>
  <div class="fdb-timeline__list">
    <?php foreach ($events as $i => $event):
      $badge = $labelColors[$event['label'] ?? ''] ?? ($event['label'] ?? 'info');
      $hasData = !empty($event['data'] ?? []);
      $hasOrigin = !empty($event['origin'] ?? '');
    ?>
      <div class="fdb-timeline__item<?= $i === count($events) - 1 ? ' fdb-timeline__item--last' : '' ?>">
        <div class="fdb-timeline__gutter">
          <div class="fdb-timeline__dot fdb-timeline__dot--<?= $badge ?>"></div>
          <?php if ($i < count($events) - 1): ?>
            <div class="fdb-timeline__line"></div>
          <?php endif; ?>
        </div>
        <div class="fdb-timeline__content">
          <div class="fdb-timeline__row">
            <span class="fdb-timeline__time"><?= number_format($event['relative_time'] ?? 0, 1) ?>ms</span>
            <span class="fdb-timeline__name"><?= htmlspecialchars($event['name'] ?? '') ?></span>
            <span class="fdb-badge fdb-badge--<?= $badge ?>"><?= htmlspecialchars(ucfirst($event['label'] ?? 'info')) ?></span>
            <?php if ($hasOrigin): ?>
              <span class="fdb-timeline__origin"><?= htmlspecialchars($event['origin']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($hasData): ?>
            <details class="fdb-details">
              <summary class="fdb-details__summary">Data</summary>
              <pre class="fdb-pre"><?= htmlspecialchars(print_r($event['data'], true)) ?></pre>
            </details>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
