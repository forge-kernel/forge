<?php

use Modules\ForgeRouter\Http\Request;
use Forge\Core\DI\Container;

/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$parentLayout = 'ForgeBilling:root';

$request = Container::getInstance()->get(Request::class);
$currentUri = $request->getUri();
$normalized = rtrim(parse_url($currentUri, PHP_URL_PATH), '/');
?>
<div class="billing-layout">
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleBillingSidebar()"></div>

  <aside class="billing-sidebar" id="billingSidebar">
    <div class="billing-sidebar__header">
      <a href="/billing" class="billing-sidebar__brand">
        <span class="billing-sidebar__brand-icon">B</span>
        Billing
      </a>
    </div>
    <nav class="billing-sidebar__nav">
      <?= component(name: 'ForgeBilling:billing-nav', props: ['activePath' => $normalized]) ?>
    </nav>
  </aside>

  <div class="billing-main">
    <header class="billing-main__header">
      <div style="display:flex;align-items:center;gap:0.75rem">
        <button class="billing-main__menu-toggle" onclick="toggleBillingSidebar()" aria-label="Toggle menu">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5h14a1 1 0 110 2H3a1 1 0 010-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2zm0 4h14a1 1 0 110 2H3a1 1 0 110-2z"/></svg>
        </button>
        <h1 style="font-size:1.125rem;font-weight:600;color:#111827;"><?= $layoutProps['title'] ?? "Billing" ?></h1>
      </div>
    </header>

    <main class="billing-main__content">
      <div class="billing-main__inner">
        <?= $content ?>
      </div>
    </main>
  </div>
</div>

<script>
  function toggleBillingSidebar() {
    document.getElementById('billingSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
  }
</script>
