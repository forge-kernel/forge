<?php
use Forge\Core\Helpers\Format;
/** @var array $metrics */
?>
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">System Monitoring</h1>
      <p class="text-sm text-gray-500 mt-1">Real-time system metrics and performance data</p>
    </div>
    <button id="refreshBtn"
      class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors flex items-center gap-2">
      <i class="fa-solid fa-rotate" id="refreshIcon"></i>
      <span>Refresh</span>
    </button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">CPU Load</h2>
      <?php if ($metrics['cpu']['available']): ?>
            <dl class="space-y-3">
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">1 Minute</dt>
                <dd class="text-lg font-bold text-gray-900" id="cpu-1min">
                  <?= htmlspecialchars((string) $metrics['cpu']['1min']) ?></dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">5 Minutes</dt>
                <dd class="text-lg font-bold text-gray-900" id="cpu-5min">
                  <?= htmlspecialchars((string) $metrics['cpu']['5min']) ?></dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">15 Minutes</dt>
                <dd class="text-lg font-bold text-gray-900" id="cpu-15min">
                  <?= htmlspecialchars((string) $metrics['cpu']['15min']) ?></dd>
              </div>
            </dl>
      <?php else: ?>
            <p class="text-sm text-gray-500">CPU load information not available</p>
      <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">System Memory</h2>
      <?php if ($metrics['memory']['system']['available']): ?>
            <?php
            $sysMem = $metrics['memory']['system'];
            $sysMemTotal = $sysMem['total'];
            $sysMemUsed = $sysMem['used'];
            $sysMemFree = $sysMem['free'];
            $sysMemPercent = $sysMem['percentage'] ?? 0;
            ?>
            <dl class="space-y-3">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <dt class="text-sm font-medium text-gray-500">Usage</dt>
                  <dd class="text-sm font-semibold text-gray-900" id="sys-mem-percent">
                    <?= htmlspecialchars((string) $sysMemPercent) ?>%</dd>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full transition-all" id="sys-mem-bar"
                    style="width: <?= htmlspecialchars((string) $sysMemPercent) ?>%"></div>
                </div>
              </div>
              <div class="flex items-center justify-between pt-2">
                <dt class="text-sm font-medium text-gray-500">Total</dt>
                <dd class="text-sm font-semibold text-gray-900" id="sys-mem-total"><?= Format::fileSize($sysMemTotal) ?></dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">Used</dt>
                <dd class="text-sm font-semibold text-gray-900" id="sys-mem-used"><?= Format::fileSize($sysMemUsed) ?></dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">Free</dt>
                <dd class="text-sm font-semibold text-gray-900" id="sys-mem-free"><?= Format::fileSize($sysMemFree) ?></dd>
              </div>
            </dl>
      <?php else: ?>
            <p class="text-sm text-gray-500">System memory information not available</p>
      <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">PHP Memory</h2>
      <?php
      $phpMem = $metrics['memory']['php'];
      $phpMemCurrent = $phpMem['current'];
      $phpMemPeak = $phpMem['peak'];
      $phpMemLimit = $phpMem['limit'];
      $phpMemPercent = $phpMemLimit > 0 ? round(($phpMemCurrent / $phpMemLimit) * 100, 1) : 0;
      ?>
      <dl class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <dt class="text-sm font-medium text-gray-500">Usage</dt>
            <dd class="text-sm font-semibold text-gray-900" id="php-mem-percent">
              <?= htmlspecialchars((string) $phpMemPercent) ?>%</dd>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full transition-all" id="php-mem-bar"
              style="width: <?= htmlspecialchars((string) min($phpMemPercent, 100)) ?>%"></div>
          </div>
        </div>
        <div class="flex items-center justify-between pt-2">
          <dt class="text-sm font-medium text-gray-500">Current</dt>
          <dd class="text-sm font-semibold text-gray-900" id="php-mem-current"><?= Format::fileSize($phpMemCurrent) ?>
          </dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Peak</dt>
          <dd class="text-sm font-semibold text-gray-900" id="php-mem-peak"><?= Format::fileSize($phpMemPeak) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Limit</dt>
          <dd class="text-sm font-semibold text-gray-900" id="php-mem-limit">
            <?= $phpMemLimit > 0 ? Format::fileSize($phpMemLimit) : 'Unlimited' ?></dd>
        </div>
      </dl>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Disk Usage - Root</h2>
      <?php
      $rootDisk = $metrics['disk']['root'];
      $rootDiskTotal = $rootDisk['total'];
      $rootDiskUsed = $rootDisk['used'];
      $rootDiskFree = $rootDisk['free'];
      $rootDiskPercent = $rootDisk['percentage'] ?? 0;
      ?>
      <dl class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <dt class="text-sm font-medium text-gray-500">Usage</dt>
            <dd class="text-sm font-semibold text-gray-900" id="root-disk-percent">
              <?= htmlspecialchars((string) $rootDiskPercent) ?>%</dd>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-purple-600 h-2 rounded-full transition-all" id="root-disk-bar"
              style="width: <?= htmlspecialchars((string) $rootDiskPercent) ?>%"></div>
          </div>
        </div>
        <div class="flex items-center justify-between pt-2">
          <dt class="text-sm font-medium text-gray-500">Total</dt>
          <dd class="text-sm font-semibold text-gray-900" id="root-disk-total"><?= Format::fileSize($rootDiskTotal) ?>
          </dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Used</dt>
          <dd class="text-sm font-semibold text-gray-900" id="root-disk-used"><?= Format::fileSize($rootDiskUsed) ?>
          </dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Free</dt>
          <dd class="text-sm font-semibold text-gray-900" id="root-disk-free"><?= Format::fileSize($rootDiskFree) ?>
          </dd>
        </div>
      </dl>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Disk Usage - Storage</h2>
      <?php
      $storageDisk = $metrics['disk']['storage'];
      $storageDiskTotal = $storageDisk['total'];
      $storageDiskUsed = $storageDisk['used'];
      $storageDiskFree = $storageDisk['free'];
      $storageDiskPercent = $storageDisk['percentage'] ?? 0;
      ?>
      <dl class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <dt class="text-sm font-medium text-gray-500">Usage</dt>
            <dd class="text-sm font-semibold text-gray-900" id="storage-disk-percent">
              <?= htmlspecialchars((string) $storageDiskPercent) ?>%</dd>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-orange-600 h-2 rounded-full transition-all" id="storage-disk-bar"
              style="width: <?= htmlspecialchars((string) $storageDiskPercent) ?>%"></div>
          </div>
        </div>
        <div class="flex items-center justify-between pt-2">
          <dt class="text-sm font-medium text-gray-500">Total</dt>
          <dd class="text-sm font-semibold text-gray-900" id="storage-disk-total">
            <?= Format::fileSize($storageDiskTotal) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Used</dt>
          <dd class="text-sm font-semibold text-gray-900" id="storage-disk-used">
            <?= Format::fileSize($storageDiskUsed) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Free</dt>
          <dd class="text-sm font-semibold text-gray-900" id="storage-disk-free">
            <?= Format::fileSize($storageDiskFree) ?></dd>
        </div>
      </dl>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">System Information</h2>
      <dl class="grid grid-cols-1 gap-3">
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
          <dd class="text-sm font-semibold text-gray-900" id="sys-php-version">
            <?= htmlspecialchars($metrics['system']['php_version']) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">PHP SAPI</dt>
          <dd class="text-sm font-semibold text-gray-900" id="sys-php-sapi">
            <?= htmlspecialchars($metrics['system']['php_sapi']) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Operating System</dt>
          <dd class="text-sm font-semibold text-gray-900" id="sys-os"><?= htmlspecialchars($metrics['system']['os']) ?>
          </dd>
        </div>
        <?php if ($metrics['system']['uptime']): ?>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">Uptime</dt>
                <dd class="text-sm font-semibold text-gray-900" id="sys-uptime">
                  <?= htmlspecialchars($metrics['system']['uptime']) ?></dd>
              </div>
        <?php endif; ?>
        <?php if ($metrics['system']['process_count'] !== null): ?>
              <div class="flex items-center justify-between">
                <dt class="text-sm font-medium text-gray-500">Process Count</dt>
                <dd class="text-sm font-semibold text-gray-900" id="sys-process-count">
                  <?= htmlspecialchars((string) $metrics['system']['process_count']) ?></dd>
              </div>
        <?php endif; ?>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Server Time</dt>
          <dd class="text-sm font-semibold text-gray-900" id="sys-server-time">
            <?= htmlspecialchars($metrics['system']['server_time']) ?></dd>
        </div>
        <div class="flex items-center justify-between">
          <dt class="text-sm font-medium text-gray-500">Timezone</dt>
          <dd class="text-sm font-semibold text-gray-900" id="sys-timezone">
            <?= htmlspecialchars($metrics['system']['timezone']) ?></dd>
        </div>
      </dl>
    </div>
  </div>
