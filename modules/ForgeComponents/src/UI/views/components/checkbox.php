<?php
use Modules\ForgeComponents\Definitions\CheckboxDefinition;
/** @var CheckboxDefinition $props */
?>
<div class="fc-checkbox-group">
  <input type="checkbox"
         name="<?= $props->name ?>"
         id="<?= $props->id ?>"
         value="<?= e($props->value) ?>"
         class="fc-checkbox"
         <?= $props->checked ? 'checked' : '' ?>
         <?= $props->required ? 'required' : '' ?>>
  <?php if ($props->label): ?>
        <label for="<?= $props->id ?>" class="fc-checkbox-label"><?= e($props->label) ?></label>
  <?php endif; ?>
</div>
