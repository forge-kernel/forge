<?php

/** @var \Modules\AppAuth\Definitions\AuthCardDefinition $props */

?>
<div class="fc-auth-card">
  <div class="fc-auth-card__header">
    <h1 class="fc-auth-card__heading"><?= $props->heading ?></h1>
    <?php if ($props->subtitle): ?>
          <p class="fc-auth-card__subtitle"><?= $props->subtitle ?></p>
    <?php endif; ?>
  </div>

  <?= component('ForgeComponents:alert') ?>

  <div class="fc-auth-card__body">
    <?= component("AppAuth:{$props->form}") ?>

    <div class="fc-auth-card__footer">
      <p><?= $props->footerLink['text'] ?> <a href="<?= $props->footerLink['href'] ?>"><?= $props->footerLink['label'] ?></a></p>
    </div>
  </div>

  <p class="fc-auth-card__terms"><?= $props->footerText ?></p>
</div>
