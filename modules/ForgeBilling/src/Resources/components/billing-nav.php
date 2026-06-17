<?php

/**
 * @var array $props
 * @var string $props['activePath']
 */

$normalized = $props['activePath'];

$navItems = [
    [
        'label' => 'Overview',
        'href' => '/billing',
        'icon' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>',
    ],
    [
        'label' => 'Plans',
        'href' => '/billing/plans',
        'icon' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>',
    ],
    [
        'label' => 'Invoices',
        'href' => '/billing/invoices',
        'icon' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>',
    ],
    [
        'label' => 'Payment Methods',
        'href' => '/billing/payment-methods',
        'icon' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>',
    ],
    [
        'label' => 'Subscription',
        'href' => '/billing/subscription',
        'icon' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
    ],
];
?>
<div class="billing-sidebar__section-title">Billing</div>
<ul class="billing-sidebar__nav-list">
  <?php foreach ($navItems as $item): ?>
    <?php
    $isActive = $normalized === $item['href'];
    ?>
    <li class="billing-sidebar__nav-item">
      <a href="<?= $item['href'] ?>"
         class="billing-sidebar__nav-link<?= $isActive ? ' billing-sidebar__nav-link--active' : '' ?>">
        <span class="billing-sidebar__nav-icon"><?= raw($item['icon']) ?></span>
        <?= htmlspecialchars($item['label']) ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
