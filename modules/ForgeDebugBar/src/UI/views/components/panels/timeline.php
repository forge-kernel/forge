<?php
/** @var array<string, mixed> $props */
$events = $props['timeline'] ?? [];

if (empty($events)): ?>
  <div class="fdb-panel__empty">No timeline events recorded.</div>
<?php return; endif; ?>
<div class="fdb-timeline">
  <?php foreach ($events as $event): ?>
    <div class="fdb-timeline__entry">
      <span class="fdb-timeline__time">[<?= number_format($event['relative_time'] ?? 0, 2) ?>ms]</span>
      <span class="fdb-timeline__name"><?= htmlspecialchars($event['name'] ?? '') ?></span>
      <span class="fdb-badge fdb-badge--<?= $event['label'] ?? 'info' ?>"><?= ucfirst($event['label'] ?? '') ?></span>
      <?php if (!empty($event['origin'] ?? '')): ?>
        <span class="fdb-timeline__origin"><?= htmlspecialchars($event['origin']) ?></span>
      <?php endif; ?>
      <?php if (!empty($event['data'] ?? [])): ?>
        <details class="fdb-details">
          <summary class="fdb-details__summary">Data</summary>
          <pre class="fdb-pre"><?= htmlspecialchars(print_r($event['data'], true)) ?></pre>
        </details>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
