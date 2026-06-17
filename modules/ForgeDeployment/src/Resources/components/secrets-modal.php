<div id="secretsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeSecretsModal()"></div>

  <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-900">Manage Secrets</h3>
        <button onclick="closeSecretsModal()"
          class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 rounded-lg p-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="px-6 py-6">
        <form id="secretsForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DigitalOcean API Token</label>
            <div class="flex items-center gap-2">
              <input type="password" name="digitalocean_api_token" id="digitaloceanToken"
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                placeholder="Enter API token or leave blank to keep current">
              <button type="button" onclick="togglePassword('digitaloceanToken')"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
                <i class="fa-solid fa-eye" id="digitaloceanTokenIcon"></i>
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">Leave blank to keep current value (masked as ••••••••)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cloudflare API Token</label>
            <div class="flex items-center gap-2">
              <input type="password" name="cloudflare_api_token" id="cloudflareToken"
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                placeholder="Enter API token or leave blank to keep current">
              <button type="button" onclick="togglePassword('cloudflareToken')"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
                <i class="fa-solid fa-eye" id="cloudflareTokenIcon"></i>
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">Leave blank to keep current value (masked as ••••••••)</p>
          </div>

          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start gap-2">
              <i class="fa-solid fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
              <div class="text-sm text-yellow-800">
                <p class="font-medium mb-1">Security Notice</p>
                <p>Secrets are stored securely. Only enter new values if you need to update them. Existing values are masked for security.</p>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeSecretsModal()"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Save Secrets
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  async function loadSecrets() {
    try {
      const response = await fetch('/hub/deployment/secrets', {
        method: 'GET',
        headers: {
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success && data.secrets) {
        const digitaloceanInput = document.getElementById('digitaloceanToken');
        const cloudflareInput = document.getElementById('cloudflareToken');

        if (digitaloceanInput && data.secrets.digitalocean_api_token) {
          digitaloceanInput.placeholder = 'Current value: ' + data.secrets.digitalocean_api_token;
        }
        if (cloudflareInput && data.secrets.cloudflare_api_token) {
          cloudflareInput.placeholder = 'Current value: ' + data.secrets.cloudflare_api_token;
        }
      }
    } catch (error) {
      console.error('Error loading secrets:', error);
    }
  }

  function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');

    if (input && icon) {
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  }

  function closeSecretsModal() {
    const modal = document.getElementById('secretsModal');
    if (modal) {
      modal.classList.add('hidden');
    }
  }

  document.getElementById('secretsForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const secrets = {
      digitalocean_api_token: formData.get('digitalocean_api_token') || '••••••••',
      cloudflare_api_token: formData.get('cloudflare_api_token') || '••••••••',
    };

    try {
      const response = await fetch('/hub/deployment/secrets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ secrets })
      });

      const data = await response.json();
      if (data.success) {
        alert('Secrets updated successfully');
        closeSecretsModal();
      } else {
        alert('Failed to update secrets: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error updating secrets: ' + error.message);
    }
  });

  document.getElementById('secretsModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
      closeSecretsModal();
    }
  });

  const manageSecretsBtn = document.getElementById('manageSecretsBtn');
  if (manageSecretsBtn) {
    manageSecretsBtn.addEventListener('click', function () {
      const modal = document.getElementById('secretsModal');
      if (modal) {
        modal.classList.remove('hidden');
        loadSecrets();
      }
    });
  }
</script>
