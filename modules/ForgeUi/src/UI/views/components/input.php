<?php

/**
 * @var array $props
 * @var string $type
 * @var string $name
 * @var string $id
 * @var string $value
 * @var string $placeholder
 * @var string $label
 * @var bool $required
 * @var string $error
 * @var bool $disabled
 * @var bool $readonly
 * @var string $size
 * @var string $variant
 * @var string $class
 */

$type = $props['type'] ?? 'text';
$name = $props['name'] ?? '';
$id = $props['id'] ?? ($name ? 'input-' . $name : 'input-' . uniqid());
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$label = $props['label'] ?? '';
$required = $props['required'] ?? false;
$error = $props['error'] ?? '';
$disabled = $props['disabled'] ?? false;
$readonly = $props['readonly'] ?? false;
$size = $props['size'] ?? 'md';
$variant = $error ? 'error' : ($props['variant'] ?? 'default');
$class = $props['class'] ?? '';

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-4 py-2.5 text-base',
];

$variantClasses = [
    'default' => 'border-gray-300 focus:border-transparent focus:ring-gray-900',
    'error' => 'border-red-300 focus:border-transparent focus:ring-red-500',
    'success' => 'border-green-300 focus:border-transparent focus:ring-green-500',
];

$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
$variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
$hasPrefix = isset($slots['prefix']) || isset($slots['icon']);
$hasSuffix = isset($slots['suffix']) || isset($slots['iconAfter']);
?>
<div class="<?= $class ?>">
    <?php if ($label || isset($slots['label'])): ?>
        <label for="<?= htmlspecialchars($id) ?>" class="block text-sm font-medium text-gray-700 mb-2">
            <?= $label ? htmlspecialchars($label) : ($slots['label'] ?? '') ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    <div class="relative">
        <?php if (isset($slots['prefix'])): ?>
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['prefix'] ?>
            </span>
        <?php endif; ?>
        <?php if (isset($slots['icon'])): ?>
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['icon'] ?>
            </span>
        <?php endif; ?>
        <input
            type="<?= htmlspecialchars($type) ?>"
            id="<?= htmlspecialchars($id) ?>"
            name="<?= htmlspecialchars($name) ?>"
            value="<?= htmlspecialchars($value) ?>"
            placeholder="<?= htmlspecialchars($placeholder) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $disabled ? 'disabled' : '' ?>
            <?= $readonly ? 'readonly' : '' ?>
            class="w-full border rounded-lg focus:outline-none focus:ring-2 transition-colors <?= $sizeClass ?> <?= $variantClass ?> <?= $hasPrefix ? 'pl-10' : '' ?> <?= $hasSuffix ? 'pr-10' : '' ?>"
        />
        <?php if (isset($slots['suffix'])): ?>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['suffix'] ?>
            </span>
        <?php endif; ?>
        <?php if (isset($slots['iconAfter'])): ?>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <?= $slots['iconAfter'] ?>
            </span>
        <?php endif; ?>
    </div>
    <?php if (isset($slots['helper']) && !isset($slots['error']) && !$error): ?>
        <p class="mt-1 text-sm text-gray-500">
            <?= $slots['helper'] ?>
        </p>
    <?php endif; ?>
    <?php if (isset($slots['error']) || $error): ?>
        <p class="mt-1 text-sm text-red-600">
            <?= $error ? htmlspecialchars($error) : ($slots['error'] ?? '') ?>
        </p>
    <?php endif; ?>
</div>
