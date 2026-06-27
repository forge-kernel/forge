<?php
/**
 *  @var array<string, mixed> $props
 */
$gap = $props['gap'] ?? 'md';

?>
<div class="fc-stack fc-stack--<?= $gap ?>">
    <?= slot('default') ?>
</div>
