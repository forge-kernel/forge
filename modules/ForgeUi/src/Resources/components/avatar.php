<?php

use App\Modules\ForgeUi\DesignTokens;

$size = $size ?? 'md';
$shape = $shape ?? 'rounded';
$src = $src ?? null;
$alt = $alt ?? '';
$initials = $initials ?? '';
$status = $status ?? null;

$baseClasses = DesignTokens::avatar($size);
$shapeClasses = [
    'rounded' => ['rounded-md'],
    'circle' => ['rounded-full'],
    'square' => ['rounded-none'],
];
$shapeClass = $shapeClasses[$shape] ?? $shapeClasses['rounded'];

$classes = class_merge($baseClasses, $shapeClass, $class ?? '');
?>
<div class="relative inline-block">
    <?php if ($src): ?>
        <img src="<?= e($src) ?>" alt="<?= e($alt) ?>" class="<?= $classes ?>">
    <?php elseif ($initials): ?>
        <div class="<?= $classes ?>">
            <?= e($initials) ?>
        </div>
    <?php elseif (isset($slots['default'])): ?>
        <div class="<?= $classes ?>">
            <?= $slots['default'] ?>
        </div>
    <?php elseif (isset($slots['icon'])): ?>
        <div class="<?= $classes ?>">
            <?= $slots['icon'] ?>
        </div>
    <?php else: ?>
        <div class="<?= $classes ?>">
            <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>
    <?php endif; ?>
    <?php if ($status && isset($slots['badge'])): ?>
        <span class="absolute bottom-0 right-0 block <?= $status === 'online' ? 'bg-green-400' : ($status === 'away' ? 'bg-yellow-400' : 'bg-gray-400') ?> rounded-full ring-2 ring-white h-3 w-3"></span>
    <?php elseif (isset($slots['badge'])): ?>
        <span class="absolute -bottom-1 -right-1">
            <?= $slots['badge'] ?>
        </span>
    <?php endif; ?>
</div>
