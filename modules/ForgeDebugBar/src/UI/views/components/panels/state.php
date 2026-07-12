<?php
/** @var array<string, mixed> $props */
$session = $props['session'] ?? [];

if (empty($session) || isset($session['status'])): ?>
  <div class="fdb-panel__empty"><?= htmlspecialchars($session['status'] ?? 'No session data available.') ?></div>
<?php return; endif;

$sessionId = $session['session_id'] ?? 'N/A';
$complexData = $session['data'] ?? [];
$keyCount = $session['count'] ?? count($complexData);

if (!function_exists('fdb_state_render_value')) {
  function fdb_state_render_value(mixed $value, int $depth = 0): string
  {
    if (is_array($value)) {
      if (empty($value)) {
        return '<span class="fdb-state__empty">empty array</span>';
      }
      $html = '<div class="fdb-state__tree">';
      foreach ($value as $k => $v) {
        $html .= '<div class="fdb-state__node">';
        $html .= '<span class="fdb-state__node-key">' . htmlspecialchars((string) $k) . ':</span> ';
        if (is_array($v)) {
          $html .= '<span class="fdb-state__type">array(' . count($v) . ')</span>';
          $html .= fdb_state_render_value($v, $depth + 1);
        } else {
          $html .= fdb_state_render_leaf($v);
        }
        $html .= '</div>';
      }
      $html .= '</div>';
      return $html;
    }
    return fdb_state_render_leaf($value);
  }
}

if (!function_exists('fdb_state_render_leaf')) {
  function fdb_state_render_leaf(mixed $value): string
  {
    if ($value === null) {
      return '<span class="fdb-state__null">null</span>';
    }
    if (is_bool($value)) {
      return '<span class="fdb-state__bool">' . ($value ? 'true' : 'false') . '</span>';
    }
    if (is_int($value) || is_float($value)) {
      return '<span class="fdb-state__num">' . htmlspecialchars((string) $value) . '</span>';
    }
    $str = (string) $value;
    if ($str === '') {
      return '<span class="fdb-state__empty">""</span>';
    }
    if (strlen($str) > 120) {
      $short = htmlspecialchars(substr($str, 0, 120));
      return '<span class="fdb-state__str" title="' . htmlspecialchars($str) . '">' . $short . '…</span>';
    }
    return '<span class="fdb-state__str">' . htmlspecialchars($str) . '</span>';
  }
}
?>
<div class="fdb-state">
  <div class="fdb-state__summary">
    <div class="fdb-state__row">
      <span class="fdb-state__label">Session ID</span>
      <span class="fdb-state__value fdb-state__value--mono"><?= htmlspecialchars($sessionId) ?></span>
    </div>
    <div class="fdb-state__row">
      <span class="fdb-state__label">Total Keys</span>
      <span class="fdb-state__value"><?= $keyCount ?></span>
    </div>
  </div>
  <?php if (!empty($complexData)): ?>
    <details class="fdb-details" open>
      <summary class="fdb-details__summary">Session Data (<?= $keyCount ?> keys)</summary>
      <div class="fdb-state__props">
        <?php foreach ($complexData as $key => $value): ?>
          <div class="fdb-state__prop">
            <div class="fdb-state__prop-key"><?= htmlspecialchars((string) $key) ?></div>
            <div class="fdb-state__prop-value"><?= fdb_state_render_value($value) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>
</div>
