<div class="space-y-6">
  <!-- Welcome Section -->
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Welcome to ForgeHub</h1>
    <p class="text-sm text-gray-500 mt-1">Your administration dashboard</p>
  </div>

  <!-- System Information Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- PHP Version -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">PHP Version</p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($phpVersion) ?></p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
          </svg>
        </div>
      </div>
    </div>

    <!-- Kernel Version -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Kernel</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">v<?= htmlspecialchars($kernelVersion) ?></p>
        </div>
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
            </path>
          </svg>
        </div>
      </div>
    </div>

    <!-- Modules Count -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Modules</p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars((string) $moduleCount) ?></p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
        </div>
      </div>
    </div>

    <!-- Hub Items Count -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Hub Items</p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars((string) $hubItemCount) ?></p>
        </div>
        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Stats Row -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Log Files -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-gray-700">Log Files</h3>
        <a href="/hub/logs" class="text-xs text-blue-600 hover:text-blue-800 font-medium">View All →</a>
      </div>
      <p class="text-3xl font-bold text-gray-900"><?= htmlspecialchars((string) $logFileCount) ?></p>
      <p class="text-xs text-gray-500 mt-1">Available log files</p>
    </div>

    <!-- Cache Stats -->
    <?php if ($cacheStats): ?>
                          <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                              <h3 class="text-sm font-medium text-gray-700">Cache</h3>
                              <a href="/hub/cache" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Manage →</a>
                            </div>
                            <p class="text-3xl font-bold text-gray-900"><?= htmlspecialchars((string) ($cacheStats['keys_count'] ?? 0)) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Cached keys</p>
                            <p class="text-xs text-gray-400 mt-1 font-mono"><?= htmlspecialchars($cacheStats['driver'] ?? 'Unknown') ?></p>
                          </div>
    <?php endif; ?>

    <!-- Queue Stats -->
    <?php if ($queueStats): ?>
                          <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                              <h3 class="text-sm font-medium text-gray-700">Queue Jobs</h3>
                              <a href="/hub/queues" class="text-xs text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                            </div>
                            <p class="text-3xl font-bold text-gray-900"><?= htmlspecialchars((string) ($queueStats['total'] ?? 0)) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Total jobs</p>
                            <div class="flex gap-2 mt-2">
                              <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded">Pending:
                                <?= htmlspecialchars((string) ($queueStats['pending'] ?? 0)) ?></span>
                              <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded">Failed:
                                <?= htmlspecialchars((string) ($queueStats['failed'] ?? 0)) ?></span>
                            </div>
                          </div>
    <?php endif; ?>
  </div>

  <!-- Quick Links -->
  <div class="bg-white rounded-xl border border-gray-200 p-6">
    <h2 class="text-lg font-medium text-gray-800 mb-4">Quick Links</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($hubItems as $item): ?>
                            <a href="<?= htmlspecialchars($item['route']) ?>"
                              class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                              <div
                                class="w-10 h-10 bg-gray-100 group-hover:bg-blue-100 rounded-lg flex items-center justify-center transition-colors">
                                <i
                                  class="fa-solid fa-<?= htmlspecialchars($item['icon'] ?? 'circle') ?> text-gray-600 group-hover:text-blue-600"></i>
                              </div>
                              <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900"><?= htmlspecialchars($item['label']) ?>
                                </p>
                                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($item['route']) ?></p>
                              </div>
                              <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                              </svg>
                            </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- System Information -->
  <div class="bg-white rounded-xl border border-gray-200 p-6">
    <h2 class="text-lg font-medium text-gray-800 mb-4">System Information</h2>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono"><?= htmlspecialchars($phpVersion) ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Kernel Version</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono">v<?= htmlspecialchars($kernelVersion) ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Server Time</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono"><?= date('Y-m-d H:i:s') ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Timezone</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono"><?= date_default_timezone_get() ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Memory Limit</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono"><?= ini_get('memory_limit') ?></dd>
      </div>
      <div>
        <dt class="text-sm font-medium text-gray-500">Max Execution Time</dt>
        <dd class="mt-1 text-sm text-gray-900 font-mono"><?= ini_get('max_execution_time') ?>s</dd>
      </div>
    </dl>
  </div>
</div>
