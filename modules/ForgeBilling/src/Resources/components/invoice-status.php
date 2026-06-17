<?php
/**
 * @var array $props
 *
 * Props:
 * - status: string (pending|paid|overdue|canceled|refunded)
 */
$status = $props['status'] ?? 'pending';
$label = match ($status) {
  'paid' => 'Paid',
  'pending' => 'Pending',
  'overdue' => 'Overdue',
  'canceled' => 'Canceled',
  'refunded' => 'Refunded',
  default => ucfirst($status),
};
?>
<span class="badge badge--<?= htmlspecialchars($status) ?>">
  <?= htmlspecialchars($label) ?>
</span>
