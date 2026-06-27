<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Events\TestPageVisitedEvent;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use App\Modules\ForgeRouter\Http\CookieJar;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Http\Request;
use Forge\Core\Session\SessionInterface;
use App\Modules\ForgeView\Traits\ViewHelper;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeRouter\Attributes\Layout;

#[Routable]
#[TenantScope("central")]
final class TestController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly CookieJar $cookies,
        private readonly EventDispatcher $dispatcher,
    ) {
    }

    #[Endpoint("/test")]
    #[Layout('main')]
    public function index(Request $request): Response
    {
        $existingUserId = $this->session->get("user_id");
        $testUserId = $existingUserId ?? 123456;

        if ($existingUserId === null) {
            $this->session->set("test_user_id", 123456);
        }

        $cookie = $this->cookies->make("remember_me", "token123", 60 * 24 * 30);

        $this->dispatcher->dispatch(
            new TestPageVisitedEvent(
                userId: $testUserId,
                visitedAt: date("Y-m-d H:i:s"),
            ),
        );

        $data = [
            "title" => "Welcome to Forge",
            "userId" => $existingUserId ?? $this->session->get("test_user_id") ?? null,
        ];

        return $this->view(view: "test/index", data: $data)->withCookie(
            $cookie,
        );
    }

    #[Endpoint("/test/failure")]
    public function failure(Request $request): Response
    {
        return $this->createErrorResponse($request, "Simulate failure", 500);
    }
}
