<?php

/**
 * @var array $props
 * @var string $variant
 * @var bool $striped
 * @var bool $hoverable
 * @var bool $bordered
 * @var bool $compact
 * @var string $class
 */

$variant = $props['variant'] ?? 'default';
$striped = $props['striped'] ?? false;
$hoverable = $props['hoverable'] ?? false;
$bordered = $props['bordered'] ?? false;
$compact = $props['compact'] ?? false;
$class = $props['class'] ?? '';

$baseClasses = 'min-w-full divide-y divide-gray-200';
if ($bordered) {
    $baseClasses .= ' border border-gray-200';
}
if ($striped) {
    $baseClasses .= ' fw-table-striped';
}
if ($hoverable) {
    $baseClasses .= ' fw-table-hoverable';
}
if ($compact) {
    $baseClasses .= ' fw-table-compact';
}
?>
<div class="overflow-x-auto">
    <table class="<?= $baseClasses ?> <?= $class ?>">
        <?php if (isset($slots['header'])): ?>
            <thead class="bg-gray-50">
                <?= $slots['header'] ?>
            </thead>
        <?php endif; ?>
        <?php if (isset($slots['body'])): ?>
            <tbody class="bg-white divide-y divide-gray-200">
                <?= $slots['body'] ?>
            </tbody>
        <?php elseif (isset($slots['empty'])): ?>
            <tbody>
                <tr>
                    <td colspan="100%" class="px-6 py-4 text-center text-sm text-gray-500">
                        <?= $slots['empty'] ?>
                    </td>
                </tr>
            </tbody>
        <?php endif; ?>
        <?php if (isset($slots['footer'])): ?>
            <tfoot class="bg-gray-50">
                <?= $slots['footer'] ?>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
