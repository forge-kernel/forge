<?php

/**
 * @var array $props
 * @var string $variant
 * @var string $padding
 * @var string $title
 * @var string $content
 * @var string $children
 * @var string $class
 */

$variant = $props['variant'] ?? 'default';
$padding = $props['padding'] ?? 'md';
$title = $props['title'] ?? '';
$content = $props['content'] ?? '';
$children = $props['children'] ?? '';
$class = $props['class'] ?? '';

$baseClasses = 'bg-white rounded-xl shadow-sm border border-gray-200';

$variantClasses = [
    'default' => '',
    'elevated' => 'shadow-lg',
    'outlined' => '',
    'flat' => 'shadow-none border-0',
];

$paddingClasses = [
    'none' => '',
    'sm' => 'p-3',
    'md' => 'p-6',
    'lg' => 'p-6',
    'xl' => 'p-8',
];

$variantClass = $variantClasses[$variant] ?? '';
$paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];
?>
<div class="<?= $baseClasses ?> <?= $variantClass ?> <?= $paddingClass ?> <?= $class ?>">
    <?php if (isset($slots['image'])): ?>
        <div class="mb-4">
            <?= $slots['image'] ?>
        </div>
    <?php endif; ?>
    <?php if (isset($slots['header']) || $title): ?>
        <div class="border-b border-gray-200 pb-4 mb-4">
            <?php if (isset($slots['header'])): ?>
                <?= $slots['header'] ?>
            <?php elseif ($title): ?>
                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($title) ?></h3>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="text-gray-700">
        <?= $content ?: ($slots['default'] ?? $children ?? '') ?>
    </div>
    <?php if (isset($slots['footer'])): ?>
        <div class="border-t border-gray-200 pt-4 mt-4">
            <?= $slots['footer'] ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($props['actions'] ?? [])): ?>
        <div class="mt-4 flex gap-2">
            <?php foreach ($props['actions'] as $action): ?>
                <a href="<?= htmlspecialchars($action['url'] ?? '#') ?>"
                  class="px-4 py-2 <?= ($action['type'] ?? 'secondary') === 'primary' ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> rounded-lg text-sm font-medium transition-colors">
                  <?= htmlspecialchars($action['label'] ?? '') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
