<?php
$tempMenu = [
    [
        'isActive' => true,
        'target' => 'nexus/dashboard',
        'label' => 'Dashboard',
        'icon' => 'fa-gauge-high'
    ],
    [
        'isActive' => false,
        'target' => '#',
        'label' => 'Users',
        'icon' => 'fa-users'
    ],
    [
        'isActive' => false,
        'target' => 'nexus/schemas',
        'label' => 'Schemas',
        'icon' => 'fa-chart-line'
    ],
    [
        'isActive' => false,
        'target' => '#',
        'label' => 'Settings',
        'icon' => 'fa-gear'
    ],
];

?>
<!-- Sidebar Navigation -->
<aside class="sidebar">
    <?= component(name: 'ForgeNexus:sidebar/header', fromModule: true, props: ['name' => 'Nexus CMS']) ?>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <h2 class="nav-section-title">Platform</h2>
            <ul class="nav-list">
                <?php foreach ($tempMenu as $item): ?>
                    <?= component(
                        name: 'ForgeNexus:sidebar/sidebar-item',
                        fromModule: true,
                        props: [
                            'isActive' => (bool) $item['isActive'],
                            'target' => $item['target'],
                            'label' => $item['label'],
                            'icon' => $item['icon']
                        ]
                    )
                        ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <div class="sidebar-footer">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fa-solid fa-code-branch"></i>
                    <span>Repository</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fa-solid fa-book"></i>
                    <span>Documentation</span>
                </a>
            </li>
        </ul>

        <div class="user-profile">
            <div class="avatar">
                <span>TO</span>
            </div>
            <div class="user-info">
                <span class="user-name">Jeremias Nunez</span>
            </div>
            <button class="user-menu-toggle" aria-label="User menu">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
        </div>
    </div>
</aside>