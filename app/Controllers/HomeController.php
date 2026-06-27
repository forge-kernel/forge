<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[TenantScope("central")]
#[UseMiddleware("web")]
final class HomeController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct()
    {
    }

    #[Endpoint("/")]
    #[Layout("main")]
    public function index(Request $request): Response
    {
        $data = [
            "title" => "Welcome to Forge Kernel",
        ];

        return $this->view(view: 'home/index', data: $data);
    }
}
