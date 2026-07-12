<?php
/** @var array<string, mixed> $props */
$views = $props['views'] ?? [];

if (empty($views)): ?>
  <div class="fdb-panel__empty">No templates rendered.</div>
<?php return; endif;

$total = count($views);

if (!function_exists('fdb_tpl_render_value')) {
  function fdb_tpl_render_value(mixed $value, int $depth = 0): string
  {
    if (is_array($value)) {
      if (empty($value)) {
        return '<span class="fdb-templates__empty">empty array</span>';
      }
      $html = '<div class="fdb-templates__tree">';
      foreach ($value as $k => $v) {
        $html .= '<div class="fdb-templates__node">';
        $html .= '<span class="fdb-templates__node-key">' . htmlspecialchars((string) $k) . ':</span> ';
        if (is_array($v)) {
          $html .= '<span class="fdb-templates__type">array(' . count($v) . ')</span>';
          $html .= fdb_tpl_render_value($v, $depth + 1);
        } else {
          $html .= fdb_tpl_render_leaf($v);
        }
        $html .= '</div>';
      }
      $html .= '</div>';
      return $html;
    }
    return fdb_tpl_render_leaf($value);
  }
}

if (!function_exists('fdb_tpl_render_leaf')) {
  function fdb_tpl_render_leaf(mixed $value): string
  {
    if ($value === null) {
      return '<span class="fdb-templates__null">null</span>';
    }
    if (is_bool($value)) {
      return '<span class="fdb-templates__bool">' . ($value ? 'true' : 'false') . '</span>';
    }
    if (is_int($value) || is_float($value)) {
      return '<span class="fdb-templates__num">' . htmlspecialchars((string) $value) . '</span>';
    }
    $str = (string) $value;
    if ($str === '') {
      return '<span class="fdb-templates__empty">""</span>';
    }
    if (strlen($str) > 120) {
      $short = htmlspecialchars(substr($str, 0, 120));
      return '<span class="fdb-templates__str" title="' . htmlspecialchars($str) . '">' . $short . '…</span>';
    }
    return '<span class="fdb-templates__str">' . htmlspecialchars($str) . '</span>';
  }
}
?>
<div class="fdb-templates">
  <div class="fdb-templates__header">
    <span class="fdb-templates__total"><?= $total ?> template<?= $total !== 1 ? 's' : '' ?> rendered</span>
  </div>
  <div class="fdb-templates__list">
    <?php foreach ($views as $i => $view):
      $path = $view['path'] ?? '';
      $viewData = $view['data'] ?? [];
      $hasData = !empty($viewData);

      $parts = explode('/', $path);
      $fileName = end($parts);
      $dirPath = count($parts) > 1 ? implode('/', array_slice($parts, 0, -1)) . '/' : '';
    ?>
      <div class="fdb-templates__entry">
        <div class="fdb-templates__row">
          <span class="fdb-templates__index"><?= $i + 1 ?></span>
          <div class="fdb-templates__info">
            <span class="fdb-templates__filename"><?= htmlspecialchars($fileName) ?></span>
            <?php if ($dirPath): ?>
              <span class="fdb-templates__dir"><?= htmlspecialchars($dirPath) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($hasData): ?>
            <details class="fdb-details">
              <summary class="fdb-details__summary fdb-templates__toggle"><?= count($viewData) ?> prop<?= count($viewData) !== 1 ? 's' : '' ?></summary>
              <div class="fdb-templates__data">
                <?php foreach ($viewData as $key => $value): ?>
                  <div class="fdb-templates__prop">
                    <div class="fdb-templates__prop-key"><?= htmlspecialchars((string) $key) ?></div>
                    <div class="fdb-templates__prop-value"><?= fdb_tpl_render_value($value) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
