<?php

declare(strict_types=1);

namespace App\Http;

use Modules\ForgeHtmx\Traits\HtmxResponseHelper;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeView\Traits\ViewHelper;
use Modules\ForgeLanguage\Definitions\LanguageSwitcherDefinition;

#[Routable]
#[UseMiddleware("web")]
final class Home
{
    use ViewHelper;
    use HtmxResponseHelper;

    #[Endpoint("/")]
    #[Layout("main")]
    public function home(): Response
    {
        return $this->view(view: 'home/index', data: [
            "title" => "Welcome to Forge Kernel",
        ]);
    }

    #[Endpoint(path: "/languages", method: "POST")]
    public function languages(): Response
    {
        return $this->htmxFragment(component(
            name: 'ForgeLanguage:language-switcher',
            props: [
                'definition' => new LanguageSwitcherDefinition(
                    showFlags: true,
                    showLabels: true,
                    showCodes: false,
                )
            ]
        ));
    }
}
