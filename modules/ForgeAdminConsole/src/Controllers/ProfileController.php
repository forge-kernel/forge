<?php
declare(strict_types=1);

namespace App\Modules\ForgeAdminConsole\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class ProfileController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Route("/admin/profile")]
    public function editProfile(): Response
    {
        $user = $this->userContext->current();
        return $this->view(view: "pages/admin/profile", data: [
            'currentUser' => $user,
        ]);
    }

    #[Route("/admin/profile", "POST")]
    public function saveProfile(Request $request): Response
    {
        try {
            $this->sanitize($request->postData);
            Flash::set('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Failed to update profile: ' . $e->getMessage());
        }

        return Redirect::to('/admin/profile');
    }
}
