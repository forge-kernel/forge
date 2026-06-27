<?php

use Forge\Core\Helpers\Flash;

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Dashboard',
];

$subscription = $data['data']['subscription'] ?? null;
$latestInvoice = $data['data']['latestInvoice'] ?? null;
$invoices = $data['data']['invoices'] ?? [];
$plans = $data['data']['plans'] ?? [];
$isActive = $data['data']['isActive'] ?? false;
$onTrial = $data['data']['onTrial'] ?? false;
?>
<?= component('ForgeComponents:alert') ?>

<div class="stats-grid">
  <div class="card stat-card">
    <div>
      <div class="stat-card__label">Current Plan</div>
      <div class="stat-card__value">
        <?php if ($subscription): ?>
                                          <?= htmlspecialchars($subscription->plan->name) ?>
        <?php else: ?>
                                          No plan
        <?php endif; ?>
      </div>
      <?php if ($subscription): ?>
                                        <?= component(name: 'ForgeBilling:subscription-status', props: [
                                            'status' => $subscription->status->value,
                                            'trialEndsAt' => $subscription->trialEndsAt,
                                        ]) ?>
      <?php endif; ?>
    </div>
    <div class="stat-card__icon stat-card__icon--plan">
      <svg width="22" height="22" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
    </div>
  </div>

  <div class="card stat-card">
    <div>
      <div class="stat-card__label">Status</div>
      <div class="stat-card__value">
        <?php if ($isActive): ?>
                                          Active
        <?php elseif ($onTrial): ?>
                                          Trial
        <?php else: ?>
                                          Inactive
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card__icon stat-card__icon--status">
      <svg width="22" height="22" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    </div>
  </div>

  <div class="card stat-card">
    <div>
      <div class="stat-card__label">Latest Invoice</div>
      <div class="stat-card__value">
        <?php if ($latestInvoice): ?>
                                          <?= htmlspecialchars($latestInvoice->currency) ?>                                 <?= number_format($latestInvoice->amount, 2) ?>
        <?php else: ?>
                                          --
        <?php endif; ?>
      </div>
      <?php if ($latestInvoice): ?>
                                        <?= component(name: 'ForgeBilling:invoice-status', props: ['status' => $latestInvoice->status->value]) ?>
      <?php endif; ?>
    </div>
    <div class="stat-card__icon stat-card__icon--invoice">
      <svg width="22" height="22" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
    </div>
  </div>

  <div class="card stat-card">
    <div>
      <div class="stat-card__label">Total Invoices</div>
      <div class="stat-card__value"><?= count($invoices) ?></div>
    </div>
    <div class="stat-card__icon stat-card__icon--payment">
      <svg width="22" height="22" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z" clip-rule="evenodd"/></svg>
    </div>
  </div>
</div>

<?php if (!$subscription): ?>
                                  <div class="card" style="margin-bottom:2rem">
                                    <div class="card__body">
                                      <div style="text-align:center;padding:2rem 1rem">
                                        <div style="font-size:2.5rem;margin-bottom:0.75rem;opacity:0.3">
                                          <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="margin:0 auto">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                          </svg>
                                        </div>
                                        <h2 style="font-size:1.25rem;font-weight:600;color:#111827;margin-bottom:0.5rem">No active subscription</h2>
                                        <p style="color:#6b7280;font-size:0.875rem;margin-bottom:1.5rem">Choose a plan to get started with billing.</p>
                                        <a href="/billing/plans" class="btn btn--primary">View Plans</a>
                                      </div>
                                    </div>
                                  </div>
<?php endif; ?>

<?php if (!empty($invoices)): ?>
                                  <div class="card" style="margin-bottom:2rem">
                                    <div class="card__header">
                                      <span class="card__title">Recent Invoices</span>
                                      <a href="/billing/invoices" class="btn btn--secondary btn--sm">View All</a>
                                    </div>
                                    <div class="card__body" style="padding:0">
                                      <div class="table-container">
                                        <table class="table">
                                          <thead>
                                            <tr>
                                              <th>Invoice</th>
                                              <th>Date</th>
                                              <th>Time</th>
                                              <th>Amount</th>
                                              <th>Status</th>
                                              <th></th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                            <?php foreach (array_slice($invoices, 0, 5) as $invoice): ?>
                                                                              <?= component(name: 'ForgeBilling:invoice-row', props: [
                                                                                  'invoice' => $invoice,
                                                                                  'detailUrl' => '/billing/invoices/' . htmlspecialchars($invoice->id),
                                                                              ]) ?>
                                            <?php endforeach; ?>
                                          </tbody>
                                        </table>
                                      </div>
                                    </div>
                                  </div>
<?php endif; ?>

<div class="quick-actions">
  <a href="/billing/plans" class="quick-action">
    <span class="quick-action__icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
    </span>
    <span class="quick-action__text">View Plans</span>
  </a>
  <a href="/billing/invoices" class="quick-action">
    <span class="quick-action__icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
    </span>
    <span class="quick-action__text">View Invoices</span>
  </a>
  <a href="/billing/payment-methods" class="quick-action">
    <span class="quick-action__icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
    </span>
    <span class="quick-action__text">Payment Methods</span>
  </a>
  <a href="/billing/subscription" class="quick-action">
    <span class="quick-action__icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
    </span>
    <span class="quick-action__text">Subscription</span>
  </a>
</div>
