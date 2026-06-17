<?php
/**
 * @var string $immediateValue
 * @var string $lazyValue
 * @var string $deferValue
 * @var string $debounceValue
 * @var string $customDebounceValue
 */
?>
<div <?= fw_id('model-binding-demo') ?>>
    <div class="mb-4">
        <?= slot('header', '<h3 class="text-lg font-semibold">Model Binding Demo</h3>') ?>
    </div>
    <div class="space-y-6">
        <?= slot('help_text') ?>

        <div class="border-b border-gray-200 pb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Immediate (fw:model) - Updates on every keystroke:</label>
            <input type="text" fw:model="immediateValue" value="<?= e($immediateValue) ?>"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
            <div fw:target class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm"><span class="font-semibold text-gray-900">Value:</span> <span class="text-gray-700 font-mono"><?= e($immediateValue) ?></span></p>
            </div>
        </div>

        <div class="border-b border-gray-200 pb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Lazy (fw:model.lazy) - Updates on blur/change:</label>
            <input type="text" fw:model.lazy="lazyValue" value="<?= e($lazyValue) ?>"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
            <div fw:target class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm"><span class="font-semibold text-gray-900">Value:</span> <span class="text-gray-700 font-mono"><?= e($lazyValue) ?></span></p>
            </div>
        </div>

        <div class="border-b border-gray-200 pb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Defer (fw:model.defer) - Updates only when action triggered:</label>
            <div class="flex gap-3 items-start">
                <input type="text" fw:model.defer="deferValue" value="<?= e($deferValue) ?>"
                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
                <button fw:click="saveForm" class="px-4 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Update</button>
            </div>
            <div fw:target class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm"><span class="font-semibold text-gray-900">Value:</span> <span class="text-gray-700 font-mono"><?= e($deferValue) ?></span></p>
            </div>
        </div>

        <div class="border-b border-gray-200 pb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Debounce (fw:model.debounce) - 600ms default:</label>
            <input type="text" fw:model.debounce="debounceValue" value="<?= e($debounceValue) ?>"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
            <div fw:target class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm"><span class="font-semibold text-gray-900">Value:</span> <span class="text-gray-700 font-mono"><?= e($debounceValue) ?></span></p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Custom Debounce (fw:model.debounce.300ms) - 300ms:</label>
            <input type="text" fw:model.debounce.300ms="customDebounceValue" value="<?= e($customDebounceValue) ?>"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
            <div fw:target class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm"><span class="font-semibold text-gray-900">Value:</span> <span class="text-gray-700 font-mono"><?= e($customDebounceValue) ?></span></p>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <?= slot('footer') ?>
    </div>
</div>
