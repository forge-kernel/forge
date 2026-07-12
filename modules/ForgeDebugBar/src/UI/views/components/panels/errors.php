<?php
/** @var array<string, mixed> $props */
$exceptions = $props['exceptions'] ?? [];

if (empty($exceptions)): ?>
  <div class="fdb-panel__empty">No exceptions caught.</div>
<?php return; endif; ?>
<div class="fdb-errors">
  <div class="fdb-errors__header">
    <span class="fdb-errors__count"><?= count($exceptions) ?> exception<?= count($exceptions) !== 1 ? 's' : '' ?> caught</span>
  </div>
  <?php foreach ($exceptions as $index => $ex): ?>
    <div class="fdb-errors__card">
      <div class="fdb-errors__card-header">
        <div class="fdb-errors__card-title">
          <span class="fdb-badge fdb-badge--error"><?= htmlspecialchars($ex['type'] ?? 'Exception') ?></span>
          <?php if (!empty($ex['code'])): ?>
            <span class="fdb-errors__code">Code <?= htmlspecialchars((string)($ex['code'])) ?></span>
          <?php endif; ?>
        </div>
        <span class="fdb-errors__index">#<?= $index + 1 ?></span>
      </div>
      <p class="fdb-errors__message"><?= htmlspecialchars($ex['message'] ?? '') ?></p>
      <div class="fdb-errors__location">
        <svg class="fdb-errors__location-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 1.5h4M8 1.5v13M3.5 4.5l9 0M3.5 4.5l0 8.5 4.5-3 4.5 3V4.5"/></svg>
        <span class="fdb-errors__file"><?= htmlspecialchars($ex['file'] ?? '') ?></span>
      </div>
      <?php if (!empty($ex['trace'] ?? '')): ?>
        <?php
          $traceLines = explode("\n", $ex['trace']);
          $traceLines = array_filter($traceLines, fn($l) => trim($l) !== '');
        ?>
        <details class="fdb-errors__trace">
          <summary class="fdb-errors__trace-summary">
            <svg class="fdb-errors__trace-chevron" viewBox="0 0 16 16" fill="currentColor"><path d="M4.427 6.427l3.396 3.396a.25.25 0 00.354 0l3.396-3.396A.25.25 0 0011.396 6H4.604a.25.25 0 00-.177.427z"/></svg>
            Stack Trace
            <span class="fdb-errors__trace-count"><?= count($traceLines) ?> frames</span>
          </summary>
          <div class="fdb-errors__trace-body">
            <?php foreach ($traceLines as $line): ?>
              <?php
                $line = trim($line);
                if (preg_match('/^(#\d+)\s+(.+)$/', $line, $m)) {
                  $frameNum = $m[1];
                  $frameRest = $m[2];
                  if (preg_match('/^([^(]+)\((.+)\)$/', $frameRest, $fm)) {
                    $call = $fm[1];
                    $loc = $fm[2];
                  } else {
                    $call = $frameRest;
                    $loc = '';
                  }
                } else {
                  $frameNum = '';
                  $call = $line;
                  $loc = '';
                }
              ?>
              <div class="fdb-errors__trace-line">
                <?php if ($frameNum): ?>
                  <span class="fdb-errors__trace-num"><?= htmlspecialchars($frameNum) ?></span>
                <?php endif; ?>
                <span class="fdb-errors__trace-call"><?= htmlspecialchars($call) ?></span>
                <?php if ($loc): ?>
                  <span class="fdb-errors__trace-location"><?= htmlspecialchars($loc) ?></span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
