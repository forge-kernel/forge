<?php

/**
 * @var array $props
 * @var string $variant
 * @var string $size
 * @var string $text
 * @var string $children
 * @var string $class
 */

$variant = $props['variant'] ?? 'default';
$size = $props['size'] ?? 'md';
$text = $props['text'] ?? '';
$children = $props['children'] ?? '';
$class = $props['class'] ?? '';

$colors = [
    'success' => 'bg-green-100 text-green-800',
    'error' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-blue-100 text-blue-800',
    'primary' => 'bg-gray-100 text-gray-800',
    'secondary' => 'bg-gray-100 text-gray-800',
    'default' => 'bg-gray-100 text-gray-800',
];

$sizeClasses = [
    'xs' => 'px-1.5 py-0.5 text-xs',
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-0.5 text-sm',
    'lg' => 'px-3 py-1 text-base',
];

$colorClass = $colors[$variant] ?? $colors['default'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
?>
<span class="inline-flex items-center font-semibold rounded-full <?= $colorClass ?> <?= $sizeClass ?> <?= $class ?>">
    <?php if (isset($slots['icon'])): ?>
        <span class="mr-1"><?= $slots['icon'] ?></span>
    <?php endif; ?>
    <?= htmlspecialchars($text ?: ($children ?: ($slots['default'] ?? ''))) ?>
    <?php if (isset($slots['iconAfter'])): ?>
        <span class="ml-1"><?= $slots['iconAfter'] ?></span>
    <?php endif; ?>
</span>
