<?php

use App\Modules\ForgeView\View;

View::layout(name: "nexus", loadFromModule: true);
?>


<!-- Chart section -->
<section class="card chart-card">
    <header class="card-header">
        <h2 class="card-title">Performance Overview</h2>
        <div class="card-actions">
            <button class="card-action-button">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>
    </header>
    <div class="card-body">
        <form class="u-flex u-flex-col u-gap-sm" method="POST" action="/signup">
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required placeholder="you@example.com">
                <span class="error-msg">Please enter a valid email.</span>
            </div>

            <div>
                <label for="pw">Password</label>
                <input id="pw" name="password" type="password" minlength="8" required>
                <span class="error-msg">Min 8 characters.</span>
            </div>

            <button type="submit">Create account</button>
        </form>
    </div>

</section>