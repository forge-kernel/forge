<?php
use App\Modules\ForgeComponents\Definitions\TextareaDefinition;
/** @var TextareaDefinition $props */
?>
<div class="fc-input-group">
  <?php if ($props->label): ?>
        <label for="<?= $props->id ?>" class="fc-input-label">
          <?= e($props->label) ?> <?= $props->required ? ' <span class="fc-input-label__required">*</span>' : '' ?>
        </label>
  <?php endif; ?>
  <textarea name="<?= $props->name ?>"
            id="<?= $props->id ?>"
            rows="<?= $props->rows ?>"
            placeholder="<?= e($props->placeholder) ?>"
            class="fc-input fc-textarea<?= $props->error ? ' fc-input--error' : '' ?>"
            <?= $props->required ? 'required' : '' ?>><?= e($props->value) ?></textarea>
  <?php if ($props->error): ?>
        <p class="fc-input-error"><?= e($props->error) ?></p>
  <?php endif; ?>
</div>
