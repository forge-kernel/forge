<?php

use App\Models\Post;
use Modules\ForgeView\View;

/**
 * @var string $title
 * @var string $message
 * @var Post $posts
 */

View::layout(name: "main", loadFromModule: false);
?>
<section class="container">
    <h2 class="text-2xl">Tenant app</h2>
    <h3>Posts information</h3>
        <pre>
            <?php print_r($posts); ?>
        </pre>
</section>