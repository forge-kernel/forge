<div id="logsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeLogsModal()"></div>

  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Deployment Logs</h3>
        <div class="flex items-center gap-2">
          <button id="refreshLogsBtn" onclick="refreshLogs()"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
            <i class="fa-solid fa-rotate"></i> Refresh
          </button>
          <button onclick="closeLogsModal()"
            class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <div class="px-6 py-6">
        <div id="logsContent"
          class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg max-h-96 overflow-y-auto whitespace-pre-wrap"
          style="min-height: 200px;">
          Loading logs...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let currentLogsDeploymentId = null;

  function closeLogsModal() {
    const modal = document.getElementById('logsModal');
    if (modal) {
      modal.classList.add('hidden');
      currentLogsDeploymentId = null;
    }
  }

  function refreshLogs() {
    if (currentLogsDeploymentId) {
      loadLogs(currentLogsDeploymentId);
    }
  }

  async function loadLogs(deploymentId) {
    currentLogsDeploymentId = deploymentId;
    const logsContent = document.getElementById('logsContent');
    if (!logsContent) return;

    logsContent.textContent = 'Loading logs...';

    try {
      const response = await fetch(`/hub/deployment/logs/${deploymentId}`, {
        method: 'GET',
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success) {
        logsContent.textContent = data.logs || 'No logs available';
        logsContent.scrollTop = logsContent.scrollHeight;
      } else {
        logsContent.textContent = 'Failed to load logs: ' + (data.message || 'Unknown error');
      }
    } catch (error) {
      logsContent.textContent = 'Error loading logs: ' + error.message;
    }
  }

  document.getElementById('logsModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
      closeLogsModal();
    }
  });

  window.viewLogs = function (deploymentId) {
    const modal = document.getElementById('logsModal');
    if (modal) {
      modal.classList.remove('hidden');
      loadLogs(deploymentId);
    }
  };
</script>
