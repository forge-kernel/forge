<?php
/**
 * @var int $pollCount
 */
?>
<div <?= fw_id('polling-counter') ?> class="card p-4 shadow-sm mb-4">
    <div class="card-header mb-3">
        <?= slot('header', '<h3 class="text-xl font-semibold">Polling Counter</h3>') ?>
    </div>
    <div class="card-body">
        <?= slot('help_text') ?>
        <div fw:poll.2s fw:action="onPoll">
            <div fw:target>
                <p class="text-lg"><strong>Poll count:</strong> <?= $pollCount ?></p>
                <p class="text-sm text-gray-600">Last updated: <?= date('H:i:s') ?></p>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <?= slot('footer') ?>
    </div>
</div>
