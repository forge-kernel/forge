<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Controllers;

use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware('web')]
final class WelcomeController
{
    use ResponseHelper;
    use ViewHelper;

    #[Endpoint]
    #[Layout("ForgeWelcome:main")]
    public function index(): Response
    {
        return $this->view(view: "index", data: []);
    }
}
