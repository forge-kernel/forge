<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Reactive]
#[Middleware('web')]
final class HomeController
{
    use ControllerHelper;

    #[State]
    public int $usersCount = 3;

    #[Action]
    public function refreshUsersCount(): void
    {
        $this->usersCount = $this->usersCount * 2;
    }

    #[Route("/nexus/auth/{otp}")]
    public function index(string $otp): Response
    {
        return $this->view(view: "pages/index");
    }

    #[Route("/nexus/dashboard")]
    public function dashboard(): Response
    {
        $data = [
            'usersCount' => $this->usersCount
        ];
        return $this->view(view: "pages/dashboard", data: $data);
    }
}
