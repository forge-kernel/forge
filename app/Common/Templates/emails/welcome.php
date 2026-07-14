<?php $layout = 'base'; ?>

<h1 style="margin: 0 0 16px; font-size: 24px; font-weight: 600; color: #111827;">
    Welcome, <?= e($name) ?>!
</h1>
<p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #6b7280;">
    Thanks for signing up for <strong style="color: #374151;"><?= e($appName) ?></strong>.
    We're excited to have you on board.
</p>
<?php if ($supportEmail): ?>
        <p style="margin: 0; font-size: 14px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px;">
            Questions? Reply to <a href="mailto:<?= e($supportEmail) ?>" style="color: #fc7205; text-decoration: none;"><?= e($supportEmail) ?></a>
        </p>
<?php endif; ?>
