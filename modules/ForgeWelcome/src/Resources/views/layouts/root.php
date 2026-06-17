<?php

/**
 * Root layout — HTML skeleton used by all ForgeWelcome pages.
 * Child layouts inherit via $parentLayout = 'ForgeWelcome:root'.
 *
 * Features demonstrated:
 *   - body class via $layoutProps['bodyClass']
 *   - head_end / body_end hooks via $layoutSections
 *
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */

use Forge\Core\Helpers\ModuleResources;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $layoutProps['title'] ?? 'Forge' ?></title>
    <link rel="stylesheet" href="/assets/modules/forge-welcome/css/style.css">
    <link rel="stylesheet" href="/assets/modules/forge-welcome/css/custom.css">
    <?= $layoutSections['head_end'] ?? '' ?>
    <?= raw(csrf_meta()) ?>
</head>
<body class="<?= $layoutProps['bodyClass'] ?? '' ?>">
    <?= $content ?>
    <?= ModuleResources::loadScripts('forge-ui') ?>
    <?= $layoutSections['body_end'] ?? '' ?>
</body>
</html>
