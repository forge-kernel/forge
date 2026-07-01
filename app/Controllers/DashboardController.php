<?php

declare(strict_types=1);

namespace App\Controllers;

use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
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

        return $this->view(view: "admin/default", data: $data);
    }
}