</div>

<script>
  function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function updateMetrics(data) {
    const metrics = data.metrics;

    if (metrics.cpu.available) {
      document.getElementById('cpu-1min').textContent = metrics.cpu['1min'].toFixed(2);
      document.getElementById('cpu-5min').textContent = metrics.cpu['5min'].toFixed(2);
      document.getElementById('cpu-15min').textContent = metrics.cpu['15min'].toFixed(2);
    }

    if (metrics.memory.system.available) {
      const sysMem = metrics.memory.system;
      document.getElementById('sys-mem-percent').textContent = sysMem.percentage.toFixed(1) + '%';
      document.getElementById('sys-mem-bar').style.width = sysMem.percentage.toFixed(1) + '%';
      document.getElementById('sys-mem-total').textContent = formatBytes(sysMem.total);
      document.getElementById('sys-mem-used').textContent = formatBytes(sysMem.used);
      document.getElementById('sys-mem-free').textContent = formatBytes(sysMem.free);
    }

    const phpMem = metrics.memory.php;
    const phpMemPercent = phpMem.limit > 0 ? (phpMem.current / phpMem.limit * 100) : 0;
    document.getElementById('php-mem-percent').textContent = phpMemPercent.toFixed(1) + '%';
    document.getElementById('php-mem-bar').style.width = Math.min(phpMemPercent, 100).toFixed(1) + '%';
    document.getElementById('php-mem-current').textContent = formatBytes(phpMem.current);
    document.getElementById('php-mem-peak').textContent = formatBytes(phpMem.peak);
    document.getElementById('php-mem-limit').textContent = phpMem.limit > 0 ? formatBytes(phpMem.limit) : 'Unlimited';

    const rootDisk = metrics.disk.root;
    document.getElementById('root-disk-percent').textContent = rootDisk.percentage.toFixed(1) + '%';
    document.getElementById('root-disk-bar').style.width = rootDisk.percentage.toFixed(1) + '%';
    document.getElementById('root-disk-total').textContent = formatBytes(rootDisk.total);
    document.getElementById('root-disk-used').textContent = formatBytes(rootDisk.used);
    document.getElementById('root-disk-free').textContent = formatBytes(rootDisk.free);

    const storageDisk = metrics.disk.storage;
    document.getElementById('storage-disk-percent').textContent = storageDisk.percentage.toFixed(1) + '%';
    document.getElementById('storage-disk-bar').style.width = storageDisk.percentage.toFixed(1) + '%';
    document.getElementById('storage-disk-total').textContent = formatBytes(storageDisk.total);
    document.getElementById('storage-disk-used').textContent = formatBytes(storageDisk.used);
    document.getElementById('storage-disk-free').textContent = formatBytes(storageDisk.free);

    document.getElementById('sys-php-version').textContent = metrics.system.php_version;
    document.getElementById('sys-php-sapi').textContent = metrics.system.php_sapi;
    document.getElementById('sys-os').textContent = metrics.system.os;
    if (metrics.system.uptime) {
      document.getElementById('sys-uptime').textContent = metrics.system.uptime;
    }
    if (metrics.system.process_count !== null) {
      document.getElementById('sys-process-count').textContent = metrics.system.process_count.toString();
    }
    document.getElementById('sys-server-time').textContent = metrics.system.server_time;
    document.getElementById('sys-timezone').textContent = metrics.system.timezone;
  }

  document.getElementById('refreshBtn')?.addEventListener('click', async function () {
    const button = this;
    const icon = document.getElementById('refreshIcon');
    const originalText = button.querySelector('span')?.textContent;

    button.disabled = true;
    if (icon) {
      icon.classList.add('fa-spin');
    }
    if (button.querySelector('span')) {
      button.querySelector('span').textContent = 'Refreshing...';
    }

    try {
      const response = await fetch('/hub/monitoring/refresh', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken || ''
        }
      });

      const data = await response.json();
      if (data.success) {
        updateMetrics(data);
      } else {
        alert('Failed to refresh metrics');
      }
    } catch (error) {
      alert('Error refreshing metrics: ' + error.message);
    } finally {
      button.disabled = false;
      if (icon) {
        icon.classList.remove('fa-spin');
      }
      if (button.querySelector('span') && originalText) {
        button.querySelector('span').textContent = originalText;
      }
    }
  });
</script>
