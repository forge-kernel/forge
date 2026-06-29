<?php

use Modules\AppAuth\Definitions\AuthCardDefinition;

?>

<?= component('AppAuth:auth-card', props: new AuthCardDefinition(
    heading: 'Create an account',
    subtitle: 'Get started with Forge today',
    form: 'register-form',
    footerLink: [
        'text' => 'Already have an account?',
        'href' => '/auth/login',
        'label' => 'Sign in',
    ],
    footerText: 'Your account will be created with default permissions. Contact an administrator to request additional access.',
)) ?>
