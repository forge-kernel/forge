<?php
use App\Modules\ForgeComponents\Definitions\ButtonDefinition;
/** @var ButtonDefinition $props */
?>
<button type="<?= $props->type ?>"
        class="fc-btn fc-btn--<?= $props->variant->value ?> fc-btn--<?= $props->size->value ?><?= $props->block ? ' fc-btn--block' : '' ?>">
  <?= slot('children') ?>
</button>
