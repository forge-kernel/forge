<?php 
  /** 
  @var array $stats
  */ 
?>
<div class="space-y-6" fw:shared>
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Queue Jobs</h1>
    <p class="mt-1 text-sm text-gray-500">Manage and monitor queue jobs</p>
  </div>

  <!-- Stats Cards - Separate island -->
  <div <?= scope('queue-stats') ?> class="space-y-4" fw:depends="jobs">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Statistics</h2>
        <p class="text-sm text-gray-500">Queue job statistics</p>
      </div>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <!-- Total Jobs Card -->
      <div class="p-6 bg-white rounded-xl border border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500">Total Jobs</p>
            <p class="mt-1 text-2xl font-bold text-gray-900" fw:target>
              <?= htmlspecialchars((string) ($stats['total'] ?? 0)) ?>
            </p>
          </div>
          <div class="flex justify-center items-center w-12 h-12 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
              </path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Jobs Card -->
      <div class="p-6 bg-white rounded-xl border border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500">Pending</p>
            <p class="mt-1 text-2xl font-bold text-gray-900" fw:target>
              <?= htmlspecialchars((string) ($stats['pending'] ?? 0)) ?>
            </p>
          </div>
          <div class="flex justify-center items-center w-12 h-12 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Processing Jobs Card -->
      <div class="p-6 bg-white rounded-xl border border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500">Processing</p>
            <p class="mt-1 text-2xl font-bold text-gray-900" fw:target>
              <?= htmlspecialchars((string) ($stats['processing'] ?? 0)) ?>
            </p>
          </div>
          <div class="flex justify-center items-center w-12 h-12 bg-yellow-100 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
              </path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Failed Jobs Card -->
      <div class="p-6 bg-white rounded-xl border border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500">Failed</p>
            <p class="mt-1 text-2xl font-bold text-gray-900" fw:target>
              <?= htmlspecialchars((string) ($stats['failed'] ?? 0)) ?>
            </p>
          </div>
          <div class="flex justify-center items-center w-12 h-12 bg-red-100 rounded-lg">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Table - Separate island -->
  <div <?= scope('queue-table') ?>> <?= component(name: 'ForgeHub:data-table', props: [
        'columns' => [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'queue', 'label' => 'Queue', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'render' => 'badge'],
            ['key' => 'priority', 'label' => 'Priority', 'render' => 'priority'],
            ['key' => 'event_class', 'label' => 'Event Class', 'sortable' => true],
            ['key' => 'attempts', 'label' => 'Attempts', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Created', 'render' => 'date'],
        ],
        'rows' => $jobs ?? [],
        'paginator' => $paginator,
        'forgewire' => true,
        'expandable' => false,
        'bulkActions' => true,
        'filters' => ['search', 'status', 'queue'],
        'actions' => [
            'view' => ['action' => 'viewJob', 'param' => 'jobId', 'label' => 'View', 'variant' => 'secondary'],
            'retry' => ['action' => 'retryJob', 'param' => 'jobId', 'label' => 'Retry', 'variant' => 'primary'],
            'trigger' => ['action' => 'triggerJob', 'param' => 'jobId', 'label' => 'Trigger', 'variant' => 'primary'],
            'delete' => ['action' => 'deleteJob', 'param' => 'jobId', 'label' => 'Delete', 'variant' => 'danger'],
        ],
        'bulkRetry' => ['action' => 'bulkRetry'],
        'bulkDelete' => ['action' => 'bulkDelete'],
        'queues' => $queues ?? [],
        'selectedRows' => $selectedJobs ?? [],
        'sortColumn' => $sortColumn ?? 'created_at',
        'sortDirection' => $sortDirection ?? 'desc',
        'search' => $search ?? '',
        'statusFilter' => $statusFilter ?? '',
        'queueFilter' => $queueFilter ?? '',
    ]) ?> <?php if (!empty($jobDetails) && ($showJobModal ?? false)): ?>
                  <div class="overflow-y-auto fixed inset-0 z-50">
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity"></div>
                    <div class="flex relative justify-center items-center p-4 min-h-screen sm:p-6">
                      <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                          <h3 class="text-xl font-semibold text-gray-900">
                            Job #<?= htmlspecialchars((string) ($jobDetails['id'] ?? '')) ?> Details
                          </h3>
                          <button fw:click="closeJobModal"
                            class="p-1 text-gray-400 rounded-lg transition-colors hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            aria-label="Close modal">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                          </button>
                        </div>
                        <div class="px-6 py-6 space-y-6">
                          <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                            <div>
                              <p class="text-gray-500">Queue</p>
                              <p class="font-medium text-gray-900">
                                <?= htmlspecialchars((string) ($jobDetails['queue'] ?? 'default')) ?>
                              </p>
                            </div>
                            <div>
                              <p class="text-gray-500">Status</p>
                              <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['status'] ?? '')) ?></p>
                            </div>
                            <div>
                              <p class="text-gray-500">Priority</p>
                              <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['priority'] ?? 0)) ?>
                              </p>
                            </div>
                            <div>
                              <p class="text-gray-500">Attempts</p>
                              <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['attempts'] ?? 0)) ?>
                              </p>
                            </div>
                            <div>
                              <p class="text-gray-500">Event Class</p>
                              <p class="font-medium text-gray-900">
                                <?= htmlspecialchars((string) ($jobDetails['event_class'] ?? '')) ?>
                              </p>
                            </div>
                            <div>
                              <p class="text-gray-500">Created At</p>
                              <p class="font-medium text-gray-900"><?= htmlspecialchars((string) ($jobDetails['created_at'] ?? '')) ?>
                              </p>
                            </div>
                          </div>

                          <?php if (isset($jobDetails['details']['payload'])): ?>
                                        <div>
                                          <h4 class="mb-2 text-sm font-semibold text-gray-700">Payload</h4>
                                          <div
                                            class="bg-gray-900 text-gray-100 rounded-lg p-4 max-h-[50vh] overflow-y-auto font-mono text-xs border border-gray-700">
                                            <pre
                                              class="whitespace-pre-wrap break-words"><?= htmlspecialchars(json_encode($jobDetails['details']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                          </div>
                                        </div>
                          <?php endif; ?>

                          <?php if (isset($jobDetails['details']['metadata'])): ?>
                                        <div>
                                          <h4 class="mb-2 text-sm font-semibold text-gray-700">Metadata</h4>
                                          <dl class="grid grid-cols-2 gap-2 text-sm">
                                            <?php foreach ($jobDetails['details']['metadata'] as $key => $value): ?>
                                                          <dt class="font-medium text-gray-500"><?= htmlspecialchars(ucfirst((string) $key)) ?>:</dt>
                                                          <dd class="text-gray-900"><?= htmlspecialchars((string) $value) ?></dd>
                                            <?php endforeach; ?>
                                          </dl>
                                        </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
    <?php endif; ?>
  </div>
</div>
