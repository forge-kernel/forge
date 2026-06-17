<?php layout('main') ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-2xl mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
      </div>
      <h1 class="text-4xl font-bold text-gray-900 mb-2">ForgeWire Examples</h1>
      <p class="text-lg text-gray-600">Comprehensive examples of all ForgeWire directives and modifiers</p>
    </div>
    

    <div class="space-y-8">
      <!-- Polling Counter -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold">1</span>
            <h2 class="text-xl font-semibold text-gray-900">Polling Counter</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">Component with Slots</span>
          </div>
        </div>
        <div class="p-6">
          <?= component(
            name: 'examples/polling-counter',
            props: ['pollCount' => $pollCount],
            slots: [
              'header' => '<h3 class="text-lg font-semibold mb-2">Polling Counter - Auto-updates every 2 seconds</h3>',
              'help_text' => '<p class="text-sm text-gray-600 mb-3">This counter increments automatically on each poll using <code class="px-1.5 py-0.5 bg-gray-100 rounded text-xs font-mono">fw:poll.2s</code></p>',
              'footer' => '<p class="text-xs text-gray-500 mt-3">Uses <code class="px-1.5 py-0.5 bg-gray-100 rounded text-xs font-mono">fw:target</code> for partial updates</p>'
            ]
          ) ?>
        </div>
      </section>

      <!-- Interactive Counter -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-700 rounded-lg text-sm font-semibold">2</span>
            <h2 class="text-xl font-semibold text-gray-900">Interactive Counter</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded">Component with Component Slots</span>
          </div>
        </div>
        <div class="p-6">
          <?= component(
            name: 'examples/interactive-counter',
            props: ['counter' => $counter, 'step' => $step],
            slots: [
              'header' => '<h3 class="text-lg font-semibold mb-2">Interactive Counter</h3>',
              'help_text' => component(
                name: 'ui/alert',
                props: ['type' => 'info', 'children' => 'Click buttons to increment/decrement. Use step input to change increment amount.']
              )
            ]
          ) ?>
        </div>
      </section>

      <!-- Model Binding Demo -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-700 rounded-lg text-sm font-semibold">3</span>
            <h2 class="text-xl font-semibold text-gray-900">Model Binding Demo</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">Component</span>
          </div>
        </div>
        <div class="p-6">
          <?= component(
            name: 'examples/model-binding',
            props: [
              'immediateValue' => $immediateValue,
              'lazyValue' => $lazyValue,
              'deferValue' => $deferValue,
              'debounceValue' => $debounceValue,
              'customDebounceValue' => $customDebounceValue
            ],
            slots: [
              'header' => '<h3 class="text-lg font-semibold mb-2">Model Binding Types</h3>',
              'help_text' => '<p class="text-sm text-gray-600 mb-3">Try typing in each input to see the different update behaviors</p>'
            ]
          ) ?>
        </div>
      </section>

      <!-- Form Submission -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-700 rounded-lg text-sm font-semibold">4</span>
            <h2 class="text-xl font-semibold text-gray-900">Form Submission</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-700 rounded">Component with Slots</span>
          </div>
        </div>
        <div class="p-6">
          <?= component(
            name: 'examples/form-submission',
            props: [
              'formName' => $formName,
              'formEmail' => $formEmail,
              'formMessage' => $formMessage
            ],
            slots: [
              'header' => '<h3 class="text-lg font-semibold mb-2">Form with Validation</h3>',
              'help_text' => '<p class="text-sm text-gray-600 mb-3">Submit the form to see validation in action</p>',
              'submit_button' => '<button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Save Form</button>'
            ]
          ) ?>
        </div>
      </section>

      <!-- Keydown Handler -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-semibold">5</span>
            <h2 class="text-xl font-semibold text-gray-900">Keydown Handler</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 rounded">Direct Island</span>
          </div>
        </div>
        <div <?= fw_id('keydown-demo') ?> class="p-6">
          <h3 class="text-lg font-semibold mb-2">Keydown Events</h3>
          <p class="text-sm text-gray-600 mb-6">Press Enter or Escape in the inputs below</p>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Press Enter:</label>
              <input type="text" fw:keydown.enter="handleEnter"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors"
                placeholder="Type and press Enter" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Press Escape:</label>
              <input type="text" fw:keydown.escape="handleEscape"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors"
                placeholder="Type and press Escape" />
            </div>

            <div fw:target class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <p class="text-sm"><span class="font-semibold text-gray-900">Last Action:</span> <span class="text-gray-700"><?= e($lastKey) ?></span></p>
            </div>
          </div>
        </div>
      </section>

      <!-- Combined Features -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-pink-100 text-pink-700 rounded-lg text-sm font-semibold">6</span>
            <h2 class="text-xl font-semibold text-gray-900">Combined Features</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-pink-100 text-pink-700 rounded">Direct Island</span>
          </div>
        </div>
        <div <?= fw_id('combined-demo') ?> class="p-6">
          <h3 class="text-lg font-semibold mb-2">Combined Features Demo</h3>
          <p class="text-sm text-gray-600 mb-6">This island combines polling, click handlers, and model bindings</p>

          <div fw:poll.3s fw:action="onPoll" class="mb-6">
            <div fw:target class="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <p class="text-sm text-blue-800">Auto-polling every 3 seconds. Poll count: <span class="font-semibold"><?= $pollCount ?></span></p>
            </div>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Combined Value:</label>
            <input type="text" fw:model="combinedValue" value="<?= e($combinedValue) ?>"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
            <div fw:target class="mt-2 text-sm text-gray-600">
              Value: <span class="font-mono font-semibold"><?= e($combinedValue) ?></span>
            </div>
          </div>

          <div class="flex gap-3 mb-6">
            <button fw:click="increment" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Increment Counter</button>
            <button fw:click="decrement" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Decrement Counter</button>
          </div>

          <div fw:target class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-sm"><span class="font-semibold text-gray-900">Counter:</span> <span class="text-2xl font-bold text-gray-700"><?= $counter ?></span></p>
          </div>
        </div>
      </section>

      <!-- Loading States -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-700 rounded-lg text-sm font-semibold">7</span>
            <h2 class="text-xl font-semibold text-gray-900">Loading States</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded">Direct Island</span>
          </div>
        </div>
        <div <?= fw_id('loading-demo') ?> class="p-6">
          <h3 class="text-lg font-semibold mb-2">Loading State Demo</h3>
          <p class="text-sm text-gray-600 mb-6">Click the button to see loading state (simulated 1 second delay)</p>

          <button fw:click="incrementLoading" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors mb-6">Increment (with delay)</button>

          <div fw:target class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-sm"><span class="font-semibold text-gray-900">Loading Counter:</span> <span class="text-xl font-bold text-gray-700"><?= $loadingCounter ?></span></p>
          </div>

          <div fw:loading class="flex items-center gap-2 text-blue-600 font-medium">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing... Please wait
          </div>
        </div>
      </section>

      <!-- Shared State Demo -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-teal-100 text-teal-700 rounded-lg text-sm font-semibold">8</span>
            <h2 class="text-xl font-semibold text-gray-900">Shared State Demo</h2>
          </div>
        </div>
        <div class="p-6">
          <p class="mb-6 text-sm text-gray-600">These two islands share the same counter state using
            <code class="px-1.5 py-0.5 bg-gray-100 rounded text-xs font-mono">#[State(shared: true)]</code>. Updating one updates both automatically.
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div <?= fw_id('shared-counter-1') ?> class="p-6 bg-gray-50 rounded-lg border border-gray-200">
              <h3 class="text-lg font-semibold mb-4 text-gray-900">Island 1</h3>
              <div class="flex gap-2 mb-4">
                <button fw:click="incrementShared" class="flex-1 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">+</button>
                <button fw:click="decrementShared" class="flex-1 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">-</button>
              </div>
              <div fw:target class="text-center">
                <p class="text-sm text-gray-600 mb-1">Shared Counter</p>
                <p class="text-3xl font-bold text-gray-900"><?= $sharedCounter ?></p>
              </div>
            </div>

            <div <?= fw_id('shared-counter-2') ?> class="p-6 bg-gray-50 rounded-lg border border-gray-200">
              <h3 class="text-lg font-semibold mb-4 text-gray-900">Island 2</h3>
              <div class="flex gap-2 mb-4">
                <button fw:click="incrementShared" class="flex-1 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">+</button>
                <button fw:click="decrementShared" class="flex-1 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">-</button>
              </div>
              <div fw:target class="text-center">
                <p class="text-sm text-gray-600 mb-1">Shared Counter</p>
                <p class="text-3xl font-bold text-gray-900"><?= $sharedCounter ?></p>
              </div>
            </div>
          </div>

          <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-800">
              <strong>Note:</strong> The counter above (non-shared) remains independent:
              <span class="font-mono font-semibold"><?= $counter ?></span>
            </p>
          </div>
        </div>
      </section>

      <!-- Polling with Target Updates -->
      <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-700 rounded-lg text-sm font-semibold">9</span>
            <h2 class="text-xl font-semibold text-gray-900">Polling with Target Updates</h2>
            <span class="ml-auto px-2.5 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 rounded">Direct Island</span>
          </div>
        </div>
        <div <?= fw_id('polling-target-demo') ?> class="p-6">
          <h3 class="text-lg font-semibold mb-2">Polling with fw:target</h3>
          <p class="text-sm text-gray-600 mb-6">This demonstrates polling that only updates the target section</p>

          <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-sm font-medium text-gray-700 mb-1">Static content that doesn't update:</p>
            <p class="text-sm text-gray-500">This paragraph stays the same</p>
          </div>

          <div fw:poll.2s fw:action="onPoll">
            <div fw:target class="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <p class="text-sm mb-2"><span class="font-semibold text-blue-900">Poll Count:</span> <span class="text-blue-700"><?= $pollCount ?></span></p>
              <p class="text-sm"><span class="font-semibold text-blue-900">Time:</span> <span class="text-blue-700 font-mono"><?= date('H:i:s') ?></span></p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
