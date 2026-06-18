<?php
use App\Modules\ForgeComponents\Definitions\SelectDefinition;
/** @var SelectDefinition $props */
?>
<div class="fc-input-group">
  <?php if ($props->label): ?>
        <label for="<?= $props->id ?>" class="fc-input-label">
          <?= e($props->label) ?> <?= $props->required ? ' <span class="fc-input-label__required">*</span>' : '' ?>
        </label>
  <?php endif; ?>
  <select name="<?= $props->name ?>"
          id="<?= $props->id ?>"
          class="fc-input fc-select<?= $props->error ? ' fc-input--error' : '' ?>"
          <?= $props->required ? 'required' : '' ?>>
    <?php if ($props->placeholder): ?>
          <option value=""><?= e($props->placeholder) ?></option>
    <?php endif; ?>
    <?php foreach ($props->options as $option): ?>
          <option value="<?= e($option->value) ?>" <?= $option->selected ? 'selected' : '' ?>><?= e($option->label) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($props->error): ?>
        <p class="fc-input-error"><?= e($props->error) ?></p>
  <?php endif; ?>
</div>
