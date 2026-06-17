<?php
/**
 * @var string $formName
 * @var string $formEmail
 * @var string $formMessage
 */
?>
<div <?= fw_id('form-submission-demo') ?>>
  <div class="mb-4">
    <?= slot('header', '<h3 class="text-lg font-semibold">Form Submission Demo</h3>') ?>
  </div>
  <div>
    <?= slot('help_text') ?>

    <form fw:submit="saveForm" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Name (required, min 3 chars):</label>
        <input type="text" fw:model.debounce="formName" value="<?= e($formName) ?>"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors fw-invalid:border-red-300 fw-invalid:ring-red-500 fw-invalid:focus:ring-red-500" />

        <div class="mt-2 error-container" style="display: none;">
          <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"></path>
            </svg>
            <p class="text-sm text-red-800">
              <span fw:validation-error="formName" fw:validation-error.all></span>
            </p>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Email (required, valid email):</label>
        <input type="email" fw:model.debounce="formEmail" value="<?= e($formEmail) ?>"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors fw-invalid:border-red-300 fw-invalid:ring-red-500 fw-invalid:focus:ring-red-500" />
        <div class="mt-2 error-container" style="display: none;">
          <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"></path>
            </svg>
            <p class="text-sm text-red-800 font-medium">
              <span fw:validation-error="formEmail"></span>
            </p>
          </div>
        </div>
      </div>

      <div fw:target>
        <?php if ($formMessage): ?>
          <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800"><?= e($formMessage) ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="flex gap-3">
        <?= slot('submit_button', '<button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Save Form</button>') ?>
      </div>

      <div fw:loading class="flex items-center gap-2 text-blue-600 font-medium mt-2">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
          </path>
        </svg>
        Saving...
      </div>
    </form>
  </div>
  <div class="mt-4">
    <?= slot('footer') ?>
  </div>
</div>

<script>
  (function () {
    const formDemo = document.querySelector('[fw\\:id="form-submission-demo"]');
    if (!formDemo) return;

    function updateErrorContainers() {
      const errorContainers = formDemo.querySelectorAll('.error-container');
      errorContainers.forEach(container => {
        const span = container.querySelector('[fw\\:validation-error]');
        if (span && span.textContent.trim()) {
          container.style.display = '';
        } else {
          container.style.display = 'none';
        }
      });
    }

    const observer = new MutationObserver(updateErrorContainers);
    observer.observe(formDemo, {
      childList: true,
      subtree: true,
      characterData: true
    });

    updateErrorContainers();
  })();
</script>
