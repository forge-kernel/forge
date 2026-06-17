<?php
/**
 *  @var array<string, mixed> $props
 *  @var array<string, mixed> $slots
 */
$align = $props['align'] ?? 'start';

?>
<div class="fc-split fc-split--<?= $align ?>">
    <?= $slots['default'] ?? '' ?>
</div>
