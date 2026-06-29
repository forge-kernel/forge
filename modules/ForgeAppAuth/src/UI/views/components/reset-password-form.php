<?php

use Modules\ForgeComponents\Definitions\ButtonDefinition;
use Modules\ForgeComponents\Definitions\InputDefinition;
use Modules\ForgeComponents\Enums\ButtonSize;
use Modules\ForgeComponents\Enums\ButtonVariant;
use Modules\ForgeComponents\Enums\InputType;

/** @var array $props */
?>

<?= form_open(attrs: ["class" => "fc-stack fc-stack--md"]) ?>
  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::HIDDEN,
      name: 'token',
      id: 'token',
      value: $props['token'] ?? '',
  )) ?>

  <p style="color: var(--fc-gray-600); font-size: var(--fc-font-size-sm); margin-bottom: var(--fc-spacing-4);">
    Choose a new password for your account.
  </p>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::PASSWORD,
      name: 'password',
      id: 'password',
      label: 'New Password',
      placeholder: 'Enter your new password',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::PASSWORD,
      name: 'confirm_password',
      id: 'confirm_password',
      label: 'Confirm Password',
      placeholder: 'Confirm your new password',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
      variant: ButtonVariant::PRIMARY,
      size: ButtonSize::LG,
      block: true,
  ), slots: [
      'children' => 'Reset password',
  ]) ?>
<?= form_close() ?>
