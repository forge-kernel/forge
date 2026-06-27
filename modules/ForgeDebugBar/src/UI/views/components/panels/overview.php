<?php
/** @var array<string, mixed> $props */
$req = $props['request'] ?? [];

if (empty($req) || isset($req['error'])): ?>
  <div class="fdb-panel__empty">No request information available.</div>
<?php return; endif; ?>
<div class="fdb-overview">
  <div class="fdb-section-title">Request</div>
  <div class="fdb-overview__row">
    <span class="fdb-overview__label">URL</span>
    <span class="fdb-overview__url"><?= htmlspecialchars($req['url'] ?? '') ?></span>
  </div>
  <div class="fdb-overview__row">
    <span class="fdb-overview__label">Method</span>
    <span class="fdb-badge fdb-badge--method fdb-badge--<?= strtolower($req['method'] ?? '') ?>"><?= htmlspecialchars($req['method'] ?? '') ?></span>
  </div>
  <div class="fdb-overview__row">
    <span class="fdb-overview__label">IP</span>
    <span class="fdb-overview__value"><?= htmlspecialchars($req['ip'] ?? '') ?></span>
  </div>
  <?php $headers = $req['headers'] ?? []; if (!empty($headers)): ?>
    <div class="fdb-section-title">Headers</div>
    <div class="fdb-kv-list">
      <?php foreach ($headers as $h => $v): ?>
        <div class="fdb-kv-list__row">
          <span class="fdb-kv-list__key"><?= htmlspecialchars($h) ?></span>
          <span class="fdb-kv-list__value"><?= htmlspecialchars($v) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php $query = $req['query'] ?? []; if (!empty($query)): ?>
    <div class="fdb-section-title">Query</div>
    <pre class="fdb-pre"><?= htmlspecialchars(print_r($query, true)) ?></pre>
  <?php endif; ?>
  <?php $body = $req['body'] ?? []; if (!empty($body)): ?>
    <div class="fdb-section-title">Body</div>
    <pre class="fdb-pre"><?= htmlspecialchars(print_r($body, true)) ?></pre>
  <?php endif; ?>
  <?php $cookies = $req['cookies'] ?? []; if (!empty($cookies)): ?>
    <div class="fdb-section-title">Cookies</div>
    <div class="fdb-kv-list">
      <?php foreach ($cookies as $k => $v): ?>
        <div class="fdb-kv-list__row">
          <span class="fdb-kv-list__key"><?= htmlspecialchars($k) ?></span>
          <span class="fdb-kv-list__value"><?= htmlspecialchars($v) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php $files = $req['files'] ?? []; if (!empty($files)): ?>
    <div class="fdb-section-title">Files</div>
    <pre class="fdb-pre"><?= htmlspecialchars(print_r($files, true)) ?></pre>
  <?php endif; ?>
</div>
