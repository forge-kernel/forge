<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Queue Workers</h1>
      <p class="text-sm text-gray-500 mt-1">Manage and monitor queue worker processes</p>
    </div>
    <button id="createWorkerBtn"
      class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">
      Create Worker
    </button>
  </div>

  <div id="workersList" class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <?php if (empty($workers)): ?>
          <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
              </path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No queue workers</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new queue worker.</p>
          </div>
    <?php else: ?>
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queues</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PIDs</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($workers as $worker): ?>
                    <tr>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($worker['name'] ?? 'Untitled') ?></div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                          <?php foreach (($worker['queues'] ?? []) as $queue): ?>
                                <span
                                  class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded"><?= htmlspecialchars($queue) ?></span>
                          <?php endforeach; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?= htmlspecialchars((string) ($worker['processes'] ?? 1)) ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1">
                          <?php if ($worker['is_running'] ?? false): ?>
                                <span
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 w-fit">Running</span>
                          <?php else: ?>
                                <span
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 w-fit">Stopped</span>
                          <?php endif; ?>
                          <?php if (isset($worker['last_started_at'])): ?>
                                <span class="text-xs text-gray-500">Started:
                                  <?= date('M j, Y H:i', strtotime($worker['last_started_at'])) ?></span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs text-gray-500 font-mono">
                          <?php if (!empty($worker['pids'])): ?>
                                <?= htmlspecialchars(implode(', ', $worker['pids'])) ?>
                          <?php else: ?>
                                -
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                          <?php if ($worker['has_output'] ?? false): ?>
                                <button onclick="viewOutput('<?= htmlspecialchars($worker['id'] ?? '') ?>')"
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
                          <?php if ($worker['is_running'] ?? false): ?>
                                <button onclick="stopWorker('<?= htmlspecialchars($worker['id'] ?? '') ?>')"
                                  class="text-red-600 hover:text-red-900 p-1.5 rounded hover:bg-red-50" title="Stop">
                                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                                  </svg>
                                </button>
                          <?php else: ?>
                                <button onclick="startWorker('<?= htmlspecialchars($worker['id'] ?? '') ?>')"
                                  class="text-green-600 hover:text-green-900 p-1.5 rounded hover:bg-green-50" title="Start">
                                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                  </svg>
                                </button>
                          <?php endif; ?>
                          <button onclick="editWorker('<?= htmlspecialchars($worker['id'] ?? '') ?>')"
                            class="text-blue-600 hover:text-blue-900">Edit</button>
                          <button
                            onclick="deleteWorker('<?= htmlspecialchars($worker['id'] ?? '') ?>', '<?= htmlspecialchars($worker['name'] ?? 'Untitled') ?>')"
                            class="text-red-600 hover:text-red-900">Delete</button>
                        </div>
                      </td>
                    </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
  </div>

  <div id="workerModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="modalBackdrop"></div>

    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Create Queue Worker</h3>
          <button onclick="closeModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form id="workerForm" class="px-6 py-6 space-y-6">
          <input type="hidden" id="workerId" name="id" value="">

          <div>
            <label for="workerName" class="block text-sm font-medium text-gray-700 mb-1">
              Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="workerName" name="name" required
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
              placeholder="e.g., Email Worker">
          </div>

          <div>
            <label for="workerQueues" class="block text-sm font-medium text-gray-700 mb-1">
              Queues <span class="text-red-500">*</span>
            </label>
            <textarea id="workerQueues" name="queues" required rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono"
              placeholder="emails, notifications, default"></textarea>
            <p class="mt-1 text-xs text-gray-500">Enter queue names separated by commas (e.g., <code
                class="bg-gray-100 px-1 rounded">emails, notifications</code>)</p>
          </div>

          <div>
            <label for="workerProcesses" class="block text-sm font-medium text-gray-700 mb-1">
              Processes <span class="text-red-500">*</span>
            </label>
            <input type="number" id="workerProcesses" name="processes" required min="1" max="10" value="1"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
            <p class="mt-1 text-xs text-gray-500">Number of worker processes per queue (1-10)</p>
          </div>

          <div class="p-3 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
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
          </div>

          <div id="outputSection" class="hidden border-t border-gray-200 pt-6">
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-gray-900">Output</h4>
                <div class="flex gap-2">
                  <button type="button" onclick="refreshOutput()"
                    class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Refresh
                  </button>
                  <button type="button" onclick="clearOutput()"
                    class="px-3 py-1.5 text-xs font-medium text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50">
                    Clear
                  </button>
                </div>
              </div>
              <div
                class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-64 overflow-y-auto font-mono text-xs border border-gray-700">
                <pre id="outputContent" class="whitespace-pre-wrap break-words">Loading output...</pre>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              <span id="submitButtonText">Create Worker</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="deleteModalBackdrop">
    </div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
              </svg>
            </div>
            <div>
              <h4 class="text-lg font-medium text-gray-900 mb-2" id="deleteWorkerName">Delete Queue Worker</h4>
              <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete this worker? This action cannot be
                undone and will stop the worker if it's running, then permanently remove it and its output logs.</p>
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

  <div id="startModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="startModalBackdrop"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <h4 class="text-lg font-medium text-gray-900 mb-2" id="startWorkerName">Start Queue Worker</h4>
              <p class="text-sm text-gray-600 mb-4">Are you sure you want to start this worker? It will begin processing
                jobs from the configured queues.</p>
              <div id="startWorkerDetails" class="text-xs text-gray-500 space-y-1"></div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
          <button type="button" onclick="closeStartModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button type="button" onclick="confirmStart()" id="confirmStartButton"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition-colors">
            Start Worker
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="stopModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="stopModalBackdrop"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
              </svg>
            </div>
            <div>
              <h4 class="text-lg font-medium text-gray-900 mb-2" id="stopWorkerName">Stop Queue Worker</h4>
              <p class="text-sm text-gray-600 mb-4">Are you sure you want to stop this worker? It will gracefully
                terminate all running processes and stop processing jobs.</p>
              <div id="stopWorkerDetails" class="text-xs text-gray-500 space-y-1"></div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
          <button type="button" onclick="closeStopModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button type="button" onclick="confirmStop()" id="confirmStopButton"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 transition-colors">
            Stop Worker
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="outputModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="outputModalBackdrop">
    </div>
    <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
      <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900" id="outputModalTitle">Worker Output</h3>
          <button onclick="closeOutputModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="px-6 py-6">
          <div
            class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-[70vh] overflow-y-auto font-mono text-xs border border-gray-700">
            <pre id="outputModalContent" class="whitespace-pre-wrap break-words">Loading...</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let editingWorkerId = null;
  let deletingWorkerId = null;
  let viewingOutputId = null;
  let startingWorkerId = null;
  let stoppingWorkerId = null;

  function openModal() {
    const modal = document.getElementById('workerModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const firstInput = modal.querySelector('input[type="text"], textarea');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }
  }

  function closeModal() {
    const modal = document.getElementById('workerModal');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = '';
    editingWorkerId = null;
    document.getElementById('workerForm')?.reset();
    document.getElementById('outputSection').classList.add('hidden');
  }

  document.getElementById('modalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalBackdrop') {
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modal = document.getElementById('workerModal');
      if (modal && !modal.classList.contains('hidden')) {
        closeModal();
      }
    }
  });

  document.getElementById('createWorkerBtn')?.addEventListener('click', () => {
    editingWorkerId = null;
    document.getElementById('modalTitle').textContent = 'Create Queue Worker';
    document.getElementById('submitButtonText').textContent = 'Create Worker';
    document.getElementById('workerForm').reset();
    document.getElementById('outputSection').classList.add('hidden');
    openModal();
  });

  async function editWorker(id) {
    editingWorkerId = id;
    document.getElementById('modalTitle').textContent = 'Edit Queue Worker';
    document.getElementById('submitButtonText').textContent = 'Update Worker';

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(id)}`);
      const data = await response.json();

      if (data.success && data.worker) {
        const worker = data.worker;
        document.getElementById('workerId').value = worker.id || '';
        document.getElementById('workerName').value = worker.name || '';
        document.getElementById('workerQueues').value = (worker.queues || []).join(', ');
        document.getElementById('workerProcesses').value = worker.processes || 1;

        if (worker.has_output) {
          document.getElementById('outputSection').classList.remove('hidden');
          await loadOutput(id);
        } else {
          document.getElementById('outputSection').classList.add('hidden');
        }

        openModal();
      } else {
        alert('Failed to load worker');
      }
    } catch (error) {
      console.error('Error loading worker:', error);
      alert('Error loading worker');
    }
  }

  async function loadOutput(id) {
    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(id)}/output`);
      const data = await response.json();

      if (data.success) {
        const outputContent = document.getElementById('outputContent');
        if (outputContent) {
          if (data.output) {
            outputContent.textContent = data.output;
          } else {
            outputContent.textContent = 'No output available';
          }
        }
      }
    } catch (error) {
      console.error('Error loading output:', error);
    }
  }

  async function refreshOutput() {
    if (editingWorkerId) {
      await loadOutput(editingWorkerId);
    }
  }

  async function clearOutput() {
    if (!editingWorkerId) return;

    if (!confirm('Are you sure you want to clear the output log?')) {
      return;
    }

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(editingWorkerId)}/output`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (data.success) {
        await loadOutput(editingWorkerId);
      } else {
        alert('Failed to clear output');
      }
    } catch (error) {
      console.error('Error clearing output:', error);
      alert('Error clearing output');
    }
  }

  document.getElementById('workerForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = {
      name: document.getElementById('workerName').value.trim(),
      queues: document.getElementById('workerQueues').value.split(',').map(q => q.trim()).filter(q => q),
      processes: parseInt(document.getElementById('workerProcesses').value) || 1,
    };

    if (!formData.name) {
      alert('Name is required');
      return;
    }

    if (formData.queues.length === 0) {
      alert('At least one queue is required');
      return;
    }

    try {
      const url = editingWorkerId
        ? `/hub/queue-workers/${encodeURIComponent(editingWorkerId)}`
        : '/hub/queue-workers';
      const method = editingWorkerId ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (data.success) {
        closeModal();
        location.reload();
      } else {
        alert(data.message || 'Failed to save worker');
      }
    } catch (error) {
      console.error('Error saving worker:', error);
      alert('Error saving worker');
    }
  });

  async function startWorker(id) {
    startingWorkerId = id;

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(id)}`);
      const data = await response.json();

      if (data.success && data.worker) {
        const worker = data.worker;
        document.getElementById('startWorkerName').textContent = `Start "${worker.name || 'Untitled'}"`;

        const details = document.getElementById('startWorkerDetails');
        details.innerHTML = `
          <div><strong>Queues:</strong> ${(worker.queues || []).join(', ') || 'None'}</div>
          <div><strong>Processes:</strong> ${worker.processes || 1} per queue</div>
        `;

        document.getElementById('startModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      } else {
        alert('Failed to load worker details');
      }
    } catch (error) {
      console.error('Error loading worker:', error);
      alert('Error loading worker details');
    }
  }

  function closeStartModal() {
    document.getElementById('startModal').classList.add('hidden');
    document.body.style.overflow = '';
    startingWorkerId = null;
  }

  document.getElementById('startModalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'startModalBackdrop') {
      closeStartModal();
    }
  });

  async function confirmStart() {
    if (!startingWorkerId) return;

    const button = document.getElementById('confirmStartButton');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Starting...';

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(startingWorkerId)}/start`, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (data.success) {
        closeStartModal();
        location.reload();
      } else {
        button.disabled = false;
        button.textContent = originalText;
        alert(data.message || 'Failed to start worker');
      }
    } catch (error) {
      console.error('Error starting worker:', error);
      button.disabled = false;
      button.textContent = originalText;
      alert('Error starting worker');
    }
  }

  async function stopWorker(id) {
    stoppingWorkerId = id;

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(id)}`);
      const data = await response.json();

      if (data.success && data.worker) {
        const worker = data.worker;
        document.getElementById('stopWorkerName').textContent = `Stop "${worker.name || 'Untitled'}"`;

        const details = document.getElementById('stopWorkerDetails');
        const pids = (worker.pids || []).length > 0 ? worker.pids.join(', ') : 'None';
        details.innerHTML = `
          <div><strong>Queues:</strong> ${(worker.queues || []).join(', ') || 'None'}</div>
          <div><strong>Processes:</strong> ${worker.processes || 1} per queue</div>
          <div><strong>Running PIDs:</strong> ${pids}</div>
        `;

        document.getElementById('stopModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      } else {
        alert('Failed to load worker details');
      }
    } catch (error) {
      console.error('Error loading worker:', error);
      alert('Error loading worker details');
    }
  }

  function closeStopModal() {
    document.getElementById('stopModal').classList.add('hidden');
    document.body.style.overflow = '';
    stoppingWorkerId = null;
  }

  document.getElementById('stopModalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'stopModalBackdrop') {
      closeStopModal();
    }
  });

  async function confirmStop() {
    if (!stoppingWorkerId) return;

    const button = document.getElementById('confirmStopButton');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Stopping...';

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(stoppingWorkerId)}/stop`, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (data.success) {
        closeStopModal();
        location.reload();
      } else {
        button.disabled = false;
        button.textContent = originalText;
        alert(data.message || 'Failed to stop worker');
      }
    } catch (error) {
      console.error('Error stopping worker:', error);
      button.disabled = false;
      button.textContent = originalText;
      alert('Error stopping worker');
    }
  }

  function deleteWorker(id, name) {
    deletingWorkerId = id;
    document.getElementById('deleteWorkerName').textContent = `Delete "${name}"`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
    deletingWorkerId = null;
  }

  document.getElementById('deleteModalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'deleteModalBackdrop') {
      closeDeleteModal();
    }
  });

  async function confirmDelete() {
    if (!deletingWorkerId) return;

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(deletingWorkerId)}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (data.success) {
        closeDeleteModal();
        location.reload();
      } else {
        alert(data.message || 'Failed to delete worker');
      }
    } catch (error) {
      console.error('Error deleting worker:', error);
      alert('Error deleting worker');
    }
  }

  async function viewOutput(id) {
    viewingOutputId = id;
    document.getElementById('outputModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    try {
      const response = await fetch(`/hub/queue-workers/${encodeURIComponent(id)}/output`);
      const data = await response.json();

      if (data.success) {
        const content = document.getElementById('outputModalContent');
        if (content) {
          if (data.output) {
            content.textContent = data.output;
          } else {
            content.textContent = 'No output available';
          }
        }
      }
    } catch (error) {
      console.error('Error loading output:', error);
      const content = document.getElementById('outputModalContent');
      if (content) {
        content.textContent = 'Error loading output';
      }
    }
  }

  function closeOutputModal() {
    document.getElementById('outputModal').classList.add('hidden');
    document.body.style.overflow = '';
    viewingOutputId = null;
  }

  document.getElementById('outputModalBackdrop')?.addEventListener('click', (e) => {
    if (e.target.id === 'outputModalBackdrop') {
      closeOutputModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const outputModal = document.getElementById('outputModal');
      if (outputModal && !outputModal.classList.contains('hidden')) {
        closeOutputModal();
      }
      const deleteModal = document.getElementById('deleteModal');
      if (deleteModal && !deleteModal.classList.contains('hidden')) {
        closeDeleteModal();
      }
      const startModal = document.getElementById('startModal');
      if (startModal && !startModal.classList.contains('hidden')) {
        closeStartModal();
      }
      const stopModal = document.getElementById('stopModal');
      if (stopModal && !stopModal.classList.contains('hidden')) {
        closeStopModal();
      }
    }
  });
</script>
