<?php

use Modules\ForgeComponents\Definitions\ButtonDefinition;
use Modules\ForgeComponents\Definitions\InputDefinition;
use Modules\ForgeComponents\Enums\ButtonSize;
use Modules\ForgeComponents\Enums\ButtonVariant;
use Modules\ForgeComponents\Enums\InputType;

?>

<?= form_open(attrs: ["class" => "fc-stack fc-stack--md"]) ?>
  <p style="color: var(--fc-gray-600); font-size: var(--fc-font-size-sm); margin-bottom: var(--fc-spacing-4);">
    Enter your email address and we'll send you a link to reset your password.
  </p>

  <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
      type: InputType::EMAIL,
      name: 'email',
      id: 'email',
      label: 'Email',
      placeholder: 'Enter your email',
      required: true,
  )) ?>

  <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
      variant: ButtonVariant::PRIMARY,
      size: ButtonSize::LG,
      block: true,
  ), slots: [
      'children' => 'Send reset link',
  ]) ?>
<?= form_close() ?>
