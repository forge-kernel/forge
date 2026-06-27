<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var string $parentLayout
 */

$parentLayout = 'ForgeComponents:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'fc-auth-center',
]);
?>
<div class="fc-auth-center__content">
  <?= $content ?>
</div>
