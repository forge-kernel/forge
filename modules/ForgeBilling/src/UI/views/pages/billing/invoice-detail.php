<?php

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Invoice Detail',
];

$invoice = $data['invoice'] ?? null;
$items = $data['items'] ?? [];

if (!$invoice):
    ?>
              <div class="card">
                <div class="card__body">
                  <div class="empty-state">
                    <div class="empty-state__text">Invoice not found</div>
                    <div class="empty-state__subtext">The requested invoice could not be found.</div>
                    <a href="/billing/invoices" class="btn btn--secondary">Back to Invoices</a>
                  </div>
                </div>
              </div>
            <?php
            return;
endif;
?>

<div class="page-header">
  <div>
    <div class="page-header__title">Invoice <?= htmlspecialchars($invoice->number) ?></div>
    <div class="page-header__subtitle">
      <?= htmlspecialchars($invoice->createdAt?->format('F j, Y') ?? '-') ?>
    </div>
  </div>
  <div class="page-header__actions">
    <?= component(name: 'ForgeBilling:invoice-status', props: ['status' => $invoice->status->value]) ?>
    <a href="/billing/invoices" class="btn btn--secondary">Back</a>
  </div>
</div>

<div class="card invoice-detail">
  <div class="card__body">
    <div class="invoice-header">
      <div>
        <div class="invoice-number"><?= htmlspecialchars($invoice->number) ?></div>
        <div class="invoice-meta">
          Created: <?= htmlspecialchars($invoice->createdAt?->format('F j, Y \a\t g:i A') ?? '-') ?>
        </div>
        <?php if ($invoice->paidAt): ?>
                      <div class="invoice-meta">
                        Paid: <?= htmlspecialchars($invoice->paidAt->format('F j, Y \a\t g:i A')) ?>
                      </div>
        <?php endif; ?>
        <?php if ($invoice->dueDate): ?>
                      <div class="invoice-meta">
                        Due: <?= htmlspecialchars($invoice->dueDate->format('F j, Y')) ?>
                      </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($items)): ?>
                  <hr class="divider">
                  <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Line Items</h3>
                  <div class="table-container">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Description</th>
                          <th>Qty</th>
                          <th>Amount</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $item): ?>
                                      <tr>
                                        <td><?= htmlspecialchars($item->description) ?></td>
                                        <td><?= (int) $item->quantity ?></td>
                                        <td><?= htmlspecialchars($item->currency) ?>                         <?= number_format($item->amount, 2) ?></td>
                                        <td><?= htmlspecialchars($item->currency) ?>                         <?= number_format($item->amount * $item->quantity, 2) ?></td>
                                      </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
    <?php endif; ?>

    <div class="invoice-total">
      <div class="invoice-total__label">Total</div>
      <div class="invoice-total__amount">
        <?= htmlspecialchars($invoice->currency) ?> <?= number_format($invoice->amount, 2) ?>
      </div>
    </div>
  </div>
</div>
