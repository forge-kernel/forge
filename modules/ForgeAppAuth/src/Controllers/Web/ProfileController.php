<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Controllers\Web;

use App\Modules\ForgeAppAuth\Models\Profile;
use App\Modules\ForgeAppAuth\Services\UserContext;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use Forge\Core\Helpers\Flash;
use Forge\Traits\SecurityHelper;

#[Routable]
#[UseMiddleware(['web', 'auth'])]
final class ProfileController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContext $userContext,
    ) {
    }

    #[Endpoint("/profile")]
    #[Layout("ForgeComponents:wrappers/admin-default")]
    public function editProfile(): Response
    {
        $user = $this->userContext->current();
        if (!$user) {
            return Redirect::to('/auth/login');
        }

        $profile = Profile::query()->where('user_id', '=', $user->getId())->first();

        return $this->view(view: "profile/edit", data: [
            'user' => [
                'id' => $user->getId(),
                'identifier' => $user->getIdentifier(),
                'email' => $user->getEmail(),
            ],
            'profile' => $profile ? [
                'first_name' => $profile->first_name ?? '',
                'last_name' => $profile->last_name ?? '',
                'email' => $profile->email ?? '',
                'phone' => $profile->phone ?? '',
                'avatar' => $profile->avatar ?? '',
            ] : [],
        ]);
    }

    #[Endpoint("/profile", "POST")]
    public function saveProfile(Request $request): Response
    {
        $user = $this->userContext->current();
        if (!$user) {
            return Redirect::to('/auth/login');
        }

        try {
            $data = $this->sanitize($request->postData);

            $profile = Profile::query()->where('user_id', '=', $user->getId())->first();
            if (!$profile) {
                $profile = new Profile();
                $profile->user_id = $user->getId();
            }

            $profile->first_name = $data['first_name'] ?? '';
            $profile->last_name = $data['last_name'] ?? '';
            $profile->email = $data['email'] ?? $user->getEmail();
            $profile->phone = $data['phone'] ?? '';
            $profile->save();

            Flash::set("success", "Profile updated successfully.");
            return Redirect::to('/profile');
        } catch (\Exception $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/profile');
        }
    }
}
