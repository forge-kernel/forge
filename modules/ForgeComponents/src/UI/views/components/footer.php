<?php
use Modules\ForgeComponents\Definitions\FooterDefinition;
/** @var FooterDefinition $props */
?>
<footer class="fc-footer">
  <div class="fc-footer__container">
    <?php if ($props->text): ?>
          <p class="fc-footer__text"><?= e($props->text) ?></p>
    <?php endif; ?>
    <?php if (!empty($props->links)): ?>
          <div class="fc-footer__links">
            <?php foreach ($props->links as $link): ?>
              <a href="<?= e($link->href) ?>" class="fc-link fc-link--sm"><?= e($link->label) ?></a>
            <?php endforeach; ?>
          </div>
    <?php endif; ?>
    <?php if ($props->copyright): ?>
          <p class="fc-footer__copyright"><?= e($props->copyright) ?></p>
    <?php endif; ?>
  </div>
</footer>
