<?php

use Modules\ForgeHub\Services\HubItemRegistry;
use Forge\Core\DI\Container;

/**
 * @var array $props
 */

$container = Container::getInstance();
$registry = $container->has(HubItemRegistry::class) ? $container->get(HubItemRegistry::class) : null;
$hubItems = $registry ? $registry->getHubItems() : [];
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';


$hasAuth = $container->has(\Modules\ForgeAuth\Contracts\UserContextInterface::class);
$user = null;
$userPermissions = [];
if ($hasAuth) {
    try {
        $authService = $container->get(\Modules\ForgeAuth\Contracts\UserContextInterface::class);
        $user = $authService->current();

        if ($user !== null && $container->has(\Modules\ForgeAuth\Services\PermissionService::class)) {
            try {
                $permissionService = $container->get(\Modules\ForgeAuth\Services\PermissionService::class);
                $userPermissions = $permissionService->getUserPermissions($user);
            } catch (\Throwable) {
            }
        }
    } catch (\Throwable) {
    }
}

$filteredHubItems = [];
foreach ($hubItems as $item) {
    $requiredPermissions = $item['permissions'] ?? [];
    if (empty($requiredPermissions)) {
        $filteredHubItems[] = $item;
        continue;
    }
    if (!empty($userPermissions)) {
        $hasPermission = false;
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                $hasPermission = true;
                break;
            }
        }
        if ($hasPermission) {
            $filteredHubItems[] = $item;
        }
    }
}

$groupedItems = [];
foreach ($filteredHubItems as $item) {
    $group = $item['module'] === 'Modules\\ForgeHub\\ForgeHubModule' ? 'Platform' : 'Settings';
    if (!isset($groupedItems[$group])) {
        $groupedItems[$group] = [];
    }
    $groupedItems[$group][] = $item;
}
?>
<aside id="sidebar"
  class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
  <div class="p-4 border-b border-gray-200">
    <a href="/hub" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
      <div class="w-8 h-8 bg-gray-900 rounded-md flex items-center justify-center">
        <i class="fa-solid fa-cube text-white text-sm"></i>
      </div>
      <h1 class="text-lg font-semibold text-gray-900">ForgeHub</h1>
    </a>
  </div>

  <nav class="flex-1 overflow-y-auto py-4">
    <!-- Dashboard Link -->
    <?php
    $isDashboard = $currentPath === '/hub' || $currentPath === '/hub/';
    ?>
    <div class="mb-4">
      <ul class="space-y-1">
        <li>
          <a href="/hub"
            class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-md transition-colors <?= $isDashboard ? 'bg-blue-50 text-blue-600 font-medium' : 'hover:bg-gray-100 hover:text-gray-900' ?>"
            onclick="if(window.innerWidth < 1024) toggleSidebar();">
            <i class="fa-solid fa-home w-5 mr-3 text-center"></i>
            <span>Dashboard</span>
          </a>
        </li>
      </ul>
    </div>
    <?php foreach ($groupedItems as $groupName => $items): ?>
              <div class="mb-4">
                <h2 class="px-4 mb-2 text-xs uppercase tracking-wider text-gray-500 font-medium">
                  <?= htmlspecialchars($groupName) ?>
                </h2>
                <ul class="space-y-1">
                  <?php foreach ($items as $item): ?>
                            <?php
                            $isActive = str_starts_with($currentPath, $item['route']);
                            ?>
                            <li class="<?= $isActive ? 'active' : '' ?>">
                              <a href="<?= htmlspecialchars($item['route']) ?>"
                                class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-md transition-colors <?= $isActive ? 'bg-blue-50 text-blue-600 font-medium' : 'hover:bg-gray-100 hover:text-gray-900' ?>"
                                onclick="if(window.innerWidth < 1024) toggleSidebar();">
                                <i class="fa-solid fa-<?= htmlspecialchars($item['icon'] ?? 'circle') ?> w-5 mr-3 text-center"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                              </a>
                            </li>
                  <?php endforeach; ?>
                </ul>
              </div>
    <?php endforeach; ?>
  </nav>

  <div class="p-4 border-t border-gray-200">
    <div class="mb-4">
      <ul class="space-y-1">
        <li>
          <a href="https://github.com/forge-kernel" target="_blank" rel="noopener noreferrer"
            class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-md hover:bg-gray-100 hover:text-gray-900 transition-colors">
            <i class="fa-solid fa-code-branch w-5 mr-3 text-center"></i>
            <span>Repository</span>
          </a>
        </li>
        <li>
          <a href="https://forge-kernel.github.io/" target="_blank" rel="noopener noreferrer"
            class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-md hover:bg-gray-100 hover:text-gray-900 transition-colors">
            <i class="fa-solid fa-book w-5 mr-3 text-center"></i>
            <span>Documentation</span>
          </a>
        </li>
      </ul>
    </div>

    <?php if ($user): ?>
              <div class="pt-3 border-t border-gray-200 relative">
                <button type="button" id="userMenuButton" onclick="toggleUserMenu()"
                  class="w-full flex items-center gap-3 px-2 py-2 rounded-md hover:bg-gray-100 transition-colors text-left">
                  <div
                    class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-700 text-sm font-semibold flex-shrink-0">
                    <?= strtoupper(substr($user->identifier ?? 'U', 0, 2)) ?>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($user->identifier ?? 'User') ?></p>
                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($user->email ?? '') ?></p>
                  </div>
                  <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                </button>

                <div id="userMenu"
                  class="hidden absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg border border-gray-200 shadow-lg z-50">
                  <div class="py-1">
                    <a href="/hub/profile"
                      class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                      <i class="fa-solid fa-user w-5 text-center"></i>
                      <span>Profile</span>
                    </a>
                    <a href="/hub/settings"
                      class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                      <i class="fa-solid fa-gear w-5 text-center"></i>
                      <span>Settings</span>
                    </a>
                    <div class="border-t border-gray-200 my-1"></div>
                    <form method="POST" action="/auth/logout" class="w-full">
                      <?= raw(csrf_input()) ?>
                      <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors text-left">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
                        <span>Logout</span>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
    <?php endif; ?>
  </div>
</aside>
