<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeAuth\Enums\Role;
use Modules\AppAuth\Services\UserContext;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Attributes\RequiresRole;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/hub')]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class SettingsController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContext $userContext
    ) {
    }

    #[Endpoint("/settings")]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        $user = $this->userContext->current();
        if ($user === null) {
            Flash::set('error', 'You must be logged in to view settings.');
            return Redirect::to('/auth/login');
        }

        $data = [
            'title' => 'Settings',
            'user' => $user,
        ];

        return $this->view(view: "settings", data: $data);
    }

    #[Endpoint("/settings/password", "POST")]
    public function updatePassword(Request $request): Response
    {
        $user = $this->userContext->current();
        if ($user === null) {
            Flash::set('error', 'You must be logged in to update your password.');
            return Redirect::to('/auth/login');
        }

        $data = $this->sanitize($request->postData);

        if (empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
            Flash::set('error', 'All password fields are required.');
            return Redirect::to('/hub/settings');
        }

        if (!password_verify($data['current_password'], $user->password)) {
            Flash::set('error', 'Current password is incorrect.');
            return Redirect::to('/hub/settings');
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            Flash::set('error', 'New password and confirmation do not match.');
            return Redirect::to('/hub/settings');
        }

        if (strlen($data['new_password']) < 6) {
            Flash::set('error', 'New password must be at least 6 characters.');
            return Redirect::to('/hub/settings');
        }

        try {
            $user->password = password_hash($data['new_password'], PASSWORD_BCRYPT);
            $user->save();

            Flash::set('success', 'Password updated successfully.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Failed to update password: ' . $e->getMessage());
        }

        return Redirect::to('/hub/settings');
    }
}
