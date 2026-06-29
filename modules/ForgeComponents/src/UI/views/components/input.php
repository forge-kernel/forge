<?php
use Modules\ForgeComponents\Definitions\InputDefinition;
/** @var InputDefinition $props */
?>
<div class="fc-input-group">
  <?php if ($props->label): ?>
        <label for="<?= $props->id ?>" class="fc-input-label">
          <?= e($props->label) ?>    <?= $props->required ? ' <span class="fc-input-label__required">*</span>' : '' ?>
        </label>
  <?php endif; ?>
  <input type="<?= $props->type->value ?>"
         name="<?= $props->name ?>"
         id="<?= $props->id ?>"
         value="<?= e($props->value) ?>"
         placeholder="<?= e($props->placeholder) ?>"
         class="fc-input<?= $props->error ? ' fc-input--error' : '' ?>"
         <?= $props->required ? 'required' : '' ?>>
  <?php if ($props->error): ?>
        <p class="fc-input-error"><?= e($props->error) ?></p>
  <?php endif; ?>
</div>
