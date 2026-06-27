<?php
/** @var array<string, mixed> $props */

$title = $props['title'] ?? '';
$content = $props['content'] ?? '';

?>
<div class="fc-card">
    <?php if ($title): ?>
            <div class="fc-card__header">
                <h3 class="fc-card__title"><?= e($title) ?></h3>
            </div>
    <?php endif; ?>
    <div class="fc-card__body">
        <?= $content ?>
    </div>
</div>
