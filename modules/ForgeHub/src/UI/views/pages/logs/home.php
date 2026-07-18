<?php

/** @var array $files */
/** @var array $entries */
/** @var string|null $selectedFile */
/** @var string|null $error */
/** @var array|null $stats */
/** @var array $modules */
/** @var array $filters */

//
?>

<div class="grid gap-6">
  <div class="bg-white rounded-lg shadow-sm p-4">
    <div class="flex flex-wrap gap-2">
      <?php foreach ($files as $file): ?>
                                                        <a href="?file=<?= rawurlencode($file->getFilename()) ?>"
                                                          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= ($_GET['file'] ?? '') === $file->getFilename() ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                                                          <?= htmlspecialchars($file->getFilename()) ?>
                                                        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (!empty($selectedFile)): ?>
                                                    <?php if ($stats): ?>
                                                                                             <?= component("ForgeHub:logs/stats", ['stats' => $stats, 'selectedFile' => $selectedFile, 'filters' => $filters]) ?>
                                                    <?php endif; ?>

                                                    <?= component('ForgeHub:logs/entries', ['entries' => $entries, 'selectedFile' => $selectedFile, 'error' => $error, 'filters' => $filters, 'modules' => $modules]) ?>
  <?php else: ?>
                                                    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                                                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                        </path>
                                                      </svg>
                                                      <h3 class="mt-2 text-sm font-medium text-gray-900">No log file selected</h3>
                                                      <p class="mt-1 text-sm text-gray-500">Please select a log file above to view entries.</p>
                                                    </div>
  <?php endif; ?>
</div>
