<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Controllers\Web;

use App\Modules\AppAuth\Requeriments\PasswordRequeriments;
use App\Modules\AppAuth\Validations\RegisterValidation;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use Forge\Core\Services\RedirectHandlerService;
use Forge\Exceptions\ValidationException;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/auth')]
#[UseMiddleware('web')]
final class RegisterController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private ForgeAuthService $forgeAuthService,
        private RedirectHandlerService $redirectHandlerService
    ) {
    }

    #[Endpoint("/register")]
    #[Layout("ForgeComponents:auth-split")]
    public function register(): Response
    {
        return $this->view(view: "register");
    }

    #[Endpoint("/register", "POST")]
    public function createUser(Request $request): Response
    {
        try {
            RegisterValidation::validate($request->postData);
            PasswordRequeriments::validate($request->postData['password']);
            $registerData = $this->sanitize($request->postData);

            $this->forgeAuthService->register($registerData);
            Flash::set("success", "Registration successful. Please login.");
            return Redirect::to($this->redirectHandlerService->getRedirect('/auth/login'));
        } catch (ValidationException $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/register');
        } catch (\Exception $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/register');
        }
    }
}
