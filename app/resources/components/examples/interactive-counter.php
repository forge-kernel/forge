<?php
/**
 * @var int $counter
 * @var int $step
 */
?>
<div <?= fw_id('interactive-counter') ?>>
    <div class="mb-4">
        <?= slot('header', '<h3 class="text-lg font-semibold">Interactive Counter</h3>') ?>
    </div>
    <div>
        <?= slot('help_text') ?>

        <div class="flex gap-3 mb-6">
            <button fw:click="increment" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">+</button>
            <button fw:click="decrement" class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">-</button>
            <button fw:click="reset" fw:param-value="0" class="px-6 py-2.5 bg-white text-gray-700 border border-gray-300 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">Reset</button>
        </div>

        <div fw:target class="text-center p-6 bg-gray-50 rounded-lg border border-gray-200 mb-6">
            <p class="text-sm text-gray-600 mb-1">Count</p>
            <p class="text-4xl font-bold text-gray-900"><?= $counter ?></p>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Step:</label>
            <div class="flex gap-3">
                <input type="number" fw:model="step" value="<?= $step ?>"
                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent outline-none transition-colors" />
                <button fw:click="incrementBy" fw:param-step="<?= $step ?>"
                    class="px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                    Increment by <?= $step ?>
                </button>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <?= slot('footer') ?>
    </div>
</div>
