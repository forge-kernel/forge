<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\AppAuth\Models\Profile;
use App\Modules\AppAuth\Services\UserContext;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class ProfileController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContext $userContext
    ) {
    }

    #[Route("/hub/profile")]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        $user = $this->userContext->current();
        if ($user === null) {
            Flash::set('error', 'You must be logged in to view your profile.');
            return Redirect::to('/auth/login');
        }

        $profile = $user->relation('profile')->first();

        $data = [
            'title' => 'Profile',
            'user' => $user,
            'profile' => $profile,
        ];

        return $this->view(view: "pages/profile", data: $data);
    }

    #[Route("/hub/profile", "POST")]
    public function update(Request $request): Response
    {
        $user = $this->userContext->current();
        if ($user === null) {
            Flash::set('error', 'You must be logged in to update your profile.');
            return Redirect::to('/auth/login');
        }

        $data = $this->sanitize($request->postData);

        try {
            if (isset($data['identifier'])) {
                $user->identifier = $data['identifier'];
            }

            if (isset($data['email'])) {
                $user->email = $data['email'];
            }

            $user->save();

            $profile = $user->relation('profile')->first();
            if ($profile === null) {
                $profile = new Profile();
                $profile->user_id = $user->id;
                $profile->first_name = $data['first_name'] ?? '';
            }

            if (isset($data['first_name'])) {
                $profile->first_name = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $profile->last_name = $data['last_name'] ?? null;
            }

            if (isset($data['phone'])) {
                $profile->phone = $data['phone'] ?? null;
            }

            $profile->save();

            Flash::set('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Failed to update profile: ' . $e->getMessage());
        }

        return Redirect::to('/hub/profile');
    }
}
