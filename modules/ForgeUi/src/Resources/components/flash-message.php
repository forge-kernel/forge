<?php

use Forge\Core\Helpers\Flash;
use App\Modules\ForgeUi\DesignTokens;

$flashMessages = Flash::flat() ?? [];
?>
<?php if (!empty($flashMessages)): ?>
    <div class="fw-flash-messages space-y-2">
        <?php foreach ($flashMessages as $msg): ?>
            <?=
                component(name: "ForgeUi:alert", props: [
                    "variant" => $msg["type"] ?? "info",
                    "dismissible" => true,
                    "children" => $msg["message"]
                ], fromModule: true)
                ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
