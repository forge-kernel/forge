<?php
/** @var array<string, mixed> $props */
$side = $props['side'] ?? 'left';
$width = $props['width'] ?? '';

?>
<div class="fc-sidebar" style="<?= $width ? '--fc-sidebar-width: ' . e($width) : '' ?>">
    <?php if ($side === 'left'): ?>
        <div class="fc-sidebar__side"><?= slot('side') ?></div>
        <div class="fc-sidebar__main"><?= slot('default') ?></div>
    <?php else: ?>
        <div class="fc-sidebar__main"><?= slot('default') ?></div>
        <div class="fc-sidebar__side"><?= slot('side') ?></div>
    <?php endif; ?>
</div>
