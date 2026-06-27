<?php
/** @var array<string, mixed> $props */
$memory = $props['memory'] ?? [];
$time = $props['time'] ?? 'N/A';

if (empty($memory)): ?>
  <div class="fdb-panel__empty">No resource information available.</div>
<?php return; endif; ?>
<div class="fdb-resources">
  <div class="fdb-resources__grid">
    <div class="fdb-resources__card">
      <div class="fdb-resources__card-label">Execution Time</div>
      <div class="fdb-resources__card-value"><?= $time ?></div>
    </div>
    <div class="fdb-resources__card">
      <div class="fdb-resources__card-label">Memory Limit</div>
      <div class="fdb-resources__card-value"><?= htmlspecialchars(ini_get('memory_limit')) ?></div>
    </div>
    <div class="fdb-resources__card">
      <div class="fdb-resources__card-label">Peak Usage</div>
      <div class="fdb-resources__card-value fdb-resources__card-value--peak"><?= htmlspecialchars($memory['peak'] ?? 'N/A') ?></div>
    </div>
    <div class="fdb-resources__card">
      <div class="fdb-resources__card-label">Current Usage</div>
      <div class="fdb-resources__card-value"><?= htmlspecialchars($memory['current'] ?? 'N/A') ?></div>
    </div>
    <div class="fdb-resources__card">
      <div class="fdb-resources__card-label">Memory Used</div>
      <div class="fdb-resources__card-value"><?= htmlspecialchars($memory['used'] ?? 'N/A') ?></div>
    </div>
  </div>
  <?php
  $percentage = $memory['percentage'] ?? null;
  if ($percentage !== 'Unlimited' && $percentage !== null):
    $percentValue = (float) str_replace(['%', ' '], '', $percentage);
    $barClass = $percentValue > 80 ? 'danger' : ($percentValue > 50 ? 'warning' : 'success');
  ?>
    <div class="fdb-resources__bar-section">
      <div class="fdb-resources__bar-label">Usage — <?= htmlspecialchars($percentage) ?></div>
      <div class="fdb-resources__bar">
        <div class="fdb-resources__bar-fill fdb-resources__bar-fill--<?= $barClass ?>" style="width: <?= htmlspecialchars($percentage) ?>"></div>
      </div>
    </div>
  <?php elseif ($percentage === 'Unlimited'): ?>
    <div class="fdb-resources__bar-section">
      <div class="fdb-resources__bar-label">Usage — Unlimited</div>
    </div>
  <?php endif; ?>
</div>
