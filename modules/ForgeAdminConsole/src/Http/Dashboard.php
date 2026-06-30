<?php
declare(strict_types=1);

namespace Modules\ForgeAdminConsole\Http;

use Modules\ForgeAuth\Contracts\UserContextInterface;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware(['web', 'auth'])]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class Dashboard
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Endpoint("/admin")]
    public function dashboard(): Response
    {
        $user = $this->userContext->current();

        $stats = [
            ['label' => 'Total Users', 'value' => '—', 'icon' => 'users', 'variant' => 'default'],
            ['label' => 'Active Sessions', 'value' => '1', 'icon' => 'clock', 'variant' => 'default'],
            ['label' => 'Modules', 'value' => '30', 'icon' => 'cube', 'variant' => 'default'],
            ['label' => 'Kernel', 'value' => 'v' . \Forge\Core\Helpers\Version::version(), 'icon' => 'chart-bar', 'variant' => 'default'],
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

        return $this->view(view: "admin/dashboard", data: [
            'stats' => $stats,
            'activities' => $activities,
            'quickActions' => $quickActions,
            'currentUser' => $user,
        ]);
    }
}
