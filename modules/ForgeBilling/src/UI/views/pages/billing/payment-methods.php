<?php

use Forge\Core\Helpers\Flash;

/**
 * @var array $data
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

$layoutProps = [
    'title' => 'Payment Methods',
];

$methods = $data['methods'] ?? [];
?>
<?= component('ForgeComponents:alert') ?>

<div class="page-header">
  <div>
    <div class="page-header__title">Payment Methods</div>
    <div class="page-header__subtitle">Manage your saved payment methods</div>
  </div>
</div>

<?php if (empty($methods)): ?>
                  <div class="card" style="margin-bottom:2rem">
                    <div class="card__body">
                      <div class="empty-state">
                        <div class="empty-state__icon">
                          <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="margin:0 auto">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                          </svg>
                        </div>
                        <div class="empty-state__text">No payment methods saved</div>
                        <div class="empty-state__subtext">Add a payment method to automate billing.</div>
                      </div>
                    </div>
                  </div>
<?php else: ?>
                  <div style="display:flex;flex-direction:column;gap:var(--spacing-4);margin-bottom:2rem">
                    <?php foreach ($methods as $method): ?>
                                      <?= component(name: 'ForgeBilling:payment-method-card', props: [
                                          'method' => $method,
                                          'deleteUrl' => '/billing/payment-methods/' . htmlspecialchars($method->id) . '/delete',
                                      ]) ?>
                    <?php endforeach; ?>
                  </div>
<?php endif; ?>

<hr class="divider">

<h3 style="font-size:1.125rem;font-weight:600;margin-bottom:1.5rem;margin-top:1.5rem">Add Payment Method</h3>
<div class="card" style="max-width:480px">
  <div class="card__body">
    <form method="POST" action="/billing/payment-methods">
      <?= raw(csrf_input()) ?>
      <div class="form-group">
        <label class="form-label" for="type">Payment Type</label>
        <select name="type" id="type" class="form-input" required>
          <option value="card">Credit Card</option>
          <option value="paypal">PayPal</option>
          <option value="bank_transfer">Bank Transfer</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="last_four">Last Four Digits</label>
        <input type="text" name="last_four" id="last_four" class="form-input" maxlength="4" pattern="[0-9]{4}" placeholder="1234" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="provider_name">Provider</label>
        <input type="text" name="provider_name" id="provider_name" class="form-input" value="manual" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="token">Token</label>
        <input type="text" name="token" id="token" class="form-input" placeholder="pm_..." required>
      </div>
      <button type="submit" class="btn btn--primary">Add Payment Method</button>
    </form>
  </div>
</div>
