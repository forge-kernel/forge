<?php

use Modules\ForgeComponents\Definitions\ButtonDefinition;
use Modules\ForgeComponents\Definitions\InputDefinition;
use Modules\ForgeComponents\Enums\ButtonSize;
use Modules\ForgeComponents\Enums\ButtonVariant;
use Modules\ForgeComponents\Enums\InputType;

/** @var array $user */
/** @var array $profile */

$layoutSections['head_end'] = ($layoutSections['head_end'] ?? '') . "\n" . '<link rel="stylesheet" href="/assets/modules/forge-components/css/forge-components/_admin.css">';
$layoutSections['breadcrumbs'] ??= [];
$layoutSections['breadcrumbs'][] = ['label' => 'Profile', 'url' => '/profile'];

?>
<div class="fc-admin-card">
  <div class="fc-admin-card__header">
    <h2 class="fc-admin-card__title">Edit Profile</h2>
  </div>
  <div class="fc-admin-card__body">
    <?php if ($layoutSections['messages'] ?? false): ?>
      <?= component('ForgeComponents:alert') ?>
    <?php endif; ?>

    <?= form_open(attrs: ["class" => "fc-stack fc-stack--md"]) ?>
      <div class="fc-split fc-split--2">
        <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
            type: InputType::TEXT,
            name: 'first_name',
            id: 'first_name',
            label: 'First Name',
            value: $profile['first_name'] ?? '',
            required: true,
        )) ?>

        <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
            type: InputType::TEXT,
            name: 'last_name',
            id: 'last_name',
            label: 'Last Name',
            value: $profile['last_name'] ?? '',
        )) ?>
      </div>

      <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
          type: InputType::EMAIL,
          name: 'email',
          id: 'email',
          label: 'Email',
          value: $profile['email'] ?? $user['email'] ?? '',
          required: true,
      )) ?>

      <?= component(name: 'ForgeComponents:input', props: new InputDefinition(
          type: InputType::TEL,
          name: 'phone',
          id: 'phone',
          label: 'Phone',
          value: $profile['phone'] ?? '',
          placeholder: '+1 (555) 000-0000',
      )) ?>

      <?= component(name: 'ForgeComponents:button', props: new ButtonDefinition(
          variant: ButtonVariant::PRIMARY,
          size: ButtonSize::LG,
          block: true,
      ), slots: [
          'children' => 'Save changes',
      ]) ?>
    <?= form_close() ?>
  </div>
</div>
