<?php

use Forge\Core\Helpers\Framework;

/**
 * @var string $title
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? "Authentication" ?> | Forge</title>
  <link rel="stylesheet" href="/assets/css/app.css" />
  <link rel="stylesheet" href="/assets/modules/ForgeComponents/css/forge-components.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?= raw(csrf_meta()) ?>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script>
</head>

<body class="h-full bg-gray-100">
  <div class="min-h-screen flex">
    <!-- Left Side - Information Panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-gray-900 to-gray-800 p-12 flex-col justify-between">
      <div>
        <div class="flex items-center gap-3 mb-8">
          <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-cube text-gray-900 text-lg"></i>
          </div>
          <h1 class="text-2xl font-bold text-white">Forge</h1>
        </div>

        <div class="space-y-6">
          <div>
            <h2 class="text-3xl font-semibold text-white mb-3">Forge Kernel</h2>
            <p class="text-gray-300 text-base">A minimal, dependency-free PHP kernel with pluggable capabilities</p>
          </div>

          <div class="space-y-4 pt-4">
            <div class="text-gray-300">
              <p class="text-base leading-relaxed">
                Capabilities, not built-ins. Database, ORM, authentication, storage — these aren't built into the kernel. They're capabilities you plug in via modules when you need them.
              </p>
            </div>

            <div class="text-gray-400 text-base pt-2 border-t border-gray-700">
              <p class="italic">
                "You're not a user here. You're a builder."
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-3 pt-8 border-t border-gray-700">
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-400">Kernel Version</span>
          <span class="text-white font-mono text-xs">v<?= htmlspecialchars(Framework::version()) ?></span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-400">License</span>
          <span class="text-white font-mono text-xs">MIT</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-400">Server Time</span>
          <span class="text-white font-mono text-xs"><?= date('H:i:s') ?></span>
        </div>
      </div>
    </div>

    <!-- Right Side - Auth Form -->
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
      <div class="w-full max-w-md space-y-8">
        <?= $content ?>
      </div>
    </div>
  </div>
</body>

</html>
