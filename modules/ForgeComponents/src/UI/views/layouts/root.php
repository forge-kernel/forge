<?php
/**
 * @var string $content
 * @var array<string, mixed> $layoutProps
 * @var array<string, mixed> $layoutSections
 * @var array<string, mixed> $layoutSlots
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $layoutProps['title'] ?? 'Forge' ?></title>
  <link rel="stylesheet" href="/assets/modules/forge-components/css/forge-components.css">
  <link rel="stylesheet" href="/assets/modules/forge-components/css/forge-components/_admin.css">
  <?= $layoutSections['head_end'] ?? '' ?>
  <?= raw(csrf_meta()) ?>
  <script>window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';</script>
</head>
<body class="<?= $layoutProps['bodyClass'] ?? '' ?>">
  <?= $content ?>
  <?= $layoutSections['body_end'] ?? '' ?>
</body>
</html>
