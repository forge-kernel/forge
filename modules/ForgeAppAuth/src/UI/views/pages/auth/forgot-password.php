<?php

use Modules\ForgeAppAuth\Definitions\AuthCardDefinition;

?>

<?= component('ForgeAppAuth:auth-card', props: new AuthCardDefinition(
    heading: 'Forgot password',
    subtitle: "No worries, we'll send you reset instructions.",
    form: 'forgot-password-form',
    footerLink: [
        'text' => 'Remember your password?',
        'href' => '/auth/login',
        'label' => 'Back to sign in',
    ],
)) ?>
