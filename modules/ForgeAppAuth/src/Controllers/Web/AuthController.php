<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Controllers\Web;

use App\Modules\ForgeAppAuth\Requirements\PasswordRequirements;
use App\Modules\ForgeAppAuth\Services\PasswordResetService;
use App\Modules\ForgeAppAuth\Validations\ForgotPasswordValidation;
use App\Modules\ForgeAppAuth\Validations\LoginValidation;
use App\Modules\ForgeAppAuth\Validations\RegisterValidation;
use App\Modules\ForgeAppAuth\Validations\ResetPasswordValidation;
use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
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
use Forge\Core\Services\RedirectHandlerService;
use Forge\Exceptions\ValidationException;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/auth')]
#[UseMiddleware('web')]
final class AuthController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly RedirectHandlerService $redirectHandler,
    ) {
    }

    #[Endpoint("/login")]
    #[Layout("ForgeComponents:auth-split")]
    public function showLogin(): Response
    {
        return $this->view(view: "auth/login");
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
            Flash::set("error", "Invalid credentials. Please try again.");
            return Redirect::to('/auth/login');
        }
    }

    #[Endpoint("/register")]
    #[Layout("ForgeComponents:auth-split")]
    public function showRegister(): Response
    {
        return $this->view(view: "auth/register");
    }

    #[Endpoint("/register", "POST")]
    public function register(Request $request): Response
    {
        try {
            RegisterValidation::validate($request->postData);
            PasswordRequirements::validate($request->postData['password']);
            $registerData = $this->sanitize($request->postData);

            unset($registerData['confirm_password']);
            unset($registerData['terms']);

            $this->forgeAuthService->register($registerData);
            Flash::set("success", "Registration successful. Please login.");
            return Redirect::to('/auth/login');
        } catch (ValidationException $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/register');
        } catch (\Exception $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/register');
        }
    }

    #[Endpoint("/forgot-password")]
    #[Layout("ForgeComponents:auth-split")]
    public function showForgotPassword(): Response
    {
        return $this->view(view: "auth/forgot-password");
    }

    #[Endpoint("/forgot-password", "POST")]
    public function sendResetLink(Request $request): Response
    {
        try {
            ForgotPasswordValidation::validate($request->postData);
            $data = $this->sanitize($request->postData);

            $this->passwordResetService->sendResetLink($data['email']);

            Flash::set("success", "If that email is registered, we've sent a password reset link.");
            return Redirect::to('/auth/login');
        } catch (ValidationException $e) {
            Flash::set("error", $e->getMessage());
            return Redirect::to('/auth/forgot-password');
        }
    }

    #[Endpoint("/reset-password")]
    #[Layout("ForgeComponents:auth-split")]
    public function showResetPassword(Request $request): Response
    {
        $token = $request->queryParams['token'] ?? '';

        if (!$token || !$this->passwordResetService->isTokenValid($token)) {
            Flash::set("error", "This reset link is invalid or has expired.");
            return Redirect::to('/auth/forgot-password');
        }

        return $this->view(view: "auth/reset-password", data: ['token' => $token]);
    }

    #[Endpoint("/reset-password", "POST")]
    public function resetPassword(Request $request): Response
    {
        try {
            ResetPasswordValidation::validate($request->postData);
            $data = $this->sanitize($request->postData);
            PasswordRequirements::validate($data['password']);

            $success = $this->passwordResetService->resetPassword(
                token: $data['token'],
                newPassword: $data['password'],
            );

            if (!$success) {
                Flash::set("error", "This reset link is invalid or has expired.");
                return Redirect::to('/auth/forgot-password');
            }

            Flash::set("success", "Your password has been reset. Please sign in.");
            return Redirect::to('/auth/login');
        } catch (ValidationException $e) {
            Flash::set("error", $e->getMessage());
            $token = $request->postData['token'] ?? '';
            return Redirect::to('/auth/reset-password?token=' . urlencode($token));
        }
    }

    #[Endpoint('/logout', 'POST')]
    public function logout(): Response
    {
        $this->forgeAuthService->logout();
        return Redirect::to('/');
    }
}
