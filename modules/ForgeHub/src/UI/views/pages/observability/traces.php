<?php

use Modules\ForgeComponents\HtmlString;
use Modules\ForgeHub\Layouts\AdminLayout;

/** @var string $title */
/** @var array<string, mixed> $traces */
/** @var array<string, string> $filters */
/** @var string $activeItem */
/** @var array<int, array<string, mixed>> $breadcrumbs */
/** @var object|null $user */

$adminLayout = AdminLayout::build($activeItem, $breadcrumbs, $user);

$layoutProps = $adminLayout['layoutProps'];
$layoutSlots = $adminLayout['layoutSlots'];
?>
<div class="fc-admin-stack">
    <div>
        <h1 class="fc-admin-page-title">Traces</h1>
        <p class="fc-admin-text-muted">Browse captured request traces. Slow and error traces are always sampled.</p>
    </div>

    <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Filters'], slots: [
        'default' => '<form method="get" action="/hub/observability/traces" class="fc-admin-grid fc-admin-grid--3" style="align-items: end;">
            <div>
                <label class="fc-input-label">Path</label>
                <input type="text" name="path" value="' . e($filters['path'] ?? '') . '" class="fc-input" placeholder="/hub/...">
            </div>
            <div>
                <label class="fc-input-label">Status</label>
                <select name="status" class="fc-input fc-select">
                    <option value="">All</option>
                    <option value="ok" ' . (($filters['status'] ?? '') === 'ok' ? 'selected' : '') . '>OK</option>
                    <option value="warning" ' . (($filters['status'] ?? '') === 'warning' ? 'selected' : '') . '>Warning</option>
                    <option value="error" ' . (($filters['status'] ?? '') === 'error' ? 'selected' : '') . '>Error</option>
                </select>
            </div>
            <div>
                <label class="fc-input-label">Min Duration (ms)</label>
                <input type="number" name="min_duration" value="' . e($filters['min_duration'] ?? '') . '" class="fc-input" placeholder="e.g. 200">
            </div>
            <div style="grid-column: 1 / -1; display: flex; gap: var(--fc-spacing-3);">
                <button type="submit" class="fc-btn fc-btn--primary">Apply</button>
                <a href="/hub/observability/traces" class="fc-btn fc-btn--ghost">Clear</a>
            </div>
        </form>',
    ]) ?>

    <?= component(name: 'ForgeComponents:admin/data-card', props: ['title' => 'Traces'], slots: [
        'default' => component(name: 'ForgeComponents:admin/table', props: [
            'columns' => [
                ['key' => 'method', 'label' => 'Method'],
                ['key' => 'path', 'label' => 'Path'],
                ['key' => 'duration', 'label' => 'Duration'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'queries', 'label' => 'Queries'],
                ['key' => 'detail', 'label' => ''],
                ['key' => 'time', 'label' => 'Time'],
            ],
            'rows' => array_map(function ($t) {
                return [
                    'method' => e($t['method']),
                    'path' => e($t['path']),
                    'duration' => number_format($t['duration_ms'], 2) . 'ms',
                    'status' => new HtmlString(component(name: 'ForgeComponents:badge', props: [
                        'type' => $t['status'] === 'error' ? 'error' : ($t['status'] === 'warning' ? 'warning' : 'success'),
                        'text' => ucfirst($t['status']),
                    ])),
                    'queries' => $t['query_count'] . ($t['slow_query_count'] > 0 ? ' (' . $t['slow_query_count'] . ' slow)' : ''),
                    'detail' => $t['sampled']
                        ? new HtmlString('<a href="/hub/observability/traces/' . e($t['id']) . '" class="fc-link">View</a>')
                        : new HtmlString(component(name: 'ForgeComponents:badge', props: ['type' => 'default', 'text' => 'Summary'])),
                    'time' => e($t['created_at']),
                ];
            }, $traces['data'] ?? []),
            'emptyMessage' => 'No traces match the current filters.',
        ]),
    ]) ?>

    <?php if (($traces['last_page'] ?? 1) > 1): ?>
            <div class="fc-admin-card__body" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="fc-admin-text-muted">Page <?= (int) $traces['page'] ?> of <?= (int) $traces['last_page'] ?></span>
                <div style="display: flex; gap: var(--fc-spacing-3);">
                    <?php if ($traces['page'] > 1): ?>
                            <a href="?<?= e(http_build_query(array_merge($filters, ['page' => $traces['page'] - 1]))) ?>" class="fc-btn fc-btn--secondary">Previous</a>
                    <?php endif; ?>
                    <?php if ($traces['page'] < $traces['last_page']): ?>
                            <a href="?<?= e(http_build_query(array_merge($filters, ['page' => $traces['page'] + 1]))) ?>" class="fc-btn fc-btn--secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
    <?php endif; ?>
</div>
