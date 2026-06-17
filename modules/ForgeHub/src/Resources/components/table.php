<?php

/**
 * @var array $props
 * @var array $headers
 * @var array $rows
 */

$headers = $props['headers'] ?? [];
$rows = $props['rows'] ?? [];
$class = $props['class'] ?? '';
?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden <?= htmlspecialchars($class) ?>">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <?php if (!empty($headers)): ?>
        <thead class="bg-gray-50">
          <tr>
            <?php foreach ($headers as $header): ?>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <?= htmlspecialchars($header) ?>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
      <?php endif; ?>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($rows as $row): ?>
          <tr class="hover:bg-gray-50">
            <?php foreach ($row as $cell): ?>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <?= is_string($cell) ? htmlspecialchars($cell) : $cell ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
