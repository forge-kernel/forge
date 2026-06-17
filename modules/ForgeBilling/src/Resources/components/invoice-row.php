<?php
/**
 * @var array $props
 *
 * Props:
 * - invoice: Invoice DTO
 * - detailUrl: string
 */
$invoice = $props['invoice'];
$detailUrl = $props['detailUrl'] ?? '';
?>
<tr>
  <td>
    <a href="<?= htmlspecialchars($detailUrl) ?>" class="table__link">
      <?= htmlspecialchars($invoice->number) ?>
    </a>
  </td>
  <td><?= htmlspecialchars($invoice->createdAt?->format('M j, Y') ?? '-') ?></td>
  <td><?= htmlspecialchars($invoice->createdAt?->format('g:i A') ?? '-') ?></td>
  <td><?= htmlspecialchars($invoice->currency) ?> <?= number_format($invoice->amount, 2) ?></td>
  <td>
    <?= component(name: 'ForgeBilling:invoice-status', props: ['status' => $invoice->status->value]) ?>
  </td>
  <td>
    <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn btn--secondary btn--sm">View</a>
  </td>
</tr>
