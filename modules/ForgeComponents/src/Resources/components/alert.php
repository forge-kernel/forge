<?php

use Forge\Core\Helpers\Flash;

$messages = Flash::flat();

if (empty($messages)) {
    return;
}

?>
<div class="fc-flash-stack">
    <?php foreach ($messages as $message): ?>
        <div class="fc-alert fc-alert--<?= $message['type'] ?? 'info' ?>" role="alert">
            <div class="fc-alert__content"><?= e($message['message']) ?></div>
        </div>
    <?php endforeach; ?>
</div>
