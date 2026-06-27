<?php
/** @var array<string, mixed> $props */
$exceptions = $props['exceptions'] ?? [];

if (empty($exceptions)): ?>
  <div class="fdb-panel__empty">No exceptions caught.</div>
<?php return; endif; ?>
<div class="fdb-errors">
  <?php foreach ($exceptions as $ex): ?>
    <div class="fdb-errors__entry">
      <div class="fdb-errors__header">
        <span class="fdb-badge fdb-badge--error"><?= htmlspecialchars($ex['type'] ?? 'Exception') ?></span>
        <span class="fdb-mono" style="color: #64748b; font-size: 12px;">Code: <?= htmlspecialchars((string)($ex['code'] ?? '')) ?></span>
      </div>
      <p class="fdb-errors__message"><?= htmlspecialchars($ex['message'] ?? '') ?></p>
      <p class="fdb-errors__file"><?= htmlspecialchars($ex['file'] ?? '') ?></p>
      <?php if (!empty($ex['trace'] ?? '')): ?>
        <details class="fdb-details">
          <summary class="fdb-details__summary">Stack Trace</summary>
          <pre class="fdb-pre"><?= htmlspecialchars($ex['trace']) ?></pre>
        </details>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
