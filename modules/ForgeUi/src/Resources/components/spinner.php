<?php

use App\Modules\ForgeUi\DesignTokens;

$variant = $variant ?? 'default';
$size = $size ?? 'md';

$baseClasses = DesignTokens::spinner($variant, $size);
$classes = class_merge($baseClasses, $class ?? '');
?>
<div class="fw-spinner-wrapper inline-flex items-center">
    <span class="<?= $classes ?>"></span>
    <?php if (isset($slots['default'])): ?>
        <span class="ml-2 text-sm text-gray-600">
            <?= $slots['default'] ?>
        </span>
    <?php endif; ?>
</div>
