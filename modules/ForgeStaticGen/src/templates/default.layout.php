<?php

use App\Modules\ForgeStaticGen\LayoutBuilder;

/**
 * @var array $meta
 * @var mixed $content
 * @var LayoutBuilder $renderer
 */
?>
<!DOCTYPE html>
<html>

<head>
    <title><?= $meta['title'] ?? 'Untitled' ?></title>
    <?= $renderer->renderComponent('head') ?>
</head>

<body>
    <div class="content">
        <?= $content ?>
    </div>
    <?= $renderer->renderComponent('footer') ?>
</body>

</html>