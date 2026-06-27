<?php

/**
 * @var array $props
 * @var string $text
 * @var string $type
 */

$text = $props['text'] ?? '';
$type = $props['type'] ?? 'default';

$colors = [
  'success' => 'bg-green-100 text-green-800',
  'error' => 'bg-red-100 text-red-800',
  'warning' => 'bg-yellow-100 text-yellow-800',
  'info' => 'bg-blue-100 text-blue-800',
  'default' => 'bg-gray-100 text-gray-800',
];

$colorClass = $colors[$type] ?? $colors['default'];
?>
<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $colorClass ?>">
  <?= htmlspecialchars($text) ?>
</span>
