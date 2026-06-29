<?php

declare(strict_types=1);

namespace Modules\ForgeNexus\Controllers;

use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
