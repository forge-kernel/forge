<?php

/**
 * @var array $props
 */

$title = $props['title'] ?? 'Dashboard';
?>
<header class="h-16 flex items-center justify-between px-6 border-b border-gray-200 bg-white">
    <div class="flex items-center gap-4">
        <button id="mobileMenuToggle" class="lg:hidden p-2 text-gray-600 hover:text-gray-900" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1 class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="flex items-center gap-2">
        <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors" aria-label="Notifications">
            <i class="fa-solid fa-bell"></i>
        </button>
        <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors" aria-label="Settings">
            <i class="fa-solid fa-gear"></i>
        </button>
    </div>
</header>
