<?php

use Forge\Core\Helpers\ModuleResources;

/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus CMS</title>
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'css/main.css') ?>">
    <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'css/nexus.css') ?>">
    <?= external_asset('font_awesome') ?>
    <?= raw(csrf_meta()) ?>
</head>

<body>
    <div class="dashboard-container">
        <?= component(name: 'ForgeNexus:sidebar/sidebar', fromModule: true) ?>
        <main class="main-content">
            <?= component(name: 'ForgeNexus:layout/header', fromModule: true) ?>
            <div class="dashboard-grid">
                <?= $content ?>
            </div>
        </main>
    </div>

    <script defer src="<?= ModuleResources::pathTo(module: 'forge-nexus', resource: 'js/nexus.js') ?>"></script>
</body>

</html>