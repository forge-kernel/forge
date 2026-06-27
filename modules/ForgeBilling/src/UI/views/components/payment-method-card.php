<?php
/**
 * @var array $props
 *
 * Props:
 * - method: PaymentMethod DTO
 * - deleteUrl: string
 */
$method = $props['method'];
$deleteUrl = $props['deleteUrl'] ?? '';
?>
<div class="payment-method-card<?= $method->isDefault ? ' payment-method-card--default' : '' ?>">
  <div class="payment-method-card__icon">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
      <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
    </svg>
  </div>
  <div class="payment-method-card__info">
    <div class="payment-method-card__type"><?= htmlspecialchars(ucfirst($method->type->value)) ?></div>
    <?php if ($method->lastFour): ?>
      <div class="payment-method-card__last-four">ending in <?= htmlspecialchars($method->lastFour) ?></div>
    <?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:0.75rem">
    <?php if ($method->isDefault): ?>
      <span class="payment-method-card__default-badge">Default</span>
    <?php endif; ?>
    <form method="POST" action="<?= htmlspecialchars($deleteUrl) ?>" onsubmit="return confirm('Remove this payment method?')">
      <?= raw(csrf_input()) ?>
      <input type="hidden" name="_method" value="DELETE">
      <button type="submit" class="btn btn--danger btn--sm">Remove</button>
    </form>
  </div>
</div>
