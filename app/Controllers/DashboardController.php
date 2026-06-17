<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Middleware("web")]
final class DashboardController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct()
    {
        //
    }

    #[Route("/dashboard")]
    #[Layout("ForgeComponents:wrappers/admin-default")]
    public function welcome(): Response
    {
        $data = [
            "title" => "Welcome to Forge Framework",
        ];

        return $this->view(view: "pages/admin/default", data: []);
    }
}
