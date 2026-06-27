<?php

use Forge\Core\Helpers\Flash;

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Pricing Plans',
];

$plans = $data['plans'] ?? [];
$subscription = $data['subscription'] ?? null;
$currentPlanId = $subscription?->plan->id;
?>
<?= component('ForgeComponents:alert') ?>

<div class="page-header">
  <div>
    <div class="page-header__title">Pricing Plans</div>
    <div class="page-header__subtitle">Choose the plan that fits your needs</div>
  </div>
</div>

<?php if (empty($plans)): ?>
                          <div class="card">
                            <div class="card__body">
                              <div class="empty-state">
                                <div class="empty-state__icon">
                                  <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="margin:0 auto">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5z" clip-rule="evenodd"/>
                                  </svg>
                                </div>
                                <div class="empty-state__text">No plans available</div>
                                <div class="empty-state__subtext">Plans have not been configured yet. Please check back later.</div>
                              </div>
                            </div>
                          </div>
<?php else: ?>
                          <div class="grid-3">
                            <?php foreach ($plans as $plan): ?>
                                                      <?php if ($plan->isActive): ?>
                                                                                <?= component(name: 'ForgeBilling:plan-card', props: [
                                                                                    'plan' => $plan,
                                                                                    'isSubscribed' => $plan->id === $currentPlanId,
                                                                                    'subscribeUrl' => '/billing/plans/' . htmlspecialchars($plan->id) . '/subscribe',
                                                                                ]) ?>
                                                      <?php endif; ?>
                            <?php endforeach; ?>
                          </div>
<?php endif; ?>
