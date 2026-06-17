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

  <div class="bg-white rounded-lg shadow-sm p-6">
    <form method="get" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" name="date" id="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="search" name="search" id="search" placeholder="Search messages..."
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
      </div>
      <input type="hidden" name="file" value="<?= htmlspecialchars($_GET['file'] ?? '') ?>">
      <div>
        <?= component(name: 'ForgeHub:button', props: ['type' => 'submit', 'variant' => 'primary', 'children' => 'Filter']) ?>
      </div>
    </form>
  </div>

  <?php if (!empty($selectedFile)): ?>
        <?php if (!empty($error)): ?>
              <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <div>
                    <h3 class="text-sm font-medium text-red-800">Error reading log file</h3>
                    <p class="text-sm text-red-700 mt-1"><?= htmlspecialchars($error) ?></p>
                  </div>
                </div>
              </div>
        <?php else: ?>
              <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                  <h2 class="text-lg font-medium text-gray-800">Log Entries: <?= htmlspecialchars($selectedFile) ?></h2>
                  <p class="text-sm text-gray-500 mt-1">Showing <?= count($entries) ?> entries</p>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <?php if (empty($entries)): ?>
                            <tr>
                              <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">
                                No log entries found
                              </td>
                            </tr>
                      <?php else: ?>
                            <?php foreach ($entries as $entry): ?>
                                  <?php
                                  $levelColors = [
                                      'ERROR' => 'bg-red-100 text-red-800',
                                      'WARNING' => 'bg-yellow-100 text-yellow-800',
                                      'INFO' => 'bg-blue-100 text-blue-800',
                                      'DEBUG' => 'bg-gray-100 text-gray-800',
                                  ];
                                  $levelColor = $levelColors[strtoupper($entry->level)] ?? 'bg-gray-100 text-gray-800';
                                  ?>
                                  <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                      <?= htmlspecialchars($entry->date->format('Y-m-d H:i:s')) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                      <?= component(name: 'ForgeHub:badge', props: ['text' => $entry->level, 'type' => strtolower($entry->level) === 'error' ? 'error' : (strtolower($entry->level) === 'warning' ? 'warning' : 'info')]) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                      <?php
                                      $rawMessage = $entry->message;
                                      $mainMessage = $rawMessage;
                                      $stackTrace = null;
                                      $stackFormatted = null;

                                      $parts = explode(' | ', $rawMessage, 2);
                                      if (count($parts) === 2) {
                                          $mainMessage = $parts[0];
                                          $stackTrace = $parts[1];
                                          $stackFormatted = str_replace(' #', "\n#", $stackTrace);
                                      }
                                      ?>
                                      <p class="whitespace-pre-wrap break-words"><?= htmlspecialchars($mainMessage) ?></p>
                                      <?php if ($stackFormatted !== null && $stackFormatted !== ''): ?>
                                            <details class="mt-2">
                                              <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700 font-medium">
                                                Show stack trace
                                              </summary>
                                              <pre
                                                class="mt-2 p-3 bg-gray-50 rounded text-xs overflow-x-auto border border-gray-200 whitespace-pre-wrap break-words"><?= htmlspecialchars($stackFormatted) ?></pre>
                                            </details>
                                      <?php endif; ?>
                                      <?php if (!empty($entry->context)): ?>
                                            <details class="mt-2">
                                              <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700 font-medium">
                                                Show context
                                              </summary>
                                              <pre
                                                class="mt-2 p-3 bg-gray-50 rounded text-xs overflow-x-auto border border-gray-200"><?= htmlspecialchars(json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                            </details>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                            <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
        <?php endif; ?>
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
