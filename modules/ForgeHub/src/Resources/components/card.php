<?php

/**
 * @var array $props
 * @var string $title
 * @var string $content
 * @var array $actions
 */

$title = $props['title'] ?? '';
$content = $props['content'] ?? '';
$actions = $props['actions'] ?? [];
$class = $props['class'] ?? '';
?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 <?= htmlspecialchars($class) ?>">
  <?php if ($title): ?>
    <h3 class="text-lg font-semibold text-gray-900 mb-4"><?= htmlspecialchars($title) ?></h3>
  <?php endif; ?>

  <div class="text-gray-700">
    <?= $content ?>
  </div>

  <?php if (!empty($actions)): ?>
    <div class="mt-4 flex gap-2">
      <?php foreach ($actions as $action): ?>
        <a href="<?= htmlspecialchars($action['url'] ?? '#') ?>"
          class="px-4 py-2 <?= $action['type'] === 'primary' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> rounded-md text-sm font-medium transition-colors">
          <?= htmlspecialchars($action['label'] ?? '') ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
