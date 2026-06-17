<?php
/**
 *  @var array<string, mixed> $props
 *  @var array<string, mixed> $slots
 */
$gap = $props['gap'] ?? 'md';

?>
<div class="fc-stack fc-stack--<?= $gap ?>">
    <?= $slots['default'] ?? '' ?>
</div>
