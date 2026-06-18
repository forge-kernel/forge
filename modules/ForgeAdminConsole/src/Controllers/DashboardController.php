<?php
declare(strict_types=1);

namespace App\Modules\ForgeAdminConsole\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class DashboardController
{
    use ControllerHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Route(path: "/admin")]
    public function dashboard(): Response
    {
        $user = $this->userContext->current();

        $stats = [
            ['label' => 'Total Users', 'value' => '—', 'icon' => 'users', 'variant' => 'default'],
            ['label' => 'Active Sessions', 'value' => '1', 'icon' => 'clock', 'variant' => 'default'],
            ['label' => 'Modules', 'value' => '30', 'icon' => 'cube', 'variant' => 'default'],
            ['label' => 'Kernel', 'value' => 'v' . \Forge\Core\Helpers\Framework::version(), 'icon' => 'chart-bar', 'variant' => 'default'],
        ];

        $activities = [
            ['title' => 'Welcome to your admin console', 'time' => 'just now', 'icon' => 'home', 'variant' => 'info'],
            ['title' => 'User ' . ($user?->getIdentifier() ?? 'Guest') . ' logged in', 'time' => '—', 'icon' => 'arrow-right-on-rectangle', 'variant' => 'success'],
        ];

        $quickActions = [
            ['label' => 'View Users', 'href' => '/admin/users', 'variant' => 'primary', 'icon' => 'users'],
            ['label' => 'Account Settings', 'href' => '/admin/account', 'variant' => 'secondary', 'icon' => 'cog-6-tooth'],
            ['label' => 'Edit Profile', 'href' => '/admin/profile', 'variant' => 'secondary', 'icon' => 'user'],
        ];

        return $this->view(view: "pages/admin/dashboard", data: [
            'stats' => $stats,
            'activities' => $activities,
            'quickActions' => $quickActions,
            'currentUser' => $user,
        ]);
    }
}
