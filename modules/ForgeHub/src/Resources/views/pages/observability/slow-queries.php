<?php

use App\Modules\ForgeComponents\Support\HtmlString;
use App\Modules\ForgeHub\Support\AdminLayout;

/** @var string $title */
/** @var array<int, array<string, mixed>> $queries */
/** @var float $minDuration */
/** @var string $activeItem */
/** @var array<int, array<string, mixed>> $breadcrumbs */
/** @var object|null $user */

$adminLayout = AdminLayout::build($activeItem, $breadcrumbs, $user);

$layoutProps = $adminLayout['layoutProps'];
$layoutSlots = $adminLayout['layoutSlots'];
?>
<div class="fc-admin-stack">
    <div>
        <h1 class="fc-admin-page-title">Slow Queries</h1>
        <p class="fc-admin-text-muted">Database queries aggregated from sampled traces over the last 7 days.</p>
    </div>

    <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Filter'], slots: [
        'default' => '<form method="get" action="/hub/observability/slow-queries" class="fc-split fc-split--center" style="gap: var(--fc-spacing-3);">
            <div style="flex: 1; max-width: 20rem;">
                <label class="fc-input-label">Minimum Duration (ms)</label>
                <input type="number" name="min_duration" value="' . e((string) $minDuration) . '" class="fc-input" placeholder="100">
            </div>
            <div style="display: flex; gap: var(--fc-spacing-3);">
                <button type="submit" class="fc-btn fc-btn--primary">Apply</button>
                <a href="/hub/observability/slow-queries" class="fc-btn fc-btn--ghost">Reset</a>
            </div>
        </form>',
    ]) ?>

    <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Slowest Queries'], slots: [
        'default' => component(name: 'ForgeComponents:admin/table', props: [
            'columns' => [
                ['key' => 'query', 'label' => 'Query'],
                ['key' => 'count', 'label' => 'Count'],
                ['key' => 'avg', 'label' => 'Avg (ms)'],
                ['key' => 'max', 'label' => 'Max (ms)'],
                ['key' => 'total', 'label' => 'Total (ms)'],
            ],
            'rows' => array_map(function ($q) {
                return [
                    'query' => new HtmlString('<code style="font-size: var(--fc-font-size-xs); white-space: pre-wrap; word-break: break-word;">' . e($q['normalized']) . '</code>'),
                    'count' => number_format($q['count']),
                    'avg' => number_format($q['avg_ms'], 2),
                    'max' => number_format($q['max_ms'], 2),
                    'total' => number_format($q['total_ms'], 2),
                ];
            }, $queries),
            'emptyMessage' => 'No slow queries found. Adjust the minimum duration or wait for sampled traces.',
        ]),
    ]) ?>
</div>
