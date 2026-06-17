<?php

use App\Modules\ForgeUi\DesignTokens;

$classes = class_merge(['fw-flash-container', 'fixed', 'top-4', 'right-4', 'z-50', 'space-y-2'], $class ?? '');
?>
<div id="fw-flash-container" class="<?= $classes ?>"></div>
