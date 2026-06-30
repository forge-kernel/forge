<?php

declare(strict_types=1);

namespace Modules\ForgeWelcome\Http;

use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware('web')]
final class Welcome
{
    use ResponseHelper;
    use ViewHelper;

    #[Endpoint("/")]
    #[Layout("ForgeWelcome:main")]
    public function home(): Response
    {
        return $this->view(view: "welcome", data: []);
    }
}
