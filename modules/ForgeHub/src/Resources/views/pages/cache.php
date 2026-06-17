<div class="grid gap-6">
  <div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-600 mb-4">Cache Statistics</h2>
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-sm font-medium text-gray-500">Driver</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono break-all"><?= htmlspecialchars($stats['driver']) ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Keys Count</dt>
        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars((string) $stats['keys_count']) ?></dd>
      </div>
    </dl>
  </div>

  <div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-600 mb-4">Cache Actions</h2>
    <div class="space-y-4">
      <div>
        <h3 class="text-sm font-medium text-gray-700 mb-2">Clear All Cache</h3>
        <p class="text-sm text-gray-500 mb-3">Remove all cached data from the application.</p>
        <?= component(name: 'ForgeHub:button', props: ['id' => 'clearAllCache', 'variant' => 'danger', 'children' => 'Clear All Cache']) ?>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Clear by Tag</h3>
        <p class="text-sm text-gray-500 mb-3">Remove cached data associated with a specific tag.</p>
        <form id="clearTagForm" class="flex flex-wrap gap-2">
          <?= component(name: 'ForgeHub:input', props: ['type' => 'text', 'name' => 'tag', 'id' => 'tag', 'placeholder' => 'Enter tag name', 'class' => 'flex-1']) ?>
          <?= component(name: 'ForgeHub:button', props: ['type' => 'submit', 'variant' => 'primary', 'children' => 'Clear Tag']) ?>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('clearAllCache')?.addEventListener('click', async function () {
    if (!confirm('Are you sure you want to clear all cache? This action cannot be undone.')) {
      return;
    }

    const button = this;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Clearing...';

    try {
      const response = await fetch('/hub/cache/clear', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken
        }
      });

      const data = await response.json();
      if (data.success) {
        alert('Cache cleared successfully');
        location.reload();
      } else {
        alert(data.message || 'Failed to clear cache');
      }
    } catch (error) {
      alert('Error clearing cache: ' + error.message);
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });

  document.getElementById('clearTagForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const tag = document.getElementById('tag')?.value;
    if (!tag) {
      alert('Please enter a tag name');
      return;
    }

    const form = this;
    const button = form.querySelector('button[type="submit"]');
    const originalText = button?.textContent;
    if (button) {
      button.disabled = true;
      button.textContent = 'Clearing...';
    }

    try {
      const formData = new FormData();
      formData.append('tag', tag);

      const response = await fetch('/hub/cache/clear-tag', {
        method: 'POST',
        headers: {
          'X-CSRF-Token': window.csrfToken
        },
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        alert(data.message || 'Tag cleared successfully');
        document.getElementById('tag').value = '';
      } else {
        alert(data.message || 'Failed to clear tag');
      }
    } catch (error) {
      alert('Error clearing tag: ' + error.message);
    } finally {
      if (button) {
        button.disabled = false;
        button.textContent = originalText;
      }
    }
  });
</script>
