<?php

use Modules\ForgeAppAuth\Definitions\AuthCardDefinition;

/** @var string $token */

?>

<?= component('ForgeAppAuth:auth-card', props: new AuthCardDefinition(
    heading: 'Reset password',
    subtitle: 'Choose a new password for your account.',
    form: 'reset-password-form',
    formProps: ['token' => $token],
    footerLink: [
        'text' => 'Remember your password?',
        'href' => '/auth/login',
        'label' => 'Back to sign in',
    ],
)) ?>
