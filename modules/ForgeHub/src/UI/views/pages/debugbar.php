<div class="grid gap-6">
  <?php if (!$hasData): ?>
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                  </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900">No debug data available</h3>
                  <p class="mt-1 text-sm text-gray-500">Make a request to your application to see debug information here.</p>
                </div>
  <?php else: ?>
                <div class="grid gap-6">
                  <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-sm font-medium text-gray-600 mb-4">Overview</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                      <div>
                        <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">
                          <?= htmlspecialchars($debugData['overview']['php_version'] ?? 'N/A') ?>
                        </dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Execution Time</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                          <?= htmlspecialchars(number_format($debugData['overview']['execution_time'] ?? 0, 2)) ?> ms
                        </dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Memory Usage</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                          <?= htmlspecialchars(formatBytes((int) ($debugData['overview']['memory_current'] ?? 0))) ?>
                        </dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Peak Memory</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                          <?= htmlspecialchars(formatBytes((int) ($debugData['overview']['memory_peak'] ?? 0))) ?>
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <?php if (!empty($debugData['queries'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-sm font-medium text-gray-600 mb-4">Database Queries (<?= count($debugData['queries']) ?>)</h2>
                                  <div class="space-y-3">
                                    <?php foreach ($debugData['queries'] as $index => $query): ?>
                                                  <details class="border border-gray-200 rounded-lg p-4">
                                                    <summary class="cursor-pointer font-medium text-sm text-gray-900 hover:text-gray-700">
                                                      Query #<?= $index + 1 ?>
                                                      <?php if (isset($query['time'])): ?>
                                                                    <span class="text-gray-500 font-normal">(<?= htmlspecialchars(number_format($query['time'], 2)) ?>
                                                                      ms)</span>
                                                      <?php endif; ?>
                                                    </summary>
                                                    <div class="mt-3">
                                                      <pre
                                                        class="bg-gray-50 p-3 rounded text-xs overflow-x-auto border border-gray-200"><?= htmlspecialchars($query['sql'] ?? json_encode($query, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                    </div>
                                                  </details>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['timeline'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Timeline (<?= count($debugData['timeline']) ?>)</h2>
                                  <div class="space-y-2">
                                    <?php foreach ($debugData['timeline'] as $event): ?>
                                                  <div class="flex items-center gap-3 text-sm py-2 border-b border-gray-100 last:border-0">
                                                    <span class="text-gray-500 font-mono text-xs"><?= htmlspecialchars($event['time'] ?? '') ?></span>
                                                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($event['name'] ?? '') ?></span>
                                                    <span class="text-gray-400"><?= htmlspecialchars($event['type'] ?? '') ?></span>
                                                  </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['messages'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Messages (<?= count($debugData['messages']) ?>)</h2>
                                  <div class="space-y-2">
                                    <?php foreach ($debugData['messages'] as $message): ?>
                                                  <div class="flex items-start gap-3 p-3 bg-gray-50 rounded border border-gray-200">
                                                    <?php
                                                    $label = $message['label'] ?? 'info';
                                                    $labelColors = [
                                                        'error' => 'bg-red-100 text-red-800',
                                                        'warning' => 'bg-yellow-100 text-yellow-800',
                                                        'info' => 'bg-blue-100 text-blue-800',
                                                    ];
                                                    $labelColor = $labelColors[$label] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded <?= $labelColor ?>">
                                                      <?= strtoupper(htmlspecialchars($label)) ?>
                                                    </span>
                                                    <div class="flex-1 text-sm text-gray-900">
                                                      <?= htmlspecialchars($message['message'] ?? '') ?>
                                                    </div>
                                                  </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['exceptions'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Exceptions (<?= count($debugData['exceptions']) ?>)</h2>
                                  <div class="space-y-4">
                                    <?php foreach ($debugData['exceptions'] as $exception): ?>
                                                  <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                                    <div class="font-semibold text-red-900 mb-2">
                                                      <?= htmlspecialchars($exception['type'] ?? 'Exception') ?>:
                                                      <?= htmlspecialchars($exception['message'] ?? '') ?>
                                                    </div>
                                                    <?php if (isset($exception['file'])): ?>
                                                                  <div class="text-sm text-red-700 mb-2">
                                                                    File: <?= htmlspecialchars($exception['file']) ?>
                                                                    <?php if (isset($exception['line'])): ?>
                                                                                  :<?= htmlspecialchars($exception['line']) ?>
                                                                    <?php endif; ?>
                                                                  </div>
                                                    <?php endif; ?>
                                                    <?php if (isset($exception['trace'])): ?>
                                                                  <details class="mt-2">
                                                                    <summary class="text-sm text-red-700 cursor-pointer font-medium">Stack Trace</summary>
                                                                    <pre
                                                                      class="mt-2 p-3 bg-white rounded text-xs overflow-x-auto border border-red-200"><?= htmlspecialchars(is_array($exception['trace']) ? json_encode($exception['trace'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $exception['trace']) ?></pre>
                                                                  </details>
                                                    <?php endif; ?>
                                                  </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['views'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Views (<?= count($debugData['views']) ?>)</h2>
                                  <ul class="space-y-1">
                                    <?php foreach ($debugData['views'] as $view): ?>
                                      <li class="text-sm text-gray-900 font-mono py-1 break-all">
                                        <?= htmlspecialchars($view['path'] ?? $view) ?>
                                        <?php if (!empty($view['data'])): ?>
                                          <pre class="mt-1 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-200 overflow-x-auto"><?= htmlspecialchars(json_encode($view['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                        <?php endif; ?>
                                      </li>
                                    <?php endforeach; ?>
                                  </ul>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['route'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Route Information</h2>
                                  <dl class="grid grid-cols-1 gap-4">
                                    <?php foreach ($debugData['route'] as $key => $value): ?>
                                                  <?php if ($key === 'middleware' && is_array($value)): ?>
                                                                <div>
                                                                  <dt class="text-sm font-medium text-gray-500 mb-2">
                                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>
                                                                  </dt>
                                                                  <dd class="mt-1">
                                                                    <ul class="flex flex-wrap gap-2">
                                                                      <?php foreach ($value as $middleware): ?>
                                                                                    <li class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-mono border border-blue-200">
                                                                                      <?= htmlspecialchars((string) $middleware) ?>
                                                                                    </li>
                                                                      <?php endforeach; ?>
                                                                    </ul>
                                                                  </dd>
                                                                </div>
                                                  <?php elseif ($key === 'uri' || $key === 'handler' || $key === 'uses'): ?>
                                                                <div>
                                                                  <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>
                                                                  </dt>
                                                                  <dd class="mt-1 text-sm text-gray-900 font-mono break-all overflow-x-auto">
                                                                    <?= htmlspecialchars((string) $value) ?>
                                                                  </dd>
                                                                </div>
                                                  <?php else: ?>
                                                                <div>
                                                                  <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>
                                                                  </dt>
                                                                  <dd class="mt-1 text-sm text-gray-900 font-mono break-words overflow-x-auto">
                                                                    <?php if (is_array($value)): ?>
                                                                                  <pre
                                                                                    class="text-xs bg-gray-50 p-2 rounded border border-gray-200 overflow-x-auto whitespace-pre-wrap break-words"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                                    <?php else: ?>
                                                                                  <?= htmlspecialchars((string) $value) ?>
                                                                    <?php endif; ?>
                                                                  </dd>
                                                                </div>
                                                  <?php endif; ?>
                                    <?php endforeach; ?>
                                  </dl>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['session'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Session Data</h2>
                                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <pre
                                      class="text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($debugData['session'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                  </div>
                                </div>
                  <?php endif; ?>

                  <?php if (!empty($debugData['request'])): ?>
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                  <h2 class="text-lg font-medium text-gray-800 mb-4">Request Information</h2>
                                  <div class="space-y-4">
                                    <div>
                                      <h3 class="text-sm font-medium text-gray-700 mb-2">Basic Info</h3>
                                      <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div>
                                          <dt class="text-xs text-gray-500">Method</dt>
                                          <dd class="text-sm text-gray-900 font-mono">
                                            <?= htmlspecialchars($debugData['request']['method'] ?? 'N/A') ?>
                                          </dd>
                                        </div>
                                        <div>
                                          <dt class="text-xs text-gray-500">URL</dt>
                                          <dd class="text-sm text-gray-900 font-mono break-all">
                                            <?= htmlspecialchars($debugData['request']['url'] ?? 'N/A') ?>
                                          </dd>
                                        </div>
                                        <div>
                                          <dt class="text-xs text-gray-500">IP</dt>
                                          <dd class="text-sm text-gray-900 font-mono"><?= htmlspecialchars($debugData['request']['ip'] ?? 'N/A') ?>
                                          </dd>
                                        </div>
                                      </dl>
                                    </div>
                                    <?php if (!empty($debugData['request']['query'])): ?>
                                                  <div>
                                                    <h3 class="text-sm font-medium text-gray-700 mb-2">Query Parameters</h3>
                                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                      <pre
                                                        class="text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($debugData['request']['query'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                    </div>
                                                  </div>
                                    <?php endif; ?>
                                    <?php if (!empty($debugData['request']['body'])): ?>
                                                  <div>
                                                    <h3 class="text-sm font-medium text-gray-700 mb-2">Body</h3>
                                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                      <pre
                                                        class="text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($debugData['request']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                    </div>
                                                  </div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                  <?php endif; ?>
                </div>
              </div>
<?php endif; ?>
</div>
