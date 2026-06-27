<?php
/** @var array<string, mixed> $props */
$route = $props['route'] ?? [];

if (isset($route['error'])): ?>
  <p class="fdb-text--error">Error: <?= htmlspecialchars($route['error']) ?></p>
<?php return; endif;

if (isset($route['message'])): ?>
  <div class="fdb-panel__empty"><?= htmlspecialchars($route['message']) ?></div>
<?php return; endif;

if (empty($route)): ?>
  <div class="fdb-panel__empty">No route information available.</div>
<?php return; endif; ?>
<div class="fdb-router">
  <div class="fdb-router__row">
    <span class="fdb-router__label">URI</span>
    <span class="fdb-router__value">
      <span class="fdb-badge fdb-badge--method fdb-badge--<?= strtolower($route['method'] ?? '') ?>"><?= htmlspecialchars($route['method'] ?? '') ?></span>
      <span class="fdb-router__uri"><?= htmlspecialchars($route['uri'] ?? '') ?></span>
    </span>
  </div>
  <?php $handler = $route['handler'] ?? ''; if (!empty($handler)): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">Endpoint</span>
      <span class="fdb-router__endpoint"><?= htmlspecialchars($handler) ?></span>
    </div>
  <?php endif; ?>
  <?php $middleware = $route['middleware'] ?? []; if (!empty($middleware)): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">Middleware</span>
      <span class="fdb-router__value">
        <?php foreach ($middleware as $m): ?>
          <span class="fdb-badge fdb-badge--info"><?= htmlspecialchars(basename(str_replace('\\', '/', $m))) ?></span>
        <?php endforeach; ?>
      </span>
    </div>
  <?php endif; ?>
  <?php if (isset($route['file'])): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">File</span>
      <span class="fdb-router__value"><?= htmlspecialchars($route['file']) ?></span>
    </div>
  <?php endif; ?>
  <?php if (isset($route['prefix'])): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">Prefix</span>
      <span class="fdb-router__value"><?= htmlspecialchars($route['prefix']) ?></span>
    </div>
  <?php endif; ?>
  <?php if (isset($route['namespace'])): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">Namespace</span>
      <span class="fdb-router__value"><?= htmlspecialchars($route['namespace']) ?></span>
    </div>
  <?php endif; ?>
  <?php if (isset($route['where'])): ?>
    <div class="fdb-router__row">
      <span class="fdb-router__label">Where</span>
      <span class="fdb-router__value"><?= htmlspecialchars($route['where']) ?></span>
    </div>
  <?php endif; ?>
</div>
