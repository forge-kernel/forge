<?php
/**
 * @var $title string
 */
?>
<div class="p-6 bg-white rounded-xl border border-gray-200">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-semibold text-gray-900"><?= $title ?></h2>
    <?= slot("actions", "") ?>
  </div>
  <div class="space-y-3">
      <?= slot("content", "") ?>
  </div>
</div>
