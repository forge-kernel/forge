<?php
/** @var array<string, mixed> $props */
$messages = $props['messages'] ?? [];

if (empty($messages)): ?>
  <div class="fdb-panel__empty">No messages collected.</div>
<?php return; endif; ?>
<div class="fdb-console">
  <?php foreach ($messages as $msg): ?>
    <div class="fdb-console__entry">
      <span class="fdb-console__time">[<?= $msg['relative_time'] ?? '0' ?>ms]</span>
      <span class="fdb-console__message"><?= htmlspecialchars($msg['message'] ?? '') ?></span>
      <?php if (in_array($msg['label'] ?? '', ['info', 'warning', 'error'])): ?>
        <span class="fdb-badge fdb-badge--<?= $msg['label'] ?>"><?= ucfirst($msg['label']) ?></span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
