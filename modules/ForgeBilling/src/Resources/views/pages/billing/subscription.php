<?php

use Forge\Core\Helpers\Flash;

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Subscription',
];

$subscription = $data['subscription'] ?? null;
?>
<?= component('ForgeComponents:alert') ?>

<div class="page-header">
  <div>
    <div class="page-header__title">Subscription</div>
    <div class="page-header__subtitle">Manage your current subscription</div>
  </div>
</div>

<?php if (!$subscription): ?>
                  <div class="card">
                    <div class="card__body">
                      <div class="empty-state">
                        <div class="empty-state__icon">
                          <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="margin:0 auto">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                          </svg>
                        </div>
                        <div class="empty-state__text">No active subscription</div>
                        <div class="empty-state__subtext">You don't have a subscription. Choose a plan to get started.</div>
                        <a href="/billing/plans" class="btn btn--primary">View Plans</a>
                      </div>
                    </div>
                  </div>
<?php else: ?>
                  <div class="two-col">
                    <div class="card">
                      <div class="card__header">
                        <span class="card__title">Plan Details</span>
                      </div>
                      <div class="card__body subscription-detail">
                        <div class="detail-row">
                          <span class="detail-row__label">Plan</span>
                          <span class="detail-row__value"><?= htmlspecialchars($subscription->plan->name) ?></span>
                        </div>
                        <div class="detail-row">
                          <span class="detail-row__label">Amount</span>
                          <span class="detail-row__value">
                            <?= htmlspecialchars($subscription->plan->currency) ?>                 <?= number_format($subscription->plan->amount, 2) ?>
                            /<?= htmlspecialchars($subscription->plan->interval) ?>
                          </span>
                        </div>
                        <div class="detail-row">
                          <span class="detail-row__label">Status</span>
                          <span class="detail-row__value">
                            <?= component(name: 'ForgeBilling:subscription-status', props: [
                                'status' => $subscription->status->value,
                                'trialEndsAt' => $subscription->trialEndsAt,
                            ]) ?>
                          </span>
                        </div>
                        <?php if ($subscription->currentPeriodEndsAt): ?>
                                          <div class="detail-row">
                                            <span class="detail-row__label">Current Period Ends</span>
                                            <span class="detail-row__value"><?= htmlspecialchars($subscription->currentPeriodEndsAt->format('F j, Y')) ?></span>
                                          </div>
                        <?php endif; ?>
                        <?php if ($subscription->trialEndsAt): ?>
                                          <div class="detail-row">
                                            <span class="detail-row__label">Trial Ends</span>
                                            <span class="detail-row__value"><?= htmlspecialchars($subscription->trialEndsAt->format('F j, Y')) ?></span>
                                          </div>
                        <?php endif; ?>
                        <?php if ($subscription->cancelledAt): ?>
                                          <div class="detail-row">
                                            <span class="detail-row__label">Cancelled At</span>
                                            <span class="detail-row__value"><?= htmlspecialchars($subscription->cancelledAt->format('F j, Y')) ?></span>
                                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="card__footer">
                        <a href="/billing/plans" class="btn btn--secondary btn--block">Change Plan</a>
                      </div>
                    </div>

                    <div class="card">
                      <div class="card__header">
                        <span class="card__title">Plan Features</span>
                      </div>
                      <div class="card__body">
                        <?php if (!empty($subscription->plan->features)): ?>
                                          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1rem">
                                            <?php foreach ($subscription->plan->features as $feature): ?>
                                                              <li style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:#374151">
                                                                <svg width="16" height="16" viewBox="0 0 20 20" fill="#16a34a" style="flex-shrink:0">
                                                                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                </svg>
                                                                <?= htmlspecialchars($feature) ?>
                                                              </li>
                                            <?php endforeach; ?>
                                          </ul>
                        <?php else: ?>
                                          <p style="color:#6b7280;font-size:0.875rem">No features listed.</p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <?php if ($subscription->status->value !== 'canceled' && $subscription->status->value !== 'expired'): ?>
                                    <div class="card" style="margin-top:2rem">
                                      <div class="card__header">
                                        <span class="card__title">Danger Zone</span>
                                      </div>
                                      <div class="card__body">
                                        <p style="font-size:0.875rem;color:#6b7280;margin-bottom:1rem">
                                          Once cancelled, your subscription will remain active until the end of the current billing period.
                                        </p>
                                        <form method="POST" action="/billing/subscription/cancel" onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                                          <?= raw(csrf_input()) ?>
                                          <button type="submit" class="btn btn--danger">Cancel Subscription</button>
                                        </form>
                                      </div>
                                    </div>
                  <?php endif; ?>
<?php endif; ?>
