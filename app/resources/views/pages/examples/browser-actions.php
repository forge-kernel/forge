<?php layout('main') ?>

<?= component('ForgeUi:flash-container') ?>
<?= component('ForgeUi:flash-message') ?>
<?= component('ForgeUi:modals') ?>
<?= component('ForgeUi:notifications') ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
        </svg>
      </div>
      <h1 class="text-4xl font-bold text-gray-900 mb-2">ForgeWire Browser Actions</h1>
      <p class="text-lg text-gray-600">Examples of redirect, flash messages, and browser events triggered from controller actions</p>
    </div>

    <div <?= fw_id('browser-actions-demo') ?> class="space-y-6">
      <!-- Redirect -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold">1</span>
            <h2 class="text-xl font-semibold text-gray-900">Redirect</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Trigger a browser redirect from a controller action</p>
          <button fw:click="testRedirect" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
            Redirect to Examples
          </button>
        </div>
      </section>

      <!-- Flash Messages -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-700 rounded-lg text-sm font-semibold">2</span>
            <h2 class="text-xl font-semibold text-gray-900">Flash Messages</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Display flash messages that auto-dismiss after 5 seconds</p>
          <div class="flex flex-wrap gap-3">
            <button fw:click="testFlashSuccess" class="px-6 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
              Success Flash
            </button>
            <button fw:click="testFlashError" class="px-6 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
              Error Flash
            </button>
            <button fw:click="testFlashInfo" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
              Info Flash
            </button>
            <button fw:click="testFlashWarning" class="px-6 py-2.5 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors">
              Warning Flash
            </button>
          </div>
        </div>
      </section>

      <!-- Modal Events -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-700 rounded-lg text-sm font-semibold">3</span>
            <h2 class="text-xl font-semibold text-gray-900">Modal Events</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Open and close modals via controller-dispatched events</p>
          <div class="flex flex-wrap gap-3">
            <button fw:click="openModal" fw:param-modalId="confirmDelete" fw:param-title="Confirm Delete"
              fw:param-message="Are you sure you want to delete this item?"
              class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Open Delete Modal
            </button>
            <button fw:click="openModal" fw:param-modalId="infoModal" fw:param-title="Information"
              fw:param-message="This is an informational modal"
              class="px-6 py-2.5 bg-white text-gray-700 border border-gray-300 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Open Info Modal
            </button>
          </div>
        </div>
      </section>

      <!-- Notifications -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-semibold">4</span>
            <h2 class="text-xl font-semibold text-gray-900">Notifications</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Trigger custom notifications via events</p>
          <div class="flex flex-wrap gap-3">
            <button fw:click="showNotification" fw:param-type="success" fw:param-message="Operation completed successfully!"
              class="px-6 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
              Success Notification
            </button>
            <button fw:click="showNotification" fw:param-type="error" fw:param-message="Something went wrong!"
              class="px-6 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
              Error Notification
            </button>
          </div>
        </div>
      </section>

      <!-- Animations -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-pink-100 text-pink-700 rounded-lg text-sm font-semibold">5</span>
            <h2 class="text-xl font-semibold text-gray-900">Animations</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Trigger animations via events</p>
          <div class="flex gap-3">
            <button fw:click="triggerAnimation" fw:param-selector=".card" fw:param-animation="fadeIn"
              class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
              Animate Cards
            </button>
          </div>
        </div>
      </section>

      <!-- Combined Actions -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-700 rounded-lg text-sm font-semibold">6</span>
            <h2 class="text-xl font-semibold text-gray-900">Combined Actions</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-6">Combine multiple actions: flash + event + redirect</p>
          <button fw:click="combinedAction" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
            Combined Action
          </button>
        </div>
      </section>
    </div>
  </div>
</div>
