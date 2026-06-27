<?php

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Invoices',
];

$invoices = $data['invoices'] ?? [];
?>
<div class="page-header">
  <div>
    <div class="page-header__title">Invoices</div>
    <div class="page-header__subtitle">View and manage your invoices</div>
  </div>
</div>

<div class="card">
  <?php if (empty($invoices)): ?>
                <div class="card__body">
                  <div class="empty-state">
                    <div class="empty-state__icon">
                      <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="margin:0 auto">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="empty-state__text">No invoices yet</div>
                    <div class="empty-state__subtext">Invoices will appear here once they are generated.</div>
                  </div>
                </div>
  <?php else: ?>
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
                        <?php foreach ($invoices as $invoice): ?>
                                      <?= component(name: 'ForgeBilling:invoice-row', props: [
                                          'invoice' => $invoice,
                                          'detailUrl' => '/billing/invoices/' . htmlspecialchars($invoice->id),
                                      ]) ?>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
  <?php endif; ?>
</div>
