<?php
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
  <title><?= $title ?? "ForgeHub" ?> | ForgeHub</title>
  <link rel="stylesheet" href="/assets/css/app.css" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?= raw(csrf_meta()) ?>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script>
  <style>
    @media (max-width: 768px) {
      .sidebar-overlay {
        display: none;
      }

      .sidebar-overlay.active {
        display: block;
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
      }
    }
  </style>
</head>

<body class="h-full bg-gray-100">
  <div class="flex h-screen overflow-hidden">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <?= component(name: 'ForgeHub:navigation') ?>

    <main class="flex-1 flex flex-col overflow-hidden">
      <?= component(name: 'ForgeHub:layout/header', props: ['title' => $title ?? 'Dashboard']) ?>

      <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-8xl mx-auto">
          <?= $content ?>
        </div>
      </div>
    </main>
  </div>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      if (sidebar && overlay) {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('active');
      }
    }

    function toggleUserMenu() {
      const menu = document.getElementById('userMenu');
      if (menu) {
        menu.classList.toggle('hidden');
      }
    }

    document.addEventListener('click', function (event) {
      const menu = document.getElementById('userMenu');
      const button = document.getElementById('userMenuButton');
      if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
        menu.classList.add('hidden');
      }
    });
  </script>
</body>

</html>
