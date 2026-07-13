<?php

/** @var \App\Dto\InvitationDTO $props */

$layout = 'base';
?>

<h1 style="margin: 0 0 16px; font-size: 24px; font-weight: 600; color: #111827;">
    You've been invited!
</h1>
<p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #6b7280;">
    Hi <?= e($props->recipientName) ?>,
</p>
<p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #6b7280;">
    <strong style="color: #374151;"><?= e($props->inviterName) ?></strong> has invited you to join
    <strong style="color: #374151;"><?= e($props->workspaceName) ?></strong>.
</p>
<table cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
    <tr>
        <td style="background-color: #fc7205; border-radius: 8px;">
            <a href="<?= e($props->inviteUrl) ?>"
               style="display: inline-block; padding: 14px 28px; font-size: 16px; font-weight: 500; color: #ffffff; text-decoration: none;">
                Accept Invitation
            </a>
        </td>
    </tr>
</table>
<p style="margin: 0; font-size: 14px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    If you didn't expect this invitation, you can safely ignore this email.
</p>
