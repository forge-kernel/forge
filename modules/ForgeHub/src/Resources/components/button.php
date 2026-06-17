<?php

/**
 * @var array $props
 * @var string $type
 * @var string $variant
 * @var string $size
 * @var string $children
 */

$type = $props['type'] ?? 'button';
$variant = $props['variant'] ?? 'primary';
$size = $props['size'] ?? 'md';
$children = $props['children'] ?? '';
$class = $props['class'] ?? '';
$onclick = $props['onclick'] ?? '';

$variantClasses = [
    'primary' => 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-900',
    'secondary' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 focus:ring-blue-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-800/5 focus:ring-gray-500',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
?>
<button type="<?= htmlspecialchars($type) ?>"
        class="inline-flex items-center justify-center font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors <?= $variantClass ?> <?= $sizeClass ?> <?= $class ?>"
        <?= $onclick ? 'onclick="' . htmlspecialchars($onclick) . '"' : '' ?>>
    <?= $children ?>
</button>
