<?php

declare(strict_types=1);

namespace App\Http;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
final class Home
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct()
    {
    }

    #[Endpoint("/")]
    #[Layout("main")]
    public function home(Request $request): Response
    {
        $data = [
            "title" => "Welcome to Forge Kernel",
        ];

        return $this->view(view: 'home/index', data: $data);
    }
}
