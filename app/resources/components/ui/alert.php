<?php
/** @var string $type
 * @var string $children 
 */
?>
<div class="font-bold  <?= htmlspecialchars($type ?: 'info') ?>">
    <?= htmlspecialchars($children ?: 'info') ?? 'info' ?>
</div>