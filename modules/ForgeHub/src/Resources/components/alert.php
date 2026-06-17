<?php

/**
 * @var array $props
 * @var string $type
 * @var string $title
 * @var string $children
 */

$type = $props['type'] ?? 'info';
$title = $props['title'] ?? '';
$children = $props['children'] ?? '';
$class = $props['class'] ?? '';

$typeStyles = [
    'warning' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'text' => 'text-red-800',
        'title' => 'text-red-800 font-semibold',
    ],
    'error' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'text' => 'text-red-800',
        'title' => 'text-red-800 font-semibold',
    ],
    'success' => [
        'bg' => 'bg-green-50',
        'border' => 'border-green-200',
        'text' => 'text-green-800',
        'title' => 'text-green-800 font-semibold',
    ],
    'info' => [
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-200',
        'text' => 'text-blue-800',
        'title' => 'text-blue-800 font-semibold',
    ],
];

$style = $typeStyles[$type] ?? $typeStyles['info'];
?>
<div class="rounded-md border-l-4 <?= $style['border'] ?> <?= $style['bg'] ?> p-4 <?= $class ?>">
    <?php if ($title): ?>
        <div class="flex">
            <div class="flex-shrink-0">
                <?php if ($type === 'warning' || $type === 'error'): ?>
                    <svg class="h-5 w-5 <?= $style['text'] ?>" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                <?php elseif ($type === 'success'): ?>
                    <svg class="h-5 w-5 <?= $style['text'] ?>" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                <?php else: ?>
                    <svg class="h-5 w-5 <?= $style['text'] ?>" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
            <div class="ml-3">
                <h3 class="<?= $style['title'] ?>"><?= htmlspecialchars($title) ?></h3>
                <div class="mt-2 text-sm <?= $style['text'] ?>">
                    <?= $children ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-sm <?= $style['text'] ?>">
            <?= $children ?>
        </div>
    <?php endif; ?>
</div>
