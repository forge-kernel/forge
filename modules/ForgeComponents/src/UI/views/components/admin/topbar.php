<header class="fc-admin__topbar">
  <div class="fc-admin__topbar-left">
    <button type="button" class="fc-admin__topbar-toggle" onclick="fcToggleSidebar()" aria-label="Toggle sidebar">
      <?= component(name: 'ForgeComponents:admin/icon', props: new \Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: 'bars-3')) ?>
    </button>
    <?php if (slot('search')): ?>
      <?= slot('search') ?>
    <?php else: ?>
      <div class="fc-admin__search">
        <span class="fc-admin__search-icon"><?= component(name: 'ForgeComponents:admin/icon', props: new \Modules\ForgeComponents\Definitions\Admin\IconDefinition(name: 'magnifying-glass')) ?></span>
        <input type="search" class="fc-admin__search-input" placeholder="Search...">
      </div>
    <?php endif; ?>
  </div>
  <div class="fc-admin__topbar-right">
    <?= slot('actions') ?>
    <?= slot('user') ?>
  </div>
</header>
