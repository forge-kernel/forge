<?php

declare(strict_types=1);

namespace Modules\ForgeWelcome\Controllers;

use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
