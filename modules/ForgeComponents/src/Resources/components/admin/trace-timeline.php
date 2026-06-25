<?php

/** @var \App\Modules\ForgeComponents\Definitions\Admin\TraceTimelineDefinition $props */

$spans = $props->spans;
$totalDuration = $props->totalDuration;
$showLabels = $props->showLabels;

if (empty($spans) || $totalDuration <= 0) {
    return;
}

$startTimes = array_column($spans, 'start_time');
$traceStart = min($startTimes);
$traceDurationSeconds = $totalDuration / 1000;

$typeColors = [
    'db' => 'fc-trace-timeline__bar--db',
    'http' => 'fc-trace-timeline__bar--http',
    'view' => 'fc-trace-timeline__bar--view',
    'cache' => 'fc-trace-timeline__bar--cache',
    'metric' => 'fc-trace-timeline__bar--metric',
    'custom' => 'fc-trace-timeline__bar--custom',
];

$maxLabelWidth = 0;
if ($showLabels) {
    foreach ($spans as $span) {
        $len = strlen((string) ($span['name'] ?? ''));
        if ($len > $maxLabelWidth) {
            $maxLabelWidth = $len;
        }
    }
}
$labelWidth = $showLabels ? min(max($maxLabelWidth * 0.45 + 1, 8), 16) : 0;
?>
<div class="fc-trace-timeline">
    <div class="fc-trace-timeline__ruler">
        <span>0ms</span>
        <span><?= e(round($totalDuration / 4, 2)) ?>ms</span>
        <span><?= e(round($totalDuration / 2, 2)) ?>ms</span>
        <span><?= e(round($totalDuration * 0.75, 2)) ?>ms</span>
        <span><?= e(round($totalDuration, 2)) ?>ms</span>
    </div>

    <div class="fc-trace-timeline__rows" style="--fc-trace-label-width: <?= $labelWidth ?>rem;">
        <?php foreach ($spans as $span):
            $name = $span['name'] ?? 'unnamed';
            $type = $span['type'] ?? 'custom';
            $duration = (float) ($span['duration_ms'] ?? 0);
            $relativeStart = $traceDurationSeconds > 0
                ? (($span['start_time'] - $traceStart) / $traceDurationSeconds) * 100
                : 0;
            $width = $totalDuration > 0 ? ($duration / $totalDuration) * 100 : 0;
            $colorClass = $typeColors[$type] ?? $typeColors['custom'];
            $sql = $span['metadata']['sql'] ?? null;
            $status = $span['status'] ?? 'ok';
            $statusClass = $status === 'error' ? 'fc-trace-timeline__bar--error' : '';
        ?>
            <div class="fc-trace-timeline__row">
                <?php if ($showLabels): ?>
                    <div class="fc-trace-timeline__label" title="<?= e($name) ?>">
                        <?= e($name) ?>
                    </div>
                <?php endif; ?>
                <div class="fc-trace-timeline__track">
                    <div
                        class="fc-trace-timeline__bar <?= $colorClass ?> <?= $statusClass ?>"
                        style="left: <?= e((string) max(0, min($relativeStart, 100))) ?>%; width: <?= e((string) max(0.2, min($width, 100 - $relativeStart))) ?>%;"
                    >
                        <div class="fc-trace-timeline__tooltip">
                            <div class="fc-trace-timeline__tooltip-name"><?= e($name) ?></div>
                            <div class="fc-trace-timeline__tooltip-meta">
                                <span><?= e(ucfirst($type)) ?></span>
                                <span><?= e(round($duration, 3)) ?>ms</span>
                                <?php if ($status === 'error'): ?>
                                    <span class="fc-trace-timeline__tooltip-error">Error</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($sql !== null): ?>
                                <div class="fc-trace-timeline__tooltip-sql"><?= e((string) $sql) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
