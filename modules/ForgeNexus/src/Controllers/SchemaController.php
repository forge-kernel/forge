<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/nexus')]
#[UseMiddleware('web')]
final class SchemaController
{
    use ResponseHelper;
    use ViewHelper;

    #[Endpoint("/schemas")]
    public function schemas(): Response
    {
        return $this->view(view: "schemas/index", data: []);
    }
}
