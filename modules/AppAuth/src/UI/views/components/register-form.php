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
      type: InputType::EMAIL,
      name: 'email',
      id: 'email',
      label: 'Email',
      placeholder: 'Enter your email',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::PASSWORD,
      name: 'password',
      id: 'password',
      label: 'Password',
      placeholder: 'Create a password',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::PASSWORD,
      name: 'confirm_password',
      id: 'confirm_password',
      label: 'Confirm Password',
      placeholder: 'Confirm your password',
      required: true,
  )) ?>

  <div style="display: flex; align-items: flex-start; gap: var(--fc-spacing-2); font-size: var(--fc-font-size-sm);">
    <input type="checkbox" name="terms" id="terms" required
      class="fc-checkbox" style="margin-top: 2px;">
    <label for="terms" style="color: var(--fc-gray-600);">
      I agree to the <a href="#" class="fc-link">Terms of Service</a> and <a href="#" class="fc-link">Privacy Policy</a>
    </label>
  </div>

  <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
      variant: ButtonVariant::PRIMARY,
      size: ButtonSize::LG,
      block: true,
  ), slots: [
      'children' => 'Create account',
  ]) ?>
<?= form_close() ?>
