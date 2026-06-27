<?php

use App\Modules\ForgeComponents\Definitions\Admin\StatCardDefinition;
use App\Modules\ForgeComponents\Definitions\Admin\TraceTimelineDefinition;
use App\Modules\ForgeComponents\HtmlString;
use App\Modules\ForgeHub\Layouts\AdminLayout;

/** @var string $title */
/** @var array<string, mixed>|null $trace */
/** @var string $activeItem */
/** @var array<int, array<string, mixed>> $breadcrumbs */
/** @var object|null $user */

$adminLayout = AdminLayout::build($activeItem, $breadcrumbs, $user);

$layoutProps = $adminLayout['layoutProps'];
$layoutSlots = $adminLayout['layoutSlots'];
?>
<div class="fc-admin-stack">
    <?php if ($trace === null): ?>
            <div class="fc-alert fc-alert--error">
                <div class="fc-alert__icon">
                    <?= component(name: 'ForgeComponents:admin/icon', props: new \App\Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: 'exclamation-triangle')) ?>
                </div>
                <div class="fc-alert__content">
                    <div class="fc-alert__title">Trace not found</div>
                    <p>The requested trace could not be found in the observability store.</p>
                </div>
            </div>
            <a href="/hub/observability/traces" class="fc-btn fc-btn--secondary">Back to Traces</a>
    <?php else: ?>
            <div>
                <h1 class="fc-admin-page-title">Trace Detail</h1>
                <p class="fc-admin-text-muted"><?= e($trace['name']) ?> &middot; <?= e($trace['id']) ?></p>
            </div>

            <?= component(name: 'ForgeComponents:admin/stats', props: [
                'columns' => 4,
                'stats' => [
                    new StatCardDefinition(label: 'Duration', value: number_format($trace['duration_ms'], 2) . 'ms', icon: 'clock'),
                    new StatCardDefinition(label: 'Status', value: ucfirst($trace['status']), icon: $trace['status'] === 'ok' ? 'check-circle' : 'exclamation-triangle'),
                    new StatCardDefinition(label: 'Queries', value: number_format($trace['query_count']), icon: 'database'),
                    new StatCardDefinition(label: 'Spans', value: number_format($trace['span_count']), icon: 'chart-bar'),
                ],
            ]) ?>

            <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Timeline'], slots: [
                'default' => empty($trace['spans'])
                    ? new HtmlString('<div class="fc-alert fc-alert--info"><div class="fc-alert__content">Span timeline is not available because this trace was not sampled. Slow/error traces and a random subset of fast traces are sampled based on the adaptive strategy.</div></div>')
                    : component(name: 'ForgeComponents:admin/trace-timeline', props: new TraceTimelineDefinition(
                        spans: $trace['spans'] ?? [],
                        totalDuration: (float) $trace['duration_ms'],
                    )),
            ]) ?>

            <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Span Details'], slots: [
                'default' => component(name: 'ForgeComponents:admin/table', props: [
                    'columns' => [
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'type', 'label' => 'Type'],
                        ['key' => 'duration', 'label' => 'Duration'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'metadata', 'label' => 'Metadata'],
                    ],
                    'rows' => array_map(function ($span) {
                        $metadata = [];
                        if (!empty($span['metadata']['sql'])) {
                            $metadata[] = new HtmlString('<code style="font-size: var(--fc-font-size-xs);">' . e(substr((string) $span['metadata']['sql'], 0, 120)) . '</code>');
                        }
                        if (!empty($span['metadata']['memory_used'])) {
                            $metadata[] = 'Memory: ' . number_format((int) $span['metadata']['memory_used'] / 1024, 2) . ' KB';
                        }

                        return [
                            'name' => e($span['name'] ?? 'unnamed'),
                            'type' => new HtmlString(component(name: 'ForgeComponents:badge', props: [
                                'type' => 'default',
                                'text' => $span['type'] ?? 'custom',
                            ])),
                            'duration' => number_format((float) ($span['duration_ms'] ?? 0), 3) . 'ms',
                            'status' => new HtmlString(component(name: 'ForgeComponents:badge', props: [
                                'type' => ($span['status'] ?? 'ok') === 'error' ? 'error' : 'success',
                                'text' => $span['status'] ?? 'ok',
                            ])),
                            'metadata' => $metadata === [] ? '-' : new HtmlString(implode('<br>', array_map(fn($m) => (string) $m, $metadata))),
                        ];
                    }, $trace['spans'] ?? []),
                ]),
            ]) ?>
    <?php endif; ?>
</div>
