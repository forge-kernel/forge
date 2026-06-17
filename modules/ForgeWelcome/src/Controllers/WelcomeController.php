<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Controllers;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class WelcomeController
{
    use ControllerHelper;

    #[Route("/")]
    #[Layout("ForgeWelcome:main")]
    public function index(): Response
    {
        return $this->view(view: "pages/index", data: []);
    }
}
