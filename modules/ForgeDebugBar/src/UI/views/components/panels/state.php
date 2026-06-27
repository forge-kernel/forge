<?php
/** @var array<string, mixed> $props */
$session = $props['session'] ?? [];

if (empty($session) || isset($session['status'])): ?>
  <div class="fdb-panel__empty"><?= htmlspecialchars($session['status'] ?? 'No session data available.') ?></div>
<?php return; endif;

$sessionId = $session['session_id'] ?? 'N/A';
$complexData = $session['data'] ?? [];
?>
<div class="fdb-state">
  <div class="fdb-state__summary">
    <div class="fdb-state__row">
      <span class="fdb-state__label">Session ID</span>
      <span class="fdb-state__value fdb-state__value--mono"><?= htmlspecialchars($sessionId) ?></span>
    </div>
    <div class="fdb-state__row">
      <span class="fdb-state__label">Total Keys</span>
      <span class="fdb-state__value"><?= $session['count'] ?? count($complexData) ?></span>
    </div>
  </div>
  <?php if (!empty($complexData)): ?>
    <details class="fdb-details" open>
      <summary class="fdb-details__summary">Session Data (<?= count($complexData) ?> top-level keys)</summary>
      <div class="fdb-state__tree">
        <?php foreach ($complexData as $key => $value): ?>
          <div class="fdb-state__node">
            <strong><?= htmlspecialchars($key) ?>:</strong>
            <?php if (is_array($value)): ?>
              <span class="fdb-state__array-indicator">Array (<?= count($value) ?> items)</span>
              <div class="fdb-state__children">
                <?= fdb_render_tree($value) ?>
              </div>
            <?php else: ?>
              <?= htmlspecialchars((string) $value) ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>
</div>
