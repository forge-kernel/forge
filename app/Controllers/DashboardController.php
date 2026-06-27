<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable]
#[UseMiddleware("web")]
final class DashboardController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct()
    {
        //
    }

    #[Endpoint("/dashboard")]
    #[Layout("ForgeComponents:wrappers/admin-default")]
    public function welcome(): Response
    {
        $data = [
            "title" => "Welcome to Forge Framework",
        ];

        return $this->view(view: "admin/default", data: []);
    }
}
