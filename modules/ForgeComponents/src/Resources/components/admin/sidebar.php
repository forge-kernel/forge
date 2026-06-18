<?php

/** @var \App\Modules\ForgeComponents\Definitions\Admin\SidebarDefinition $props */

$abbr = '';
if ($props->brand) {
    $words = preg_split('/\s+/', trim($props->brand));
    $abbr = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}

?>
<aside id="fc-sidebar" class="fc-admin__sidebar">
  <a href="<?= e($props->brandHref) ?>" class="fc-admin__sidebar-brand">
    <?php if ($props->logoUrl): ?>
      <div class="fc-admin__sidebar-logo">
        <img src="<?= e($props->logoUrl) ?>" alt="<?= e($props->brand) ?>">
      </div>
    <?php else: ?>
      <div class="fc-admin__sidebar-logo">
        <?= e($abbr ?: '⬡') ?>
      </div>
    <?php endif; ?>
    <span class="fc-admin__sidebar-brand-text">
      <span class="fc-admin__sidebar-title"><?= e($props->brand) ?></span>
      <?php if ($props->tagline): ?>
        <span class="fc-admin__sidebar-tagline"><?= e($props->tagline) ?></span>
      <?php endif; ?>
    </span>
  </a>

  <nav class="fc-admin__sidebar-nav" aria-label="Main navigation">
    <?php foreach ($props->groups as $group): ?>
      <?= component(name: 'ForgeComponents:admin/nav-group', props: $group) ?>
    <?php endforeach; ?>
  </nav>

  <div class="fc-admin__sidebar-footer">
    <?php if ($props->footerLinks): ?>
      <ul class="fc-admin-nav-group__list">
        <?php foreach ($props->footerLinks as $link): ?>
          <?= component(name: 'ForgeComponents:admin/nav-item', props: $link) ?>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($props->statusLabel): ?>
      <div class="fc-admin__sidebar-status">
        <div class="fc-admin__sidebar-status-dot<?= $props->statusOnline ? ' fc-admin__sidebar-status-dot--online' : '' ?>"></div>
        <div class="fc-admin__sidebar-status-text">
          <span class="fc-admin__sidebar-status-label"><?= e($props->statusLabel) ?></span>
          <?php if ($props->statusOnline): ?>
            <span class="fc-admin__sidebar-status-sublabel">All systems operational</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($props->contextLabel): ?>
      <div class="fc-admin__sidebar-context">
        <div class="fc-admin__sidebar-context-avatar">
          <?= e(strtoupper(substr($props->contextLabel, 0, 2))) ?>
        </div>
        <div class="fc-admin__sidebar-context-text">
          <span class="fc-admin__sidebar-context-label"><?= e($props->contextLabel) ?></span>
          <?php if ($props->contextSubLabel): ?>
            <span class="fc-admin__sidebar-context-sublabel"><?= e($props->contextSubLabel) ?></span>
          <?php endif; ?>
        </div>
        <span class="fc-admin__sidebar-context-chevron">
          <?= component(name: 'ForgeComponents:admin/icon', props: new \App\Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: 'chevron-right')) ?>
        </span>
      </div>
    <?php endif; ?>
  </div>
</aside>

<div id="fc-sidebar-overlay" class="fc-admin__sidebar-overlay" onclick="fcToggleSidebar()"></div>
