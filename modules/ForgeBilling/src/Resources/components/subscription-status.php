<?php
/**
 * @var array $props
 *
 * Props:
 * - status: string (active|trial|past_due|canceled|expired)
 * - trialEndsAt: ?DateTimeImmutable
 */
$status = $props['status'] ?? '';
$trialEndsAt = $props['trialEndsAt'] ?? null;
$label = match ($status) {
  'active' => 'Active',
  'trial' => 'Trial',
  'past_due' => 'Past Due',
  'canceled' => 'Canceled',
  'expired' => 'Expired',
  default => ucfirst($status),
};

$cssClass = match ($status) {
  'active' => 'badge--active',
  'trial' => 'badge--trial',
  'past_due' => 'badge--past_due',
  'canceled' => 'badge--canceled',
  'expired' => 'badge--expired',
  default => 'badge--pending',
};
?>
<span class="badge <?= $cssClass ?>">
  <?= htmlspecialchars($label) ?>
</span>
<?php if ($status === 'trial' && $trialEndsAt): ?>
  <span style="font-size:0.75rem;color:#6b7280;margin-left:0.5rem">
    (ends <?= htmlspecialchars($trialEndsAt->format('M j, Y')) ?>)
  </span>
<?php endif; ?>
