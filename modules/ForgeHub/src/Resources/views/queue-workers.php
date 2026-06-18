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
                        <span
                          class="text-sm text-gray-900"><?= htmlspecialchars((string) ($worker['processes'] ?? 1)) ?></span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($worker['is_running'] ?? false): ?>
                              <span
                                class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Running</span>
                        <?php else: ?>
                              <span
                                class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Stopped</span>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php if (!empty($worker['pids'])): ?>
                              <?= htmlspecialchars(implode(', ', $worker['pids'])) ?>
                        <?php else: ?>
                              <span class="text-gray-400">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2" data-worker-id="<?= htmlspecialchars($worker['id'] ?? '') ?>">
                          <?php if ($worker['is_running'] ?? false): ?>
                                <button class="stop-worker-btn px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-md hover:bg-red-100 transition-colors"
                                  data-worker-id="<?= htmlspecialchars($worker['id'] ?? '') ?>">
                                  Stop
                                </button>
                          <?php else: ?>
                                <button class="start-worker-btn px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-md hover:bg-green-100 transition-colors"
                                  data-worker-id="<?= htmlspecialchars($worker['id'] ?? '') ?>">
                                  Start
                                </button>
                          <?php endif; ?>
                          <button class="view-worker-btn px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors"
                            data-worker-id="<?= htmlspecialchars($worker['id'] ?? '') ?>">
                            View
                          </button>
                          <button class="delete-worker-btn px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-md hover:bg-red-100 transition-colors"
                            data-worker-id="<?= htmlspecialchars($worker['id'] ?? '') ?>">
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
    <?php endif; ?>
  </div>

  <!-- Create Worker Modal -->
  <div id="createWorkerModal"
    class="overflow-y-auto fixed inset-0 z-50 hidden transition-opacity duration-300 ease-out">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>
    <div class="flex relative justify-center items-center p-4 min-h-screen sm:p-6">
      <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl transform transition-all duration-300 ease-out">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Create Queue Worker</h3>
          <button id="closeCreateModal"
            class="p-1 text-gray-400 rounded-lg transition-colors hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <form id="createWorkerForm" class="px-6 py-6 space-y-4">
          <div>
            <label for="workerName" class="block text-sm font-medium text-gray-700">Worker Name</label>
            <input type="text" id="workerName" name="name" required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
              placeholder="e.g., email-worker">
          </div>
          <div>
            <label for="workerQueues" class="block text-sm font-medium text-gray-700">Queues</label>
            <input type="text" id="workerQueues" name="queues" required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
              placeholder="e.g., emails, notifications">
            <p class="mt-1 text-xs text-gray-500">Comma-separated queue names</p>
          </div>
          <div>
            <label for="workerProcesses" class="block text-sm font-medium text-gray-700">Processes</label>
            <input type="number" id="workerProcesses" name="processes" min="1" max="10" value="1"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            <p class="mt-1 text-xs text-gray-500">Number of parallel processes (1-10)</p>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" id="cancelCreate"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
              Create Worker
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Worker Modal -->
  <div id="viewWorkerModal"
    class="overflow-y-auto fixed inset-0 z-50 hidden transition-opacity duration-300 ease-out">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>
    <div class="flex relative justify-center items-center p-4 min-h-screen sm:p-6">
      <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900" id="viewWorkerTitle">Worker Details</h3>
          <button id="closeViewModal"
            class="p-1 text-gray-400 rounded-lg transition-colors hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
            aria-label="Close modal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="px-6 py-6 space-y-6">
          <div id="viewWorkerContent">
            <!-- Content loaded dynamically -->
          </div>
          <div id="viewWorkerOutput" class="hidden">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Recent Output</h4>
            <pre id="workerOutputContent"
              class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-64 overflow-y-auto font-mono text-xs border border-gray-700 whitespace-pre-wrap"></pre>
          </div>
        </div>
        <div class="flex justify-end px-6 py-4 border-t border-gray-200">
          <button id="closeViewModalBtn"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('createWorkerBtn');
    const createModal = document.getElementById('createWorkerModal');
    const closeCreateBtn = document.getElementById('closeCreateModal');
    const cancelCreateBtn = document.getElementById('cancelCreate');
    const createForm = document.getElementById('createWorkerForm');
    const viewModal = document.getElementById('viewWorkerModal');
    const viewWorkerTitle = document.getElementById('viewWorkerTitle');
    const viewWorkerContent = document.getElementById('viewWorkerContent');
    const viewWorkerOutput = document.getElementById('viewWorkerOutput');
    const workerOutputContent = document.getElementById('workerOutputContent');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    const closeViewBtn = document.getElementById('closeViewModal');

    // Show/hide create worker modal
    function showModal(modal) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      setTimeout(() => modal.querySelector('.bg-white')?.classList.add('scale-100'), 10);
    }

    function hideModal(modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    if (createBtn) createBtn.addEventListener('click', () => showModal(createModal));
    if (closeCreateBtn) closeCreateBtn.addEventListener('click', () => hideModal(createModal));
    if (cancelCreateBtn) cancelCreateBtn.addEventListener('click', () => hideModal(createModal));

    createModal?.addEventListener('click', function(e) {
      if (e.target === this) hideModal(createModal);
    });

    // Create worker
    createForm?.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const queues = formData.get('queues')?.split(',').map(q => q.trim()).filter(Boolean) || [];

      const response = await fetch('/hub/queue-workers', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.csrfToken || ''
        },
        body: JSON.stringify({
          name: formData.get('name'),
          queues: queues,
          processes: parseInt(formData.get('processes')) || 1
        })
      });

      const data = await response.json();

      if (data.success) {
        hideModal(createModal);
        location.reload();
      } else {
        alert(data.message || 'Failed to create worker');
      }
    });

    // Start worker
    document.querySelectorAll('.start-worker-btn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.workerId;
        if (!confirm('Start this worker?')) return;

        const response = await fetch(`/hub/queue-workers/${id}/start`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken || ''
          }
        });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.message || 'Failed to start worker');
      });
    });

    // Stop worker
    document.querySelectorAll('.stop-worker-btn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.workerId;
        if (!confirm('Stop this worker?')) return;

        const response = await fetch(`/hub/queue-workers/${id}/stop`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken || ''
          }
        });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.message || 'Failed to stop worker');
      });
    });

    // View worker
    document.querySelectorAll('.view-worker-btn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.workerId;

        const response = await fetch(`/hub/queue-workers/${id}`);
        const data = await response.json();

        if (data.success) {
          const worker = data.worker;
          viewWorkerTitle.textContent = `Worker: ${worker.name || 'Untitled'}`;
          viewWorkerContent.innerHTML = `
                  <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <dt class="text-gray-500">Name</dt>
                      <dd class="font-medium text-gray-900">${worker.name || 'Untitled'}</dd>
                    </div>
                    <div>
                      <dt class="text-gray-500">Queues</dt>
                      <dd class="font-medium text-gray-900">${(worker.queues || []).join(', ')}</dd>
                    </div>
                    <div>
                      <dt class="text-gray-500">Processes</dt>
                      <dd class="font-medium text-gray-900">${worker.processes || 1}</dd>
                    </div>
                    <div>
                      <dt class="text-gray-500">Status</dt>
                      <dd class="font-medium ${worker.is_running ? 'text-green-600' : 'text-gray-900'}">${worker.is_running ? 'Running' : 'Stopped'}</dd>
                    </div>
                    <div>
                      <dt class="text-gray-500">PIDs</dt>
                      <dd class="font-medium text-gray-900">${(worker.pids || []).join(', ') || '-'}</dd>
                    </div>
                    <div>
                      <dt class="text-gray-500">Output Size</dt>
                      <dd class="font-medium text-gray-900">${worker.output_size || 0} bytes</dd>
                    </div>
                    <div class="col-span-2">
                      <dt class="text-gray-500">Created</dt>
                      <dd class="font-medium text-gray-900">${worker.created_at || '-'}</dd>
                    </div>
                    <div class="col-span-2">
                      <dt class="text-gray-500">Last Started</dt>
                      <dd class="font-medium text-gray-900">${worker.last_started_at || 'Never'}</dd>
                    </div>
                  </dl>
                `;

          if (worker.has_output && worker.last_output) {
            viewWorkerOutput.classList.remove('hidden');
            workerOutputContent.textContent = worker.last_output;
          } else {
            viewWorkerOutput.classList.add('hidden');
          }

          showModal(viewModal);
        } else {
          alert(data.message || 'Failed to load worker details');
        }
      });
    });

    // Close view modal
    if (closeViewModalBtn) closeViewModalBtn.addEventListener('click', () => hideModal(viewModal));
    if (closeViewBtn) closeViewBtn.addEventListener('click', () => hideModal(viewModal));
    viewModal?.addEventListener('click', function(e) {
      if (e.target === this) hideModal(viewModal);
    });

    // Delete worker
    document.querySelectorAll('.delete-worker-btn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.workerId;
        if (!confirm('Delete this worker? This action cannot be undone.')) return;

        const response = await fetch(`/hub/queue-workers/${id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': window.csrfToken || ''
          }
        });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.message || 'Failed to delete worker');
      });
    });
  });
</script>
