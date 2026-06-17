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
 */

$type = $props['type'] ?? 'text';
$name = $props['name'] ?? '';
$id = $props['id'] ?? $name;
$value = $props['value'] ?? '';
$placeholder = $props['placeholder'] ?? '';
$label = $props['label'] ?? '';
$required = $props['required'] ?? false;
$error = $props['error'] ?? '';
$class = $props['class'] ?? '';
?>
<div class="<?= $class ?>">
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($id) ?>" class="block text-sm font-medium text-gray-700 mb-1">
            <?= htmlspecialchars($label) ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    <input type="<?= htmlspecialchars($type) ?>"
           name="<?= htmlspecialchars($name) ?>"
           id="<?= htmlspecialchars($id) ?>"
           value="<?= htmlspecialchars($value) ?>"
           placeholder="<?= htmlspecialchars($placeholder) ?>"
           <?= $required ? 'required' : '' ?>
           class="w-full px-3 py-2 border <?= $error ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-200 focus:ring-blue-500 focus:border-blue-500' ?> rounded-lg focus:outline-none focus:ring-1 text-sm">
    <?php if ($error): ?>
        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>
