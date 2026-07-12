<?php
/**
 * @var array $data
 * @var array $tabs
 */

$memory = $data['memory'] ?? [];
$time = $data['time'] ?? 'N/A';
$phpVersion = $data['php_version'] ?? 'N/A';

if (!function_exists('fdb_count')) {
    function fdb_count(array $data, string $key): ?int
    {
        $items = $data[$key] ?? [];
        return is_array($items) && !empty($items) ? count($items) : null;
    }
}

if (!function_exists('fdb_render_tree')) {
    function fdb_render_tree(array $data, int $depth = 0): string
    {
        $html = '<div class="fdb-state__sublist">';
        foreach ($data as $key => $value) {
            $html .= '<div class="fdb-state__subnode">';
            $html .= '<strong>' . htmlspecialchars((string) $key) . ':</strong> ';
            if (is_array($value)) {
                $html .= '<span class="fdb-state__array-indicator">Array (' . count($value) . ' items)</span>';
                $html .= fdb_render_tree($value, $depth + 1);
            } elseif (is_object($value)) {
                $html .= 'Object (' . get_class($value) . ')';
            } elseif (is_null($value)) {
                $html .= 'null';
            } else {
                $html .= htmlspecialchars((string) $value);
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
?>
<div class="fdb-bar">
  <div class="fdb-bar__brand">
    <svg class="fdb-bar__brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.5 3.798v5.02a3 3 0 0 1-.879 2.121l-2.377 2.377a9.845 9.845 0 0 1 5.091 1.013 8.315 8.315 0 0 0 5.713.636l.285-.071-3.954-3.955a3 3 0 0 1-.879-2.121v-5.02a23.614 23.614 0 0 0-3 0Zm4.5.138a.75.75 0 0 0 .093-1.495A24.837 24.837 0 0 0 12 2.25a25.048 25.048 0 0 0-3.093.191A.75.75 0 0 0 9 3.936v4.882a1.5 1.5 0 0 1-.44 1.06l-6.293 6.294c-1.62 1.621-.903 4.475 1.471 4.88 2.686.46 5.447.698 8.262.698 2.816 0 5.576-.239 8.262-.697 2.373-.406 3.092-3.26 1.47-4.881L15.44 9.879A1.5 1.5 0 0 1 15 8.818V3.936Z" clip-rule="evenodd" /></svg>
    <span class="fdb-bar__brand-text">Forge</span>
    <span class="fdb-bar__brand-chevron"></span>
  </div>
  <nav class="fdb-bar__tabs">
    <?php foreach ($tabs as $tab):
      $count = fdb_count($data, $tab['data_key']);
    ?>
      <button class="fdb-tab" data-tab="<?= $tab['name'] ?>" type="button">
        <?= $tab['label'] ?>
        <?php if ($count !== null): ?>
          <span class="fdb-tab__count"><?= $count ?></span>
        <?php endif; ?>
      </button>
    <?php endforeach; ?>
  </nav>
  <div class="fdb-bar__metrics">
    <span class="fdb-metric">
      <svg class="fdb-metric__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
      <span class="fdb-metric__value" id="fdb-metric-time"><?= $time ?></span>
    </span>
    <span class="fdb-metric">
      <svg class="fdb-metric__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
      <span class="fdb-metric__value" id="fdb-metric-memory"><?= $memory['current'] ?? 'N/A' ?></span>
    </span>
    <span class="fdb-metric">
      <svg class="fdb-metric__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
      <span class="fdb-metric__value" id="fdb-metric-php"><?= $phpVersion ?></span>
    </span>
  </div>
</div>
<div class="fdb-panels">
  <?php foreach ($tabs as $tab): ?>
    <div class="fdb-panel" id="fdb-panel-<?= $tab['name'] ?>">
      <div class="fdb-panel__content">
        <?= component($tab['component'], props: $data) ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
