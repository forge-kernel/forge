<?php

use Modules\AppAuth\Models\User;

/**
 * @var string $title
 * @var string $message
 * @var User $user
 */

layout(name: "main");
?>
<section class="container">
    <h2>User area</h2>

    <h3>Welcome
        <form action="/auth/logout" method="POST">
            <?= csrf_input() ?>
            <button>Logout</button>
        </form>
    </h3>
    <p>Identifier: <?= $user->identifier ?></p>
    <p>Email: <?= $user->email ?></p>
    <p>Account created on: <?= $user->created_at ?></p>
</section>
