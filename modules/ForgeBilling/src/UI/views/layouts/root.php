<?php
/**
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
  <title><?= $layoutProps['title'] ?? "Billing" ?> | ForgeBilling</title>
  <link rel="stylesheet" href="<?= ModuleResources::pathTo(module: 'forge-billing', resource: 'css/billing.css') ?>">
  <link rel="stylesheet" href="/assets/css/app.css" />
  <?= raw(csrf_meta()) ?>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script>
  <?= $layoutSections['head_end'] ?? '' ?>
</head>
<body>
  <?= $content ?>
  <?= $layoutSections['body_end'] ?? '' ?>
</body>
</html>
