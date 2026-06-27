<?php
/**
 * @var array $props
 * @var array $slots
 *
 * Props:
 * - plan: BillingPlan DTO
 * - isSubscribed: bool
 * - subscribeUrl: string
 */
$plan = $props['plan'];
$isSubscribed = $props['isSubscribed'] ?? false;
$subscribeUrl = $props['subscribeUrl'] ?? '';

$formattedAmount = number_format($plan->amount, 2);
$intervalLabel = match ($plan->interval) {
  'month', 'monthly' => '/month',
  'year', 'yearly' => '/year',
  'week', 'weekly' => '/week',
  'day', 'daily' => '/day',
  default => '/' . $plan->interval,
};
?>
<div class="card plan-card" style="display:flex;flex-direction:column">
  <div class="card__body" style="flex:1">
    <h3 style="font-size:1.125rem;font-weight:600;color:#111827;margin-bottom:0.25rem">
      <?= htmlspecialchars($plan->name) ?>
    </h3>
    <div style="margin-bottom:1.5rem">
      <span style="font-size:2.25rem;font-weight:700;color:#111827"><?= htmlspecialchars($plan->currency) ?> <?= $formattedAmount ?></span>
      <span style="font-size:0.875rem;color:#6b7280"><?= $intervalLabel ?></span>
    </div>
    <?php if (!empty($plan->features)): ?>
      <ul style="list-style:none;padding:0;margin:0 0 1.5rem 0;display:flex;flex-direction:column;gap:0.75rem">
        <?php foreach ($plan->features as $feature): ?>
          <li style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:#374151">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="#16a34a" style="flex-shrink:0">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            <?= htmlspecialchars($feature) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card__footer">
    <?php if ($isSubscribed): ?>
      <span style="display:block;text-align:center;font-size:0.875rem;color:#16a34a;font-weight:500">Current Plan</span>
    <?php else: ?>
      <form method="POST" action="<?= htmlspecialchars($subscribeUrl) ?>">
        <?= raw(csrf_input()) ?>
        <button type="submit" class="btn btn--primary btn--block btn--lg">Subscribe</button>
      </form>
    <?php endif; ?>
  </div>
</div>
