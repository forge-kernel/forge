<?php
/** @var array<string, mixed> $props */
$views = $props['views'] ?? [];

if (empty($views)): ?>
  <div class="fdb-panel__empty">No templates rendered.</div>
<?php return; endif; ?>
<div class="fdb-templates">
  <?php foreach ($views as $view): ?>
    <div class="fdb-templates__entry">
      <strong class="fdb-mono"><?= htmlspecialchars($view['path'] ?? '') ?></strong>
      <?php $viewData = $view['data'] ?? []; if (!empty($viewData)): ?>
        <details class="fdb-details" style="margin-top: 4px;">
          <summary class="fdb-details__summary">View Data</summary>
          <pre class="fdb-pre"><?= htmlspecialchars(print_r($viewData, true)) ?></pre>
        </details>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
