<?php
/** @var array $stats */
/** @var string|null $selectedFile */
/** @var array $filters */

if (!$stats || !$selectedFile) {
    return;
}

$levelColors = [
    'ERROR'    => 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca;',
    'WARNING'  => 'background:#fffbeb;color:#d97706;border:1px solid #fde68a;',
    'INFO'     => 'background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;',
    'DEBUG'    => 'background:#f9fafb;color:#6b7280;border:1px solid #e5e7eb;',
    'CRITICAL' => 'background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;',
];

$activeLevel = $filters['level'] ?? null;
$activeModule = $filters['module'] ?? null;

$queryBase = '?file=' . rawurlencode($selectedFile);
if (!empty($filters['search'])) $queryBase .= '&search=' . rawurlencode($filters['search']);
if (!empty($filters['date'])) $queryBase .= '&date=' . rawurlencode($filters['date']);
?>

<div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;padding:0.75rem 1rem;border-bottom:1px solid #f0f0f0;background:#fafbfc;font-size:0.8rem;">
  <span style="color:#9ca3af;font-weight:500;"><?= number_format($stats['total']) ?> entries</span>

  <span style="width:1px;height:1rem;background:#e5e7eb;"></span>

  <?php foreach ($stats['byLevel'] as $level => $count): ?>
    <?php
    $style = $levelColors[$level] ?? $levelColors['DEBUG'];
    $isActive = $activeLevel === $level;
    if ($isActive) $style = 'background:#1f2937;color:#fff;border:1px solid #1f2937;';
    $href = $isActive
        ? '?file=' . rawurlencode($selectedFile) . (!empty($filters['search']) ? '&search=' . rawurlencode($filters['search']) : '') . (!empty($filters['date']) ? '&date=' . rawurlencode($filters['date']) : '') . (!empty($filters['module']) ? '&module=' . rawurlencode($filters['module']) : '')
        : $queryBase . '&level=' . rawurlencode($level) . (!empty($filters['module']) ? '&module=' . rawurlencode($filters['module']) : '');
    ?>
    <a href="<?= $href ?>" style="<?= $style ?>display:inline-flex;align-items:center;gap:0.35rem;padding:0.2rem 0.55rem;border-radius:4px;font-weight:500;text-decoration:none;transition:opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
      <?= htmlspecialchars($level) ?>
      <span style="opacity:0.7;"><?= $count ?></span>
    </a>
  <?php endforeach; ?>

  <?php if (!empty($stats['byModule'])): ?>
    <span style="width:1px;height:1rem;background:#e5e7eb;"></span>
    <?php foreach (array_slice($stats['byModule'], 0, 6, true) as $module => $count): ?>
      <?php
      $isActive = $activeModule === $module;
      $style = $isActive
          ? 'background:#1f2937;color:#fff;border:1px solid #1f2937;'
          : 'background:#eef2ff;color:#6366f1;border:1px solid #c7d2fe;';
      $href = $isActive
          ? '?file=' . rawurlencode($selectedFile) . (!empty($filters['search']) ? '&search=' . rawurlencode($filters['search']) : '') . (!empty($filters['date']) ? '&date=' . rawurlencode($filters['date']) : '') . (!empty($filters['level']) ? '&level=' . rawurlencode($filters['level']) : '')
          : $queryBase . '&module=' . rawurlencode($module) . (!empty($filters['level']) ? '&level=' . rawurlencode($filters['level']) : '');
      ?>
      <a href="<?= $href ?>" style="<?= $style ?>display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.5rem;border-radius:4px;font-weight:500;font-size:0.75rem;text-decoration:none;transition:opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
        <?= htmlspecialchars($module) ?>
        <span style="opacity:0.6;"><?= $count ?></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
