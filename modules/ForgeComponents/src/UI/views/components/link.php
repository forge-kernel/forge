<?php
use Modules\ForgeComponents\Definitions\LinkDefinition;
/** @var LinkDefinition $props */
?>
<a href="<?= e($props->href) ?>" class="fc-link fc-link--<?= $props->variant ?> fc-link--<?= $props->size ?>">
  <?= e($props->text) ?>
</a>
