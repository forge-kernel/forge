<?php

declare(strict_types=1);

namespace App\Http;

use Capability\ForgeHtmx\Traits\HtmxResponseHelper;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeView\Traits\ViewHelper;

#[UseMiddleware("web")]

final class Sprinkle
{
    use ViewHelper;
    use HtmxResponseHelper;

    #[Endpoint("/sprinkle")]
    #[Layout("root")]
    public function sprinkle(): Response
    {
        return $this->view(view: 'sprinkle/index');
    }
    #[Endpoint("/sprinkle/raw")]
    #[Layout("root")]
    public function raw(): Response
    {
        return $this->view(view: 'sprinkle/raw');
    }
}
