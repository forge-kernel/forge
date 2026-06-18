<?php

use App\Modules\ForgeComponents\Definitions\ButtonDefinition;
use App\Modules\ForgeComponents\Definitions\InputDefinition;
use App\Modules\ForgeComponents\Enums\ButtonSize;
use App\Modules\ForgeComponents\Enums\ButtonVariant;
use App\Modules\ForgeComponents\Enums\InputType;

?>

<?= form_open(attrs: ["class" => "fc-stack fc-stack--md"]) ?>
  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::TEXT,
      name: 'identifier',
      id: 'identifier',
      label: 'Identifier',
      placeholder: 'Enter your identifier',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::PASSWORD,
      name: 'password',
      id: 'password',
      label: 'Password',
      placeholder: 'Enter your password',
      required: true,
  )) ?>

  <div class="fc-split fc-split--center" style="justify-content: space-between;">
    <label class="fc-split--center" style="gap: var(--fc-spacing-2); cursor: pointer;">
      <input type="checkbox" name="remember" class="fc-checkbox">
      <span style="color: var(--fc-gray-600); font-size: var(--fc-font-size-sm);">Remember me</span>
    </label>
    <a href="/auth/forgot-password" class="fc-link" style="font-size: var(--fc-font-size-sm);">Forgot password?</a>
  </div>

  <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
      variant: ButtonVariant::PRIMARY,
      size: ButtonSize::LG,
      block: true,
  ), slots: [
      'children' => 'Sign in',
  ]) ?>
<?= form_close() ?>
