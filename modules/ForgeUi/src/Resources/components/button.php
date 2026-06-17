<?php

/**
 * @var array $props
 * @var string $type
 * @var string $variant
 * @var string $size
 * @var bool $disabled
 * @var bool $loading
 * @var bool $fullWidth
 * @var string $children
 * @var string $class
 */

$type = $props['type'] ?? 'button';
$variant = $props['variant'] ?? 'primary';
$size = $props['size'] ?? 'md';
$disabled = $props['disabled'] ?? false;
$loading = $props['loading'] ?? false;
$fullWidth = $props['fullWidth'] ?? false;
$children = $props['children'] ?? ($slots['default'] ?? '');
$class = $props['class'] ?? '';

$variantClasses = [
    'primary' => 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-900',
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-900',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
    'info' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-800/5 focus:ring-gray-500',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
$stateClasses = '';
if ($disabled || $loading) {
    $stateClasses .= ' opacity-50 cursor-not-allowed';
}
if ($loading) {
    $stateClasses .= ' relative text-transparent';
}
if ($fullWidth) {
    $stateClasses .= ' w-full';
}
?>
<button type="<?= htmlspecialchars($type) ?>"
        <?= ($disabled || $loading) ? 'disabled' : '' ?>
        class="inline-flex items-center justify-center font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors <?= $variantClass ?> <?= $sizeClass ?> <?= $stateClasses ?> <?= $class ?>">
    <?php if ($loading): ?>
        <span class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
    <?php endif; ?>
    <?php if (isset($slots['icon'])): ?>
        <span class="mr-2"><?= $slots['icon'] ?></span>
    <?php endif; ?>
    <span class="<?= $loading ? 'invisible' : '' ?>">
        <?= $children ?>
    </span>
    <?php if (isset($slots['iconAfter'])): ?>
        <span class="ml-2"><?= $slots['iconAfter'] ?></span>
    <?php endif; ?>
</button>
