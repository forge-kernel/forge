<?php
use App\Modules\ForgeComponents\Definitions\NavbarDefinition;
/** @var NavbarDefinition $props */
?>
<nav class="fc-navbar">
  <div class="fc-navbar__container">
    <a href="<?= e($props->brandHref) ?>" class="fc-navbar__brand">
      <span class="fc-navbar__brand-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="fc-navbar__brand-svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg></span>
      <span class="fc-navbar__brand-text"><?= e($props->brand) ?></span>
    </a>

    <div class="fc-navbar__links">
      <?php foreach ($props->links as $link): ?>
        <a href="<?= e($link->href) ?>" class="fc-navbar__link<?= $link->active ? ' fc-navbar__link--active' : '' ?>">
          <?= e($link->label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($props->user): ?>
    <div class="fc-navbar__actions">
      <span class="fc-navbar__user"><?= e($props->user['identifier'] ?? '') ?></span>
      <form method="POST" action="<?= e($props->user['logoutUrl'] ?? '/auth/logout') ?>" style="display:inline">
        <?= raw(csrf_input()) ?>
        <button type="submit" class="fc-btn fc-btn--ghost fc-btn--sm">Sign out</button>
      </form>
    </div>
    <?php elseif ($props->showAuthButtons): ?>
    <div class="fc-navbar__actions">
      <?php if ($props->authLinkText): ?>
        <a href="<?= e($props->authLinkHref) ?>" class="fc-btn fc-btn--ghost fc-btn--sm"><?= e($props->authLinkText) ?></a>
      <?php endif; ?>
      <?php if ($props->registerLinkText): ?>
        <a href="<?= e($props->registerLinkHref) ?>" class="fc-btn fc-btn--primary fc-btn--sm"><?= e($props->registerLinkText) ?></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</nav>
