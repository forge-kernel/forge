<?php

$activityProps = [
    'activities' => [
        [
            'title' => 'New user registered',
            'time' => '2 minutes ago',
            'icon' => '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
            'iconBg' => 'bg-blue-100'
        ],
        [
            'title' => 'Order completed',
            'time' => '5 minutes ago',
            'icon' => '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'iconBg' => 'bg-green-100'
        ]
    ]
]
    ?>

<div class="space-y-6">
  <?php if (tenant_can('custom_domain')): ?>
            <div class="settings-panel">
              <h3>Custom Domain Settings</h3>
              <!-- form here -->
            </div>
  <?php else: ?>
            <div class="upgrade-banner">
              Upgrade to Pro to use Custom Domains!
            </div>

            <?php
            $currentUsers = 12;
            $maxUsers = tenant_limit('max_users');
            ?>

            <div class="usage-stats">
              Users:
              <?= $currentUsers ?> /
              <?= $maxUsers === PHP_INT_MAX ? 'Unlimited' : $maxUsers ?>
            </div>

  <?php endif; ?>

  <?= component('ForgeComponents:admin/stats', [
      'stats' => $stats ?? [],
      'columns' => 4
  ]) ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?= component('ForgeComponents:admin/card', [
        'title' => 'Recent Activity',
        'slots' => [
            'default' => component('ForgeComponents:admin/activity-list', $activityProps)
        ]
    ]) ?>

    <?= component('ForgeComponents:admin/card', [
        'title' => 'Quick Actions',
        'slots' => [
            'default' => component('ForgeComponents:admin/quick-actions', [
                'actions' => [
                    ['variant' => 'primary', 'label' => 'Add User'],
                    ['variant' => 'secondary', 'label' => 'View Reports'],
                    ['variant' => 'outline', 'label' => 'Export Data'],
                    ['variant' => 'ghost', 'label' => 'Settings']
                ],
                'columns' => 2
            ])
        ]
    ]) ?>
  </div>
</div>
