<?php
/**
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $data
 */
use Modules\ForgeComponents\Definitions\FooterDefinition;
use Modules\ForgeComponents\Definitions\NavbarDefinition;
use Modules\ForgeComponents\Definitions\NavbarLinkDefinition;

$currentUser = $data['currentUser'] ?? null;

$navbar = new NavbarDefinition(
    brand: 'Forge',
    brandHref: '/',
    links: [
        new NavbarLinkDefinition(label: 'Features', href: '/#features'),
        new NavbarLinkDefinition(label: 'Docs', href: 'https://forge-kernel.github.io/'),
    ],
    user: $currentUser ? [
        'identifier' => $currentUser->getIdentifier(),
        'logoutUrl' => '/auth/logout',
    ] : null,
    authLinkText: 'Sign in',
    authLinkHref: '/auth/login',
    registerLinkText: 'Get started',
    registerLinkHref: '/auth/register',
);

$footer = new FooterDefinition(
    text: 'Forge — A PHP Kernel for Builders.',
    links: [
        new NavbarLinkDefinition(label: 'Documentation', href: 'https://forge-kernel.github.io/'),
        new NavbarLinkDefinition(label: 'GitHub', href: 'https://github.com/forge-kernel'),
    ],
    copyright: '© ' . date('Y') . ' Forge. MIT License.',
);

$layoutProps = array_merge($layoutProps ?? [], [
    'title' => 'Forge — Welcome',
    'navbar' => $navbar,
    'footer' => $footer,
]);

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') .
        "\n" . '<link rel="stylesheet" href="/assets/modules/forge-landing/css/landing.css">',
]);
?>

<section class="fc-landing">
  <div class="fc-landing__container">
    <div class="fc-landing__flash">
      <?= component('ForgeComponents:alert') ?>
    </div>

    <div class="fc-landing__hero">
      <div class="fc-landing__hero-badge">
        <span class="fc-badge fc-badge--success">Open Source</span>
        <span class="fc-badge fc-badge--default">MIT License</span>
      </div>

      <h1 class="fc-landing__hero-title">
        Build <span class="fc-landing__hero-highlight">PHP applications</span><br>
        without the bloat.
      </h1>

      <p class="fc-landing__hero-subtitle">
        A modular, dependency-free PHP kernel with pluggable capabilities.
        Database, ORM, authentication, storage — plug in what you need, when you need it.
      </p>

      <div class="fc-landing__hero-actions">
        <a href="/auth/register" class="fc-btn fc-btn--primary fc-btn--lg">
          Get started free
        </a>
        <a href="https://forge-kernel.github.io/" class="fc-btn fc-btn--secondary fc-btn--lg">
          Read the docs
        </a>
      </div>
    </div>

    <div class="fc-landing__features" id="features">
      <div class="fc-landing__feature-grid">
        <div class="fc-card">
          <div class="fc-card__body">
            <h3 class="fc-card__title" style="margin-bottom: var(--fc-spacing-2);">Modular by design</h3>
            <p style="font-size: var(--fc-font-size-sm); color: var(--fc-color-text-secondary); line-height: var(--fc-leading-relaxed);">
              Every capability is a module. Install only what you need — auth, ORM, router, views, and more.
            </p>
          </div>
        </div>

        <div class="fc-card">
          <div class="fc-card__body">
            <h3 class="fc-card__title" style="margin-bottom: var(--fc-spacing-2);">Zero dependencies</h3>
            <p style="font-size: var(--fc-font-size-sm); color: var(--fc-color-text-secondary); line-height: var(--fc-leading-relaxed);">
              No Composer, no NPM, no build step. Pure PHP with a custom autoloader and vanilla CSS design system.
            </p>
          </div>
        </div>

        <div class="fc-card">
          <div class="fc-card__body">
            <h3 class="fc-card__title" style="margin-bottom: var(--fc-spacing-2);">Built for builders</h3>
            <p style="font-size: var(--fc-font-size-sm); color: var(--fc-color-text-secondary); line-height: var(--fc-leading-relaxed);">
              CLI generators, interactive scaffolding, and a component system that gets out of your way.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
