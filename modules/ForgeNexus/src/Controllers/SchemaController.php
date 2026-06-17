<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class SchemaController
{
    use ControllerHelper;

    #[Route("/nexus/schemas")]
    public function schemas(): Response
    {
        return $this->view(view: "pages/schemas/index", data: []);
    }
}
