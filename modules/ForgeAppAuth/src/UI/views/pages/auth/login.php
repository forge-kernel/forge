<?php

use App\Modules\ForgeAppAuth\Definitions\AuthCardDefinition;

?>

<?= component('ForgeAppAuth:auth-card', props: new AuthCardDefinition(
    heading: 'Welcome back',
    subtitle: 'Sign in to your account to continue',
    form: 'auth-form',
    footerLink: [
        'text' => "Don't have an account?",
        'href' => '/auth/register',
        'label' => 'Sign up',
    ],
    footerText: 'By signing in, you agree to our Terms of Service and Privacy Policy',
)) ?>
