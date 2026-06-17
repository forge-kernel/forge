<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Controllers\Web;

use App\Modules\AppAuth\Validations\LoginValidation;
use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use Forge\Core\Services\RedirectHandlerService;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
final class LoginController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly RedirectHandlerService $redirectHandler
    ) {
    }

    #[Route("/auth/login")]
    #[Layout("ForgeComponents:auth-split")]
    public function index(): Response
    {
        return $this->view(view: "pages/login");
    }

    #[Route("/auth/login", "POST")]
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

    #[Route('/auth/logout', 'POST')]
    public function logout(): Response
    {
        $this->forgeAuthService->logout();
        return Redirect::to('/');
    }
}
