<div id="configModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeConfigModal()"></div>

  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Deployment Configuration</h3>
        <button onclick="closeConfigModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="px-6 py-6">
        <form id="configForm" class="space-y-6">
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Server Configuration</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Server Name</label>
                <input type="text" name="server[name]" id="serverName"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <input type="text" name="server[region]" id="serverRegion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                <input type="text" name="server[size]" id="serverSize"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <input type="text" name="server[image]" id="serverImage"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Provision Configuration</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PHP Version</label>
                <input type="text" name="provision[php_version]" id="phpVersion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Type</label>
                <input type="text" name="provision[database_type]" id="databaseType"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Version</label>
                <input type="text" name="provision[database_version]" id="databaseVersion"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                <input type="text" name="provision[database_name]" id="databaseName"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>

          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Deployment Configuration</h4>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                <input type="text" name="deployment[domain]" id="deploymentDomain"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SSL Email</label>
                <input type="email" name="deployment[ssl_email]" id="sslEmail"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeConfigModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Configuration
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  async function loadConfig() {
    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'GET',
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success && data.config) {
        const config = data.config;

        if (config.server) {
          if (config.server.name) document.getElementById('serverName').value = config.server.name;
          if (config.server.region) document.getElementById('serverRegion').value = config.server.region;
          if (config.server.size) document.getElementById('serverSize').value = config.server.size;
          if (config.server.image) document.getElementById('serverImage').value = config.server.image;
        }

        if (config.provision) {
          if (config.provision.php_version) document.getElementById('phpVersion').value = config.provision.php_version;
          if (config.provision.database_type) document.getElementById('databaseType').value = config.provision.database_type;
          if (config.provision.database_version) document.getElementById('databaseVersion').value = config.provision.database_version;
          if (config.provision.database_name) document.getElementById('databaseName').value = config.provision.database_name;
        }

        if (config.deployment) {
          if (config.deployment.domain) document.getElementById('deploymentDomain').value = config.deployment.domain;
          if (config.deployment.ssl_email) document.getElementById('sslEmail').value = config.deployment.ssl_email;
        }
      }
    } catch (error) {
      console.error('Error loading config:', error);
    }
  }

  function closeConfigModal() {
    const modal = document.getElementById('configModal');
    if (modal) {
      modal.classList.add('hidden');
    }
  }

  document.getElementById('configForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const config = {
      server: {},
      provision: {},
      deployment: {},
    };

    for (const [key, value] of formData.entries()) {
      const parts = key.split('[');
      if (parts.length === 2) {
        const section = parts[0];
        const field = parts[1].replace(']', '');
        if (section === 'server' || section === 'provision' || section === 'deployment') {
          config[section][field] = value;
        }
      }
    }

    try {
      const response = await fetch('/hub/deployment/config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ config })
      });

      const data = await response.json();
      if (data.success) {
        alert('Configuration saved successfully');
        closeConfigModal();
        location.reload();
      } else {
        alert('Failed to save configuration: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error saving configuration: ' + error.message);
    }
  });

  document.getElementById('configModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
      closeConfigModal();
    }
  });

  const editConfigBtn = document.getElementById('editConfigBtn');
  if (editConfigBtn) {
    editConfigBtn.addEventListener('click', function () {
      const modal = document.getElementById('configModal');
      if (modal) {
        modal.classList.remove('hidden');
        loadConfig();
      }
    });
  }
</script>
