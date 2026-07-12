<?php
/** @var array<string, mixed> $props */
$req = $props['request'] ?? [];

if (empty($req) || isset($req['error'])): ?>
  <div class="fdb-panel__empty">No request information available.</div>
<?php return; endif;

if (!function_exists('fdb_ov_render_value')) {
  function fdb_ov_render_value(mixed $value): string
  {
    if (is_array($value)) {
      if (empty($value)) {
        return '<span class="fdb-ov__null">empty</span>';
      }
      $html = '<div class="fdb-ov__tree">';
      foreach ($value as $k => $v) {
        $html .= '<div class="fdb-ov__node">';
        $html .= '<span class="fdb-ov__node-key">' . htmlspecialchars((string) $k) . ':</span> ';
        if (is_array($v)) {
          $html .= '<span class="fdb-ov__type">array(' . count($v) . ')</span>';
          $html .= fdb_ov_render_value($v);
        } else {
          $html .= fdb_ov_render_leaf($v);
        }
        $html .= '</div>';
      }
      $html .= '</div>';
      return $html;
    }
    return fdb_ov_render_leaf($value);
  }
}

if (!function_exists('fdb_ov_render_leaf')) {
  function fdb_ov_render_leaf(mixed $value): string
  {
    if ($value === null) {
      return '<span class="fdb-ov__null">null</span>';
    }
    if (is_bool($value)) {
      return '<span class="fdb-ov__bool">' . ($value ? 'true' : 'false') . '</span>';
    }
    if (is_int($value) || is_float($value)) {
      return '<span class="fdb-ov__num">' . htmlspecialchars((string) $value) . '</span>';
    }
    $str = (string) $value;
    if ($str === '') {
      return '<span class="fdb-ov__null">""</span>';
    }
    if (strlen($str) > 120) {
      $short = htmlspecialchars(substr($str, 0, 120));
      return '<span class="fdb-ov__str" title="' . htmlspecialchars($str) . '">' . $short . '…</span>';
    }
    return '<span class="fdb-ov__str">' . htmlspecialchars($str) . '</span>';
  }
}

$url = $req['url'] ?? '';
$method = strtoupper($req['method'] ?? '');
$ip = $req['ip'] ?? '';
$headers = $req['headers'] ?? [];
$query = $req['query'] ?? [];
$body = $req['body'] ?? [];
$cookies = $req['cookies'] ?? [];
$files = $req['files'] ?? [];
?>
<div class="fdb-overview">
  <div class="fdb-overview__hero">
    <div class="fdb-overview__method-badge fdb-badge fdb-badge--method fdb-badge--<?= strtolower($method) ?>"><?= htmlspecialchars($method) ?></div>
    <span class="fdb-overview__url"><?= htmlspecialchars($url) ?></span>
  </div>
  <div class="fdb-overview__meta">
    <?php if ($ip): ?>
      <div class="fdb-overview__meta-item">
        <span class="fdb-overview__meta-label">IP</span>
        <span class="fdb-overview__meta-value"><?= htmlspecialchars($ip) ?></span>
      </div>
    <?php endif; ?>
    <div class="fdb-overview__meta-item">
      <span class="fdb-overview__meta-label">PHP</span>
      <span class="fdb-overview__meta-value"><?= htmlspecialchars($props['php_version'] ?? 'N/A') ?></span>
    </div>
  </div>

  <?php if (!empty($query)): ?>
    <details class="fdb-details" open>
      <summary class="fdb-details__summary fdb-overview__section-title">Query Parameters <span class="fdb-overview__count"><?= count($query) ?></span></summary>
      <div class="fdb-overview__props">
        <?php foreach ($query as $k => $v): ?>
          <div class="fdb-overview__prop">
            <span class="fdb-overview__prop-key"><?= htmlspecialchars((string) $k) ?></span>
            <span class="fdb-overview__prop-value"><?= fdb_ov_render_value($v) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if (!empty($body)): ?>
    <details class="fdb-details" open>
      <summary class="fdb-details__summary fdb-overview__section-title">Request Body <span class="fdb-overview__count"><?= is_array($body) ? count($body) : 1 ?></span></summary>
      <div class="fdb-overview__props">
        <?php if (is_array($body)): ?>
          <?php foreach ($body as $k => $v): ?>
            <div class="fdb-overview__prop">
              <span class="fdb-overview__prop-key"><?= htmlspecialchars((string) $k) ?></span>
              <span class="fdb-overview__prop-value"><?= fdb_ov_render_value($v) ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="fdb-overview__prop-value"><?= fdb_ov_render_leaf($body) ?></div>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if (!empty($headers)): ?>
    <details class="fdb-details">
      <summary class="fdb-details__summary fdb-overview__section-title">Headers <span class="fdb-overview__count"><?= count($headers) ?></span></summary>
      <div class="fdb-overview__props">
        <?php foreach ($headers as $k => $v): ?>
          <div class="fdb-overview__prop">
            <span class="fdb-overview__prop-key"><?= htmlspecialchars((string) $k) ?></span>
            <span class="fdb-overview__prop-value"><?= htmlspecialchars((string) $v) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if (!empty($cookies)): ?>
    <details class="fdb-details">
      <summary class="fdb-details__summary fdb-overview__section-title">Cookies <span class="fdb-overview__count"><?= count($cookies) ?></span></summary>
      <div class="fdb-overview__props">
        <?php foreach ($cookies as $k => $v): ?>
          <div class="fdb-overview__prop">
            <span class="fdb-overview__prop-key"><?= htmlspecialchars((string) $k) ?></span>
            <span class="fdb-overview__prop-value"><?= htmlspecialchars((string) $v) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if (!empty($files)): ?>
    <details class="fdb-details">
      <summary class="fdb-details__summary fdb-overview__section-title">Files <span class="fdb-overview__count"><?= is_array($files) ? count($files) : 1 ?></span></summary>
      <div class="fdb-overview__props">
        <?php if (is_array($files)): ?>
          <?php foreach ($files as $k => $v): ?>
            <div class="fdb-overview__prop">
              <span class="fdb-overview__prop-key"><?= htmlspecialchars((string) $k) ?></span>
              <span class="fdb-overview__prop-value"><?= fdb_ov_render_value($v) ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="fdb-overview__prop-value"><?= fdb_ov_render_leaf($files) ?></div>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>
</div>
