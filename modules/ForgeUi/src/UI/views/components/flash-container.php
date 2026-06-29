<?php

use Modules\ForgeUi\DesignTokens;

$classes = merge_classes(['fw-flash-container', 'fixed', 'top-4', 'right-4', 'z-50', 'space-y-2'], $class ?? '');
?>
<div id="fw-flash-container" class="<?= $classes ?>"></div>
