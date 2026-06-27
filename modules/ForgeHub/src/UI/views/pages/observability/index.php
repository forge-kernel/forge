<?php
use App\Modules\ForgeComponents\Definitions\Admin\StatCardDefinition;
use App\Modules\ForgeComponents\HtmlString;
use App\Modules\ForgeHub\Layouts\AdminLayout;

/** @var string $title */
/** @var array<string, mixed> $stats */
/** @var array<int, array<string, mixed>> $recentTraces */
/** @var string $activeItem */
/** @var array<int, array<string, mixed>> $breadcrumbs */
/** @var object|null $user */

$adminLayout = AdminLayout::build($activeItem, $breadcrumbs, $user);

$layoutProps = $adminLayout['layoutProps'];
$layoutSlots = $adminLayout['layoutSlots'];
?>
<div class="fc-admin-stack">
    <div>
        <h1 class="fc-admin-page-title">Observability</h1>
        <p class="fc-admin-text-muted">Request tracing, performance metrics, and slow query analysis.</p>
    </div>

    <?= component(name: 'ForgeComponents:admin/stats', props: [
        'columns' => 4,
        'stats' => [
            new StatCardDefinition(
                label: 'Avg Response',
                value: number_format($stats['avg_duration'], 2) . 'ms',
                icon: 'clock',
                variant: 'default',
            ),
            new StatCardDefinition(
                label: 'Requests (24h)',
                value: number_format($stats['total_requests']),
                icon: 'chart-bar',
                variant: 'default',
            ),
            new StatCardDefinition(
                label: 'Errors',
                value: number_format($stats['error_count']),
                icon: 'exclamation-triangle',
                variant: $stats['error_count'] > 0 ? 'warning' : 'default',
            ),
            new StatCardDefinition(
                label: 'Slow Queries',
                value: number_format($stats['slow_query_count']),
                icon: 'database',
                variant: $stats['slow_query_count'] > 0 ? 'warning' : 'default',
            ),
        ],
    ]) ?>

    <div class="fc-admin-grid fc-admin-grid--2">
        <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Recent Slow / Error Traces'], slots: [
            'default' => component(name: 'ForgeComponents:admin/table', props: [
                'columns' => [
                    ['key' => 'method', 'label' => 'Method'],
                    ['key' => 'path', 'label' => 'Path'],
                    ['key' => 'duration', 'label' => 'Duration'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'queries', 'label' => 'Queries'],
                    ['key' => 'time', 'label' => 'Time'],
                ],
                'rows' => array_map(fn($t) => [
                    'method' => $t['method'],
                    'path' => $t['path'],
                    'duration' => number_format($t['duration_ms'], 2) . 'ms',
                    'status' => new HtmlString(component(name: 'ForgeComponents:badge', props: [
                        'type' => $t['status'] === 'error' ? 'error' : ($t['status'] === 'warning' ? 'warning' : 'default'),
                        'text' => ucfirst($t['status']),
                    ])),
                    'queries' => $t['query_count'] . ($t['slow_query_count'] > 0 ? ' (' . $t['slow_query_count'] . ' slow)' : ''),
                    'time' => $t['created_at'],
                ], $recentTraces),
                'emptyMessage' => 'No slow or error traces found yet.',
            ]),
        ]) ?>

        <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Sampling Overview'], slots: [
            'default' => component(name: 'ForgeComponents:admin/table', props: [
                'columns' => [
                    ['key' => 'label', 'label' => 'Metric'],
                    ['key' => 'value', 'label' => 'Value'],
                ],
                'rows' => [
                    ['label' => 'Sampled Traces (24h)', 'value' => number_format($stats['sampled_traces'])],
                    ['label' => 'Unique Paths', 'value' => number_format($stats['unique_paths'])],
                    ['label' => 'Total Queries (24h)', 'value' => number_format($stats['total_queries'])],
                    ['label' => 'Strategy', 'value' => 'Adaptive'],
                ],
            ]),
        ]) ?>
    </div>

    <div class="fc-admin-card">
        <div class="fc-admin-card__header">
            <h3 class="fc-admin-card-title">Explore</h3>
        </div>
        <div class="fc-admin-card__body">
            <div class="fc-admin-grid fc-admin-grid--3">
                <a href="/hub/observability/traces" class="fc-btn fc-btn--secondary fc-btn--block">View All Traces</a>
                <a href="/hub/observability/slow-queries" class="fc-btn fc-btn--secondary fc-btn--block">Slow Queries</a>
                <button id="refreshStatsBtn" class="fc-btn fc-btn--secondary fc-btn--block">Refresh Stats</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('refreshStatsBtn')?.addEventListener('click', async function () {
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = 'Loading...';
        try {
            const response = await fetch('/hub/observability/api/stats?hours=24');
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (error) {
            alert('Failed to refresh stats');
        } finally {
            this.disabled = false;
            this.innerHTML = originalText;
        }
    });
</script>
