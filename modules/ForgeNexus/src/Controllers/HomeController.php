<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

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
