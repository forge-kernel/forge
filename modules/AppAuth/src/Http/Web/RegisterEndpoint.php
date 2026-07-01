<?php

declare(strict_types=1);

namespace Modules\AppAuth\Http\Web;

use Modules\AppAuth\Requeriments\PasswordRequeriments;
use Modules\AppAuth\Validations\RegisterValidation;
use Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Forge\Core\Services\RedirectHandlerService;
use Forge\Exceptions\ValidationException;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/auth')]
#[UseMiddleware('web')]
final class RegisterEndpoint
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
