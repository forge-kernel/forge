<?php

declare(strict_types=1);

namespace Modules\ForgeNexus\Controllers;

use Modules\ForgeWire\Attributes\Action;
use Modules\ForgeWire\Attributes\Reactive;
use Modules\ForgeWire\Attributes\State;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/nexus')]
#[Reactive]
#[UseMiddleware('web')]
final class HomeController
{
    use ResponseHelper;
    use ViewHelper;

    #[State]
    public int $usersCount = 3;

    #[Action]
    public function refreshUsersCount(): void
    {
        $this->usersCount = $this->usersCount * 2;
    }

    #[Endpoint("/auth/{otp}")]
    public function index(string $otp): Response
    {
        return $this->view(view: "index");
    }

    #[Endpoint("/dashboard")]
    public function dashboard(): Response
    {
        $data = [
            'usersCount' => $this->usersCount
        ];
        return $this->view(view: "dashboard", data: $data);
    }
}
