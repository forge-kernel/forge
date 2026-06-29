<?php

declare(strict_types=1);

namespace Modules\AppAuth\Controllers\Web;

use Modules\AppAuth\Validations\LoginValidation;
use Modules\ForgeAuth\Exceptions\LoginException;
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
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/auth')]
#[UseMiddleware('web')]
final class LoginController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly RedirectHandlerService $redirectHandler
    ) {
    }

    #[Endpoint("/login")]
    #[Layout("ForgeComponents:auth-split")]
    public function index(): Response
    {
        return $this->view(view: "login");
    }

    #[Endpoint("/login", "POST")]
    public function login(Request $request): Response
    {
        try {
            LoginValidation::validate($request->postData);
            $loginCredentials = $this->sanitize($request->postData);

            $this->forgeAuthService->login($loginCredentials);
            return Redirect::to($this->redirectHandler->getRedirect('/'));
        } catch (LoginException $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/login');
        }
    }

    #[Endpoint('/logout', 'POST')]
    public function logout(): Response
    {
        $this->forgeAuthService->logout();
        return Redirect::to('/');
    }
}
