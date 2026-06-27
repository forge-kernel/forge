<?php
/** @var array<string, mixed> $props */
$queries = $props['Database'] ?? [];

if (empty($queries)): ?>
  <div class="fdb-panel__empty">No queries data available or database not active.</div>
<?php return; endif; ?>
<div class="fdb-database">
  <?php foreach ($queries as $q):
    $perf = $q['performance'] ?? 'fast';
  ?>
    <div class="fdb-database__entry fdb-database__entry--<?= $perf ?>">
      <div class="fdb-database__sql"><?= htmlspecialchars($q['query'] ?? '') ?></div>
      <div class="fdb-database__meta">
        <span class="fdb-database__time">
          <svg class="fdb-metric__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
          <?= $q['time_ms'] ?? 'N/A' ?> ms
        </span>
        <span class="fdb-database__conn">
          <svg class="fdb-metric__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
          <?= htmlspecialchars($q['connection_name'] ?? '') ?>
        </span>
        <span class="fdb-badge fdb-badge--<?= $perf ?>"><?= $perf ?></span>
      </div>
      <?php $bindings = $q['bindings'] ?? []; if (!empty($bindings)): ?>
        <details class="fdb-database__bindings">
          <summary class="fdb-details__summary">Bindings (<?= count($bindings) ?>)</summary>
          <pre class="fdb-pre"><?= htmlspecialchars(print_r($bindings, true)) ?></pre>
        </details>
      <?php endif; ?>
      <?php if (!empty($q['origin'] ?? '')): ?>
        <p class="fdb-database__origin">Origin: <?= htmlspecialchars($q['origin']) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
