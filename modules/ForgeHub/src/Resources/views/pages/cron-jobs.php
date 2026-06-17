<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Cron Jobs</h1>
      <p class="text-sm text-gray-500 mt-1">Manage scheduled tasks and commands</p>
    </div>
    <button id="createCronJobBtn"
      class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">
      Create Cron Job
    </button>
  </div>

  <div id="cronJobsList" class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <?php if (empty($cronJobs)): ?>
          <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No cron jobs</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new cron job.</p>
          </div>
    <?php else: ?>
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($cronJobs as $job): ?>
                    <tr>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($job['name'] ?? 'Untitled') ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">
                          <?= htmlspecialchars($job['schedule_readable'] ?? $job['cron_expression'] ?? 'N/A') ?>
                        </div>
                        <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($job['cron_expression'] ?? '* * * * *') ?>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                          <?php if (($job['command_type'] ?? 'forge') === 'script'): ?>
                                <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 rounded">Script</span>
                          <?php else: ?>
                                <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded">Forge</span>
                          <?php endif; ?>
                          <div class="text-sm text-gray-500 max-w-md truncate"
                            title="<?= htmlspecialchars($job['command'] ?? '') ?>">
                            <?= htmlspecialchars($job['command'] ?? '') ?>
                          </div>
                          <?php if ($job['has_output'] ?? false): ?>
                                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded"
                                  title="Has output">Output</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1">
                          <?php if ($job['enabled'] ?? true): ?>
                                <span
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 w-fit">Enabled</span>
                          <?php else: ?>
                                <span
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 w-fit">Disabled</span>
                          <?php endif; ?>
                          <?php if (isset($job['last_run'])): ?>
                                <span class="text-xs text-gray-500">Last run:
                                  <?= date('M j, Y H:i', strtotime($job['last_run'])) ?></span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                          <?php if ($job['has_output'] ?? false): ?>
                                <button onclick="viewOutput('<?= htmlspecialchars($job['id'] ?? '') ?>')"
                                  class="text-purple-600 hover:text-purple-900 p-1.5 rounded hover:bg-purple-50" title="View Output">
                                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                  </svg>
                                </button>
                          <?php endif; ?>
                          <button
                            onclick="runCronJob('<?= htmlspecialchars($job['id'] ?? '') ?>', '<?= htmlspecialchars($job['name'] ?? 'Untitled') ?>')"
                            class="text-green-600 hover:text-green-900 p-1.5 rounded hover:bg-green-50" title="Run Now">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                              </path>
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                          </button>
                          <button onclick="editCronJob('<?= htmlspecialchars($job['id'] ?? '') ?>')"
                            class="text-blue-600 hover:text-blue-900">Edit</button>
                          <button
                            onclick="deleteCronJob('<?= htmlspecialchars($job['id'] ?? '') ?>', '<?= htmlspecialchars($job['name'] ?? 'Untitled') ?>')"
                            class="text-red-600 hover:text-red-900">Delete</button>
                        </div>
                      </td>
                    </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
  </div>

  <div id="cronJobModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="modalBackdrop"></div>

    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Create Cron Job</h3>
          <button onclick="closeModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form id="cronJobForm" class="px-6 py-6 space-y-6">
          <input type="hidden" id="cronJobId" name="id" value="">

          <div>
            <label for="cronJobName" class="block text-sm font-medium text-gray-700 mb-1">
              Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="cronJobName" name="name" required
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
              placeholder="e.g., Backup Database">
          </div>

          <div>
            <label for="cronJobCommandType" class="block text-sm font-medium text-gray-700 mb-1">
              Command Type <span class="text-red-500">*</span>
            </label>
            <select id="cronJobCommandType" name="command_type" required
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
              onchange="toggleCommandType()">
              <option value="forge">Forge Command</option>
              <option value="script">PHP Script</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Choose whether to run a Forge command or a PHP script file</p>
          </div>

          <div>
            <label for="cronJobCommand" class="block text-sm font-medium text-gray-700 mb-1">
              <span id="commandLabel">Command</span> <span class="text-red-500">*</span>
            </label>
            <div id="forgeCommandContainer">
              <div class="flex items-center gap-2 mb-2">
                <label class="flex items-center cursor-pointer">
                  <input type="radio" name="commandInputMode" value="manual" id="commandModeManual" checked
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                    onchange="toggleCommandInputMode()">
                  <span class="ml-2 text-sm text-gray-700">Manual</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input type="radio" name="commandInputMode" value="select" id="commandModeSelect"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                    onchange="toggleCommandInputMode()">
                  <span class="ml-2 text-sm text-gray-700">Select from list</span>
                </label>
              </div>
              <div id="manualCommandInput">
                <textarea id="cronJobCommand" name="command" required rows="2"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono"
                  placeholder="backup:database"></textarea>
                <p class="mt-1 text-xs text-gray-500">Enter the Forge command (e.g., <code
                    class="bg-gray-100 px-1 rounded">backup:database</code> or <code
                    class="bg-gray-100 px-1 rounded">queue:work</code>)</p>
                <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                  <p class="font-medium mb-1">PHP Command Info:</p>
                  <p class="text-blue-700">
                    <?php if ($phpInfo['is_default'] ?? false): ?>
                          Using default PHP CLI command (<code class="bg-blue-100 px-1 rounded">php</code>).
                    <?php else: ?>
                          Using PHP at <code
                            class="bg-blue-100 px-1 rounded"><?= htmlspecialchars($phpInfo['path'] ?? 'php') ?></code>
                          <?php if (!empty($phpInfo['version'] ?? '')): ?>
                                (version <?= htmlspecialchars($phpInfo['version']) ?>)
                          <?php endif; ?>
                    <?php endif; ?>
                  </p>
                  <p class="text-blue-700 mt-1">
                    For a specific PHP version, use the exact path like <code
                      class="bg-blue-100 px-1 rounded">/usr/bin/php8.4</code> in your command.
                  </p>
                </div>
              </div>
              <div id="selectCommandInput" class="hidden">
                <select id="cronJobCommandSelect" name="command"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                  onchange="updateCommandFromSelect()">
                  <option value="">Select a command...</option>
                </select>
                <input type="hidden" id="cronJobCommandHidden" name="command" value="">
                <div id="commandDetails" class="mt-3 hidden">
                  <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p id="commandDescription" class="text-sm text-gray-700 mb-2"></p>
                    <div id="commandUsage" class="mb-2 hidden">
                      <p class="text-xs font-medium text-gray-600 mb-1">Usage:</p>
                      <code class="text-xs bg-white px-2 py-1 rounded border border-gray-300 block"
                        id="commandUsageText"></code>
                    </div>
                    <div id="commandArguments" class="mb-2 hidden">
                      <p class="text-xs font-medium text-gray-600 mb-1">Arguments:</p>
                      <div id="commandArgumentsList" class="space-y-1"></div>
                    </div>
                  </div>
                  <div id="commandArgumentsInput" class="mt-3 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Command Arguments <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" id="cronJobCommandArgs" name="command_args"
                      class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono"
                      placeholder="--name=value --flag">
                    <p class="mt-1 text-xs text-gray-500">Add arguments separated by spaces (e.g., <code
                        class="bg-gray-100 px-1 rounded">--name=John --verbose</code>)</p>
                  </div>
                </div>
              </div>
            </div>
            <div id="scriptCommandContainer" class="hidden">
              <input type="text" id="cronJobScript" name="command" required
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono"
                placeholder="app/scripts/backup.php">
              <p class="mt-1 text-xs text-gray-500">Enter the path to the PHP script file relative to project root
                (e.g., <code class="bg-gray-100 px-1 rounded">app/scripts/backup.php</code>)</p>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-gray-200 pt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Mode</label>
              <p class="text-xs text-gray-500">Choose between simple inputs or advanced cron expression</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="advancedMode" class="sr-only peer" onchange="toggleScheduleMode()">
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
              </div>
              <span class="ml-3 text-sm font-medium text-gray-700" id="modeLabel">Simple</span>
            </label>
          </div>

          <div id="simpleSchedule" class="space-y-3">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
              <div>
                <label for="scheduleSeconds" class="block text-xs font-medium text-gray-700 mb-1">Seconds</label>
                <input type="number" id="scheduleSeconds" name="schedule[seconds]" min="0" max="59" value="0"
                  class="w-full px-2 py-1.5 border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
                <p class="mt-0.5 text-xs text-gray-400">Note: Standard cron uses minute precision</p>
              </div>
              <div>
                <label for="scheduleMinutes" class="block text-xs font-medium text-gray-700 mb-1">Minutes</label>
                <input type="number" id="scheduleMinutes" name="schedule[minutes]" min="0" max="59" value="0"
                  class="w-full px-2 py-1.5 border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label for="scheduleHours" class="block text-xs font-medium text-gray-700 mb-1">Hours</label>
                <input type="number" id="scheduleHours" name="schedule[hours]" min="0" max="23" value="0"
                  class="w-full px-2 py-1.5 border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label for="scheduleDays" class="block text-xs font-medium text-gray-700 mb-1">Days</label>
                <input type="text" id="scheduleDays" name="schedule[days]" placeholder="*" value="*"
                  class="w-full px-2 py-1.5 border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                  pattern="(\*|\d{1,2})">
                <p class="mt-0.5 text-xs text-gray-400">1-31 or *</p>
              </div>
              <div>
                <label for="scheduleMonths" class="block text-xs font-medium text-gray-700 mb-1">Months</label>
                <input type="text" id="scheduleMonths" name="schedule[months]" placeholder="*" value="*"
                  class="w-full px-2 py-1.5 border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                  pattern="(\*|\d{1,2})">
                <p class="mt-0.5 text-xs text-gray-400">1-12 or *</p>
              </div>
            </div>
            <div id="schedulePreview" class="p-3 bg-gray-50 rounded-lg">
              <p class="text-xs font-medium text-gray-700 mb-1">Schedule Preview:</p>
              <p class="text-sm text-gray-600" id="previewText">-</p>
            </div>
          </div>

          <div id="advancedSchedule" class="hidden">
            <label for="cronExpression" class="block text-sm font-medium text-gray-700 mb-1">
              Cron Expression <span class="text-red-500">*</span>
            </label>
            <input type="text" id="cronExpression" name="schedule[expression]" placeholder="* * * * *"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono">
            <p class="mt-1 text-xs text-gray-500">Format: minute hour day month weekday (e.g., "0 2 * * *" for daily at
              2 AM)</p>
            <div class="mt-2 p-3 bg-gray-50 rounded-lg">
              <p class="text-xs font-medium text-gray-700 mb-1">Examples:</p>
              <ul class="text-xs text-gray-600 space-y-1">
                <li><code class="bg-white px-1 rounded">*/5 * * * *</code> - Every 5 minutes</li>
                <li><code class="bg-white px-1 rounded">0 * * * *</code> - Every hour</li>
                <li><code class="bg-white px-1 rounded">0 2 * * *</code> - Daily at 2 AM</li>
                <li><code class="bg-white px-1 rounded">0 0 1 * *</code> - Monthly on the 1st</li>
              </ul>
            </div>
          </div>

          <div class="space-y-4 pt-4 border-t border-gray-200">
            <div class="flex items-center">
              <label class="flex items-center cursor-pointer">
                <input type="checkbox" id="cronJobEnabled" name="enabled" checked
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Enabled</span>
              </label>
            </div>

          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Save
            </button>
          </div>
        </form>

        <div id="outputSection" class="hidden px-6 py-6">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">Command Output</label>
            <div class="flex items-center gap-2">
              <button type="button" onclick="refreshOutput()"
                class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                  </path>
                </svg>
                Refresh
              </button>
              <button type="button" onclick="clearOutput()"
                class="text-xs text-red-500 hover:text-red-700">Clear</button>
            </div>
          </div>
          <div
            class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-96 overflow-y-auto font-mono text-xs border border-gray-700">
            <pre id="outputContent" class="whitespace-pre-wrap break-words">Loading output...</pre>
          </div>
          <p class="mt-1 text-xs text-gray-500">Showing last 200 lines of output. Output is automatically captured when
            commands run.</p>
        </div>
      </div>
    </div>
  </div>

  <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="deleteModalBackdrop">
    </div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Delete Cron Job</h3>
          <button onclick="closeDeleteModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="px-6 py-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                  </path>
                </svg>
              </div>
            </div>
            <div class="flex-1">
              <h4 class="text-lg font-medium text-gray-900 mb-2" id="deleteJobName">Delete Cron Job</h4>
              <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete this cron job? This action cannot be
                undone and will permanently remove the job and its output logs.</p>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
          <button type="button" onclick="closeDeleteModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button type="button" onclick="confirmDelete()" id="confirmDeleteButton"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 transition-colors">
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="runModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="runModalBackdrop"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900" id="runModalTitle">Run Cron Job</h3>
          <button onclick="closeRunModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="px-6 py-6">
          <div class="mb-4">
            <p class="text-sm text-gray-600" id="runJobInfo">Executing command...</p>
          </div>
          <div
            class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-96 overflow-y-auto font-mono text-xs border border-gray-700">
            <pre id="runOutput" class="whitespace-pre-wrap break-words">Starting execution...</pre>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
              <span id="runStatus">Preparing...</span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="closeRunModal()" id="runCloseButton"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                Close
              </button>
              <button type="button" onclick="viewRunOutput()" id="viewOutputButton"
                class="hidden px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors"
                data-job-id="">
                View Full Output
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let editingJobId = null;

  function openModal() {
    const modal = document.getElementById('cronJobModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const firstInput = modal.querySelector('input[type="text"], textarea');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }
  }

  function closeModal() {
    const modal = document.getElementById('cronJobModal');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = '';
    editingJobId = null;
    document.getElementById('cronJobForm')?.reset();
    document.getElementById('cronJobForm').style.display = '';
    document.getElementById('outputSection').classList.add('hidden');
  }

  document.getElementById('modalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalBackdrop') {
      closeModal();
    }
  });

  document.getElementById('createCronJobBtn')?.addEventListener('click', () => {
    editingJobId = null;
    document.getElementById('modalTitle').textContent = 'Create Cron Job';
    document.getElementById('cronJobForm').reset();
    document.getElementById('cronJobForm').style.display = '';
    document.getElementById('cronJobId').value = '';
    document.getElementById('cronJobCommandType').value = 'forge';
    document.getElementById('advancedMode').checked = false;
    document.getElementById('outputSection').classList.add('hidden');
    toggleCommandType();
    toggleScheduleMode();
    openModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modal = document.getElementById('cronJobModal');
      if (modal && !modal.classList.contains('hidden')) {
        closeModal();
      }
    }
  });

  function toggleScheduleMode() {
    const advanced = document.getElementById('advancedMode').checked;
    const simpleDiv = document.getElementById('simpleSchedule');
    const advancedDiv = document.getElementById('advancedSchedule');
    const modeLabel = document.getElementById('modeLabel');

    if (advanced) {
      simpleDiv.classList.add('hidden');
      advancedDiv.classList.remove('hidden');
      modeLabel.textContent = 'Advanced';
    } else {
      simpleDiv.classList.remove('hidden');
      advancedDiv.classList.add('hidden');
      modeLabel.textContent = 'Simple';
      updateSchedulePreview();
    }
  }

  function toggleCommandType() {
    const commandType = document.getElementById('cronJobCommandType').value;
    const forgeContainer = document.getElementById('forgeCommandContainer');
    const scriptContainer = document.getElementById('scriptCommandContainer');
    const commandLabel = document.getElementById('commandLabel');
    const forgeCommand = document.getElementById('cronJobCommand');
    const scriptCommand = document.getElementById('cronJobScript');

    if (commandType === 'script') {
      forgeContainer.classList.add('hidden');
      scriptContainer.classList.remove('hidden');
      commandLabel.textContent = 'Script Path';
      if (forgeCommand) forgeCommand.removeAttribute('required');
      if (scriptCommand) scriptCommand.setAttribute('required', 'required');
    } else {
      forgeContainer.classList.remove('hidden');
      scriptContainer.classList.add('hidden');
      commandLabel.textContent = 'Command';
      if (scriptCommand) scriptCommand.removeAttribute('required');
      if (forgeCommand) forgeCommand.setAttribute('required', 'required');
    }
  }

  function updateSchedulePreview() {
    const seconds = document.getElementById('scheduleSeconds').value.trim() || '*';
    const minutes = document.getElementById('scheduleMinutes').value.trim() || '*';
    const hours = document.getElementById('scheduleHours').value.trim() || '*';
    const days = document.getElementById('scheduleDays').value.trim() || '*';
    const months = document.getElementById('scheduleMonths').value.trim() || '*';

    let preview = '';

    if (minutes === '*' && hours === '*' && days === '*' && months === '*') {
      preview = 'Every minute';
    } else if (minutes !== '*' && hours === '*' && days === '*' && months === '*') {
      const minVal = parseInt(minutes) || 0;
      if (minVal === 0) {
        preview = 'Every hour';
      } else {
        preview = `Every ${minVal} minute${minVal !== 1 ? 's' : ''}`;
      }
    } else if (minutes === '*' && hours !== '*' && days === '*' && months === '*') {
      const hourVal = parseInt(hours) || 0;
      const hour12 = hourVal > 12 ? hourVal - 12 : (hourVal == 0 ? 12 : hourVal);
      const ampm = hourVal >= 12 ? 'PM' : 'AM';
      preview = `Every minute at hour ${hour12} ${ampm}`;
    } else if (minutes !== '*' && hours !== '*' && days === '*' && months === '*') {
      const hourVal = parseInt(hours) || 0;
      const minVal = parseInt(minutes) || 0;
      const hour12 = hourVal > 12 ? hourVal - 12 : (hourVal == 0 ? 12 : hourVal);
      const ampm = hourVal >= 12 ? 'PM' : 'AM';
      preview = `Daily at ${hour12}:${String(minVal).padStart(2, '0')} ${ampm}`;
    } else if (days !== '*' && months === '*') {
      preview = `Monthly on day ${days}`;
    } else if (months !== '*') {
      const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      preview = `Yearly in ${monthNames[parseInt(months)] || months}`;
    } else {
      preview = `${minutes} ${hours} ${days} ${months} *`;
    }

    document.getElementById('previewText').textContent = preview || '-';
  }

  ['scheduleSeconds', 'scheduleMinutes', 'scheduleHours', 'scheduleDays', 'scheduleMonths'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateSchedulePreview);
  });

  async function editCronJob(id) {
    try {
      const response = await fetch(`/hub/cron-jobs/${encodeURIComponent(id)}`, {
        headers: {
          'X-CSRF-Token': window.csrfToken
        }
      });
      const data = await response.json();

      if (data.success && data.cronJob) {
        editingJobId = id;
        const job = data.cronJob;
        document.getElementById('modalTitle').textContent = 'Edit Cron Job';
        document.getElementById('cronJobId').value = id;
        document.getElementById('cronJobName').value = job.name || '';
        document.getElementById('cronJobCommandType').value = job.command_type || 'forge';
        document.getElementById('cronJobEnabled').checked = job.enabled !== false;

        toggleCommandType();

        if (job.command_type === 'script') {
          document.getElementById('cronJobScript').value = job.command || '';
        } else {
          const commandParts = (job.command || '').split(' ');
          const baseCommand = commandParts[0] || '';
          const args = commandParts.slice(1).join(' ');

          if (document.getElementById('commandModeSelect').checked) {
            document.getElementById('cronJobCommandSelect').value = baseCommand;
            updateCommandFromSelect();
            if (args) {
              document.getElementById('cronJobCommandArgs').value = args;
              updateCommandFromSelect();
            }
          } else {
            document.getElementById('cronJobCommand').value = job.command || '';
          }
        }

        if (job.schedule?.mode === 'advanced' || job.schedule?.expression) {
          document.getElementById('advancedMode').checked = true;
          document.getElementById('cronExpression').value = job.cron_expression || job.schedule?.expression || '* * * * *';
        } else {
          document.getElementById('advancedMode').checked = false;
          document.getElementById('scheduleSeconds').value = job.schedule?.seconds ?? '';
          document.getElementById('scheduleMinutes').value = job.schedule?.minutes ?? '';
          document.getElementById('scheduleHours').value = job.schedule?.hours ?? '';
          document.getElementById('scheduleDays').value = job.schedule?.days ?? '*';
          document.getElementById('scheduleMonths').value = job.schedule?.months ?? '*';
        }

        toggleScheduleMode();
        updateSchedulePreview();

        document.getElementById('outputSection').classList.remove('hidden');
        loadOutput(data.cronJob.id);

        openModal();
      } else {
        alert('Failed to load cron job');
      }
    } catch (error) {
      alert('Error loading cron job: ' + error.message);
    }
  }

  async function loadOutput(jobId) {
    if (!jobId) return;

    const outputContent = document.getElementById('outputContent');
    if (!outputContent) return;

    outputContent.textContent = 'Loading...';

    try {
      const url = `/hub/cron-jobs/${encodeURIComponent(jobId)}/output?lines=200`;
      const response = await fetch(url, {
        headers: {
          'X-CSRF-Token': window.csrfToken,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (!response.ok) {
        console.error('Output fetch error:', data);
        throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
      }

      if (data.success) {
        if (data.output !== null && data.output !== undefined && data.output !== '') {
          outputContent.textContent = data.output;
          const container = outputContent.parentElement;
          if (container) {
            setTimeout(() => {
              container.scrollTop = container.scrollHeight;
            }, 100);
          }
        } else {
          outputContent.textContent = 'No output available yet. Run the command to see output here.';
        }
      } else {
        outputContent.textContent = data.message || 'Failed to load output';
      }
    } catch (error) {
      console.error('Error loading output:', error);
      outputContent.textContent = 'Error loading output: ' + error.message;
    }
  }

  function refreshOutput() {
    if (editingJobId) {
      loadOutput(editingJobId);
    }
  }

  async function clearOutput() {
    if (!editingJobId) return;
    if (!confirm('Are you sure you want to clear the output log?')) return;

    try {
      const response = await fetch(`/hub/cron-jobs/${encodeURIComponent(editingJobId)}/output`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': window.csrfToken
        }
      });
      const data = await response.json();

      if (data.success) {
        document.getElementById('outputContent').textContent = 'Output cleared';
        document.getElementById('outputSection').classList.add('hidden');
      } else {
        alert(data.message || 'Failed to clear output');
      }
    } catch (error) {
      alert('Error clearing output: ' + error.message);
    }
  }

  async function viewOutput(jobId) {
    editingJobId = jobId;
    try {
      const response = await fetch(`/hub/cron-jobs/${encodeURIComponent(jobId)}/output?lines=200`, {
        headers: {
          'X-CSRF-Token': window.csrfToken,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (!response.ok) {
        console.error('Output fetch error:', data);
        throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
      }

      if (data.success) {
        const jobResponse = await fetch(`/hub/cron-jobs/${encodeURIComponent(jobId)}`, {
          headers: {
            'X-CSRF-Token': window.csrfToken
          }
        });
        const jobData = await jobResponse.json();
        const jobName = jobData.success && jobData.cronJob ? jobData.cronJob.name : 'Untitled';

        document.getElementById('modalTitle').textContent = 'View Output: ' + jobName;
        document.getElementById('cronJobForm').style.display = 'none';
        document.getElementById('outputSection').classList.remove('hidden');

        if (data.output !== null && data.output !== undefined && data.output !== '') {
          document.getElementById('outputContent').textContent = data.output;
          const container = document.getElementById('outputContent').parentElement;
          if (container) {
            setTimeout(() => {
              container.scrollTop = container.scrollHeight;
            }, 100);
          }
        } else {
          document.getElementById('outputContent').textContent = 'No output available yet. Run the command to see output here.';
        }

        openModal();
      } else {
        alert('Failed to load output: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error loading output:', error);
      alert('Error loading output: ' + error.message);
    }
  }

  let runningJobId = null;
  let runningJobName = null;

  async function runCronJob(jobId, jobName) {
    if (!jobId) {
      alert('Invalid job ID');
      return;
    }

    runningJobId = jobId;
    runningJobName = jobName || 'Untitled';

    document.getElementById('runModalTitle').textContent = `Run: ${runningJobName}`;
    document.getElementById('runJobInfo').textContent = `Executing command for "${runningJobName}"...`;
    document.getElementById('runOutput').textContent = 'Starting execution...';
    document.getElementById('runStatus').textContent = 'Preparing...';
    document.getElementById('viewOutputButton').classList.add('hidden');
    document.getElementById('runCloseButton').textContent = 'Close';

    const viewOutputBtn = document.getElementById('viewOutputButton');
    if (viewOutputBtn) {
      viewOutputBtn.setAttribute('data-job-id', jobId);
    }

    openRunModal();

    try {
      const response = await fetch(`/hub/cron-jobs/${encodeURIComponent(jobId)}/run`, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': window.csrfToken
        }
      });

      const data = await response.json();

      if (data.success) {
        document.getElementById('runStatus').textContent = `Completed with exit code: ${data.exit_code || 0}`;
        document.getElementById('runOutput').textContent = data.output_preview || 'Command executed successfully.';
        document.getElementById('viewOutputButton').classList.remove('hidden');
        document.getElementById('runCloseButton').textContent = 'Close';
      } else {
        document.getElementById('runStatus').textContent = 'Failed';
        document.getElementById('runOutput').textContent = data.message || 'Failed to execute command';
        document.getElementById('runCloseButton').textContent = 'Close';
      }
    } catch (error) {
      document.getElementById('runStatus').textContent = 'Error';
      document.getElementById('runOutput').textContent = 'Error executing command: ' + error.message;
      document.getElementById('runCloseButton').textContent = 'Close';
    }
  }

  function openRunModal() {
    const modal = document.getElementById('runModal');
    const backdrop = document.getElementById('runModalBackdrop');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    backdrop.addEventListener('click', closeRunModal);
    document.addEventListener('keydown', handleRunModalEscape);
  }

  function closeRunModal() {
    const modal = document.getElementById('runModal');
    const backdrop = document.getElementById('runModalBackdrop');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    runningJobId = null;
    runningJobName = null;
    backdrop.removeEventListener('click', closeRunModal);
    document.removeEventListener('keydown', handleRunModalEscape);
  }

  function handleRunModalEscape(e) {
    if (e.key === 'Escape') {
      closeRunModal();
    }
  }

  function viewRunOutput() {
    const viewOutputBtn = document.getElementById('viewOutputButton');
    const jobId = viewOutputBtn?.getAttribute('data-job-id') || runningJobId;

    if (jobId) {
      closeRunModal();
      setTimeout(() => {
        viewOutput(jobId);
      }, 300);
    } else {
      console.error('No job ID available for viewing output', { runningJobId, dataJobId: viewOutputBtn?.getAttribute('data-job-id') });
      alert('Unable to view output: Job ID not found');
    }
  }

  let deletingJobId = null;
  let deletingJobName = null;

  function deleteCronJob(id, name) {
    deletingJobId = id;
    deletingJobName = name || 'Untitled';
    document.getElementById('deleteJobName').textContent = `Delete "${deletingJobName}"?`;
    openDeleteModal();
  }

  function openDeleteModal() {
    const modal = document.getElementById('deleteModal');
    const backdrop = document.getElementById('deleteModalBackdrop');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const firstButton = document.getElementById('confirmDeleteButton');
    if (firstButton) {
      setTimeout(() => firstButton.focus(), 100);
    }

    backdrop.addEventListener('click', closeDeleteModal);
    document.addEventListener('keydown', handleDeleteModalEscape);
  }

  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    const backdrop = document.getElementById('deleteModalBackdrop');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    deletingJobId = null;
    deletingJobName = null;
    backdrop.removeEventListener('click', closeDeleteModal);
    document.removeEventListener('keydown', handleDeleteModalEscape);
  }

  function handleDeleteModalEscape(e) {
    if (e.key === 'Escape') {
      closeDeleteModal();
    }
  }

  async function confirmDelete() {
    if (!deletingJobId) return;

    const button = document.getElementById('confirmDeleteButton');
    button.disabled = true;
    button.textContent = 'Deleting...';

    try {
      const response = await fetch(`/hub/cron-jobs/${encodeURIComponent(deletingJobId)}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': window.csrfToken
        }
      });
      const data = await response.json();

      if (data.success) {
        closeDeleteModal();
        location.reload();
      } else {
        button.disabled = false;
        button.textContent = 'Delete';
        alert(data.message || 'Failed to delete cron job');
      }
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Delete';
      alert('Error deleting cron job: ' + error.message);
    }
  }

  let availableCommands = {};
  let selectedCommandData = null;

  async function loadAvailableCommands() {
    try {
      const response = await fetch('/hub/cron-jobs/commands', {
        headers: {
          'X-CSRF-Token': window.csrfToken
        }
      });
      const data = await response.json();

      if (data.success && data.commands) {
        availableCommands = data.commands;
        populateCommandSelect();
      }
    } catch (error) {
      console.error('Failed to load commands:', error);
    }
  }

  function populateCommandSelect() {
    const select = document.getElementById('cronJobCommandSelect');
    if (!select) return;

    select.innerHTML = '<option value="">Select a command...</option>';

    const groups = Object.keys(availableCommands).sort();
    groups.forEach(group => {
      const optgroup = document.createElement('optgroup');
      optgroup.label = group.charAt(0).toUpperCase() + group.slice(1);

      availableCommands[group].forEach(cmd => {
        const option = document.createElement('option');
        option.value = cmd.name;
        option.textContent = cmd.name + (cmd.description ? ' - ' + cmd.description : '');
        option.setAttribute('data-command-data', JSON.stringify(cmd));
        optgroup.appendChild(option);
      });

      select.appendChild(optgroup);
    });
  }

  function toggleCommandInputMode() {
    const manualMode = document.getElementById('commandModeManual').checked;
    const manualInput = document.getElementById('manualCommandInput');
    const selectInput = document.getElementById('selectCommandInput');
    const textarea = document.getElementById('cronJobCommand');
    const select = document.getElementById('cronJobCommandSelect');

    if (manualMode) {
      manualInput.classList.remove('hidden');
      selectInput.classList.add('hidden');
      textarea.required = true;
      if (select) {
        select.required = false;
        select.removeAttribute('required');
      }
    } else {
      manualInput.classList.add('hidden');
      selectInput.classList.remove('hidden');
      textarea.required = false;
      textarea.removeAttribute('required');
      if (select) {
        select.required = true;
        select.setAttribute('required', 'required');
      }
      if (Object.keys(availableCommands).length === 0) {
        loadAvailableCommands();
      }
    }
  }

  function updateCommandFromSelect() {
    const select = document.getElementById('cronJobCommandSelect');
    const detailsDiv = document.getElementById('commandDetails');
    const description = document.getElementById('commandDescription');
    const usageDiv = document.getElementById('commandUsage');
    const usageText = document.getElementById('commandUsageText');
    const argsDiv = document.getElementById('commandArguments');
    const argsList = document.getElementById('commandArgumentsList');
    const argsInputDiv = document.getElementById('commandArgumentsInput');
    const examplesDiv = document.getElementById('commandExamples');
    const examplesList = document.getElementById('commandExamplesList');
    const textarea = document.getElementById('cronJobCommand');
    const argsInput = document.getElementById('cronJobCommandArgs');

    if (!select || !detailsDiv) return;

    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.value) {
      try {
        selectedCommandData = JSON.parse(selectedOption.getAttribute('data-command-data') || '{}');
      } catch (e) {
        selectedCommandData = { name: selectedOption.value };
      }

      detailsDiv.classList.remove('hidden');

      if (selectedCommandData.description) {
        description.textContent = selectedCommandData.description;
        description.classList.remove('hidden');
      } else {
        description.textContent = 'No description available';
        description.classList.remove('hidden');
      }

      if (selectedCommandData.usage) {
        usageText.textContent = selectedCommandData.usage;
        usageDiv.classList.remove('hidden');
      } else {
        usageDiv.classList.add('hidden');
      }

      if (selectedCommandData.arguments && selectedCommandData.arguments.length > 0) {
        argsList.innerHTML = '';
        selectedCommandData.arguments.forEach(arg => {
          const argDiv = document.createElement('div');
          argDiv.className = 'text-xs bg-white px-2 py-1 rounded border border-gray-300';
          const required = arg.required ? '<span class="text-red-500">*</span>' : '';
          const defaultValue = arg.default !== null && arg.default !== undefined ? ` (default: ${arg.default})` : '';
          argDiv.innerHTML = `<code>--${arg.name}</code> ${required} - ${arg.description}${defaultValue}`;
          argsList.appendChild(argDiv);
        });
        argsDiv.classList.remove('hidden');
        argsInputDiv.classList.remove('hidden');
      } else {
        argsDiv.classList.add('hidden');
        argsInputDiv.classList.add('hidden');
      }

      if (selectedCommandData.examples && selectedCommandData.examples.length > 0) {
        examplesList.innerHTML = '';
        selectedCommandData.examples.forEach(example => {
          const exampleDiv = document.createElement('div');
          exampleDiv.className = 'text-xs bg-white px-2 py-1 rounded border border-gray-300 font-mono';
          exampleDiv.textContent = example;
          examplesList.appendChild(exampleDiv);
        });
        examplesDiv.classList.remove('hidden');
      } else {
        examplesDiv.classList.add('hidden');
      }

      const argsInput = document.getElementById('cronJobCommandArgs');
      const hiddenInput = document.getElementById('cronJobCommandHidden');
      if (textarea) {
        const baseCommand = selectedCommandData.name || selectedOption.value;
        const args = argsInput ? argsInput.value.trim() : '';
        const fullCommand = args ? `${baseCommand} ${args}`.trim() : baseCommand;
        textarea.value = fullCommand;
        if (hiddenInput) {
          hiddenInput.value = fullCommand;
        }
      } else if (hiddenInput) {
        const baseCommand = selectedCommandData.name || selectedOption.value;
        const args = argsInput ? argsInput.value.trim() : '';
        hiddenInput.value = args ? `${baseCommand} ${args}`.trim() : baseCommand;
      }

      if (argsInput) {
        argsInput.removeEventListener('input', handleArgsInput);
        argsInput.addEventListener('input', handleArgsInput);
      }
    } else {
      detailsDiv.classList.add('hidden');
      selectedCommandData = null;
      if (textarea) {
        textarea.value = '';
      }
      const argsInput = document.getElementById('cronJobCommandArgs');
      if (argsInput) {
        argsInput.value = '';
      }
    }
  }

  function handleArgsInput() {
    const textarea = document.getElementById('cronJobCommand');
    const hiddenInput = document.getElementById('cronJobCommandHidden');
    const select = document.getElementById('cronJobCommandSelect');
    if (select && selectedCommandData) {
      const baseCommand = selectedCommandData.name || select.value;
      const args = this.value.trim();
      const fullCommand = args ? `${baseCommand} ${args}`.trim() : baseCommand;
      if (textarea) {
        textarea.value = fullCommand;
      }
      if (hiddenInput) {
        hiddenInput.value = fullCommand;
      }
    }
  }


  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAvailableCommands);
  } else {
    loadAvailableCommands();
  }

  document.getElementById('cronJobForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const commandType = formData.get('command_type');

    let command = '';
    if (commandType === 'script') {
      command = document.getElementById('cronJobScript').value;
    } else {
      const manualMode = document.getElementById('commandModeManual').checked;
      if (manualMode) {
        command = document.getElementById('cronJobCommand').value;
      } else {
        const select = document.getElementById('cronJobCommandSelect');
        const argsInput = document.getElementById('cronJobCommandArgs');
        const baseCommand = select ? select.value : '';
        const args = argsInput ? argsInput.value.trim() : '';
        command = args ? `${baseCommand} ${args}`.trim() : baseCommand;

        if (!command && document.getElementById('cronJobCommand')) {
          command = document.getElementById('cronJobCommand').value;
        }
      }
    }

    if (!command || !command.trim()) {
      alert('Command is required');
      return;
    }

    const data = {
      name: formData.get('name'),
      command: command.trim(),
      command_type: commandType,
      enabled: formData.has('enabled'),
      advanced: document.getElementById('advancedMode').checked,
    };

    if (data.advanced) {
      data.schedule = {
        mode: 'advanced',
        expression: formData.get('schedule[expression]') || '* * * * *'
      };
    } else {
      const seconds = (formData.get('schedule[seconds]') || '').toString().trim();
      const minutes = (formData.get('schedule[minutes]') || '').toString().trim();
      const hours = (formData.get('schedule[hours]') || '').toString().trim();
      const days = (formData.get('schedule[days]') || '*').toString().trim();
      const months = (formData.get('schedule[months]') || '*').toString().trim();

      data.schedule = {
        mode: 'simple',
        seconds: seconds === '' ? '*' : seconds,
        minutes: minutes === '' ? '*' : minutes,
        hours: hours === '' ? '*' : hours,
        days: days === '' ? '*' : days,
        months: months === '' ? '*' : months
      };
    }

    const url = editingJobId ? `/hub/cron-jobs/${encodeURIComponent(editingJobId)}` : '/hub/cron-jobs';
    const method = editingJobId ? 'PUT' : 'POST';

    try {
      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (result.success) {
        location.reload();
      } else {
        alert(result.message || 'Failed to save cron job');
      }
    } catch (error) {
      alert('Error saving cron job: ' + error.message);
    }
  });

  updateSchedulePreview();
</script>
