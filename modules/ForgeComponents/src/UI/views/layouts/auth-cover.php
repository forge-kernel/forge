<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var string $parentLayout
 */

$parentLayout = 'ForgeComponents:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'fc-auth-cover',
]);
?>
<div class="fc-auth-cover__card">
  <?= $content ?>
</div>
