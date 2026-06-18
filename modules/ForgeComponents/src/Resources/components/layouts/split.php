<?php
/**
 *  @var array<string, mixed> $props
 */
$align = $props['align'] ?? 'start';

?>
<div class="fc-split fc-split--<?= $align ?>">
    <?= slot('default') ?>
</div>
