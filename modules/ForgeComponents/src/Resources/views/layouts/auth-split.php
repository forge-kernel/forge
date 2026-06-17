<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 * @var string $parentLayout
 */

use Forge\Core\Helpers\Framework;

$parentLayout = 'ForgeComponents:root';

$layoutProps = array_merge($layoutProps ?? [], [
    'bodyClass' => 'h-full fc-auth-split',
]);

$layoutSections = array_merge($layoutSections ?? [], [
    'head_end' => ($layoutSections['head_end'] ?? '') . "\n" . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">',
]);

$infoSide = $layoutProps['infoSide'] ?? 'left';
?>
<div class="fc-auth-split__inner<?= $infoSide === 'right' ? ' fc-auth-split__inner--reverse' : '' ?>">
  <div class="fc-auth-split__side">
    <?php if (isset($layoutProps['info'])): ?>
              <?= $layoutProps['info'] ?>
    <?php else: ?>
              <div>
                <div class="fc-auth-split__brand">
                  <div class="fc-auth-split__logo"><i class="fa-solid fa-cube"></i></div>
                  <h1 class="fc-auth-split__title">Forge</h1>
                </div>

                <div class="fc-auth-split__content-area">
                  <div>
                    <h2 class="fc-auth-split__heading">Forge Kernel</h2>
                    <p class="fc-auth-split__subtext">A minimal, dependency-free PHP kernel with pluggable capabilities</p>
                  </div>

                  <div class="fc-auth-split__description">
                    <p class="fc-auth-split__body-text">
                      Capabilities, not built-ins. Database, ORM, authentication, storage — these aren't built into the kernel. They're capabilities you plug in via modules when you need them.
                    </p>

                    <p class="fc-auth-split__quote">&ldquo;You're not a user here. You're a builder.&rdquo;</p>
                  </div>
                </div>
              </div>

              <div class="fc-auth-split__meta">
                <div class="fc-auth-split__meta-row">
                  <span class="fc-auth-split__meta-label">Kernel Version</span>
                  <span class="fc-auth-split__meta-value">v<?= htmlspecialchars(Framework::version()) ?></span>
                </div>
                <div class="fc-auth-split__meta-row">
                  <span class="fc-auth-split__meta-label">License</span>
                  <span class="fc-auth-split__meta-value">MIT</span>
                </div>
                <div class="fc-auth-split__meta-row">
                  <span class="fc-auth-split__meta-label">Server Time</span>
                  <span class="fc-auth-split__meta-value"><?= date('H:i:s') ?></span>
                </div>
              </div>
    <?php endif; ?>
  </div>

  <div class="fc-auth-split__main">
    <div class="fc-auth-split__content"><?= $content ?></div>
  </div>
</div>
