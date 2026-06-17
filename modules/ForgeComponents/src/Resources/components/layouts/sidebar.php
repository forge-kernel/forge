<?php

$side = $props['side'] ?? 'left';
$width = $props['width'] ?? '';

?>
<div class="fc-sidebar" style="<?= $width ? '--fc-sidebar-width: ' . $width : '' ?>">
    <?php if ($side === 'left'): ?>
        <div class="fc-sidebar__side"><?= $slots['side'] ?? '' ?></div>
        <div class="fc-sidebar__main"><?= $slots['default'] ?? '' ?></div>
    <?php else: ?>
        <div class="fc-sidebar__main"><?= $slots['default'] ?? '' ?></div>
        <div class="fc-sidebar__side"><?= $slots['side'] ?? '' ?></div>
    <?php endif; ?>
</div>
