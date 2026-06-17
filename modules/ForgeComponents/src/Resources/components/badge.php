<?php
/** @var array<string, mixed> $props */
$type = $props['type'] ?? 'default';

?>
<span class="fc-badge fc-badge--<?= $type ?>">
    <?= e($props['text'] ?? '') ?>
</span>
