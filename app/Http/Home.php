<?php

declare(strict_types=1);

namespace App\Http;

use Capability\ForgeHtmx\Traits\HtmxResponseHelper;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeView\Traits\ViewHelper;
use Modules\ForgeLanguage\Definitions\LanguageSwitcherDefinition;

#[UseMiddleware("web")]

final class Home
{
    use ViewHelper;
    use HtmxResponseHelper;

    #[Endpoint("/")]
    #[Layout("main")]
    public function home(): Response
    {
        debug_log('message from home', 'info');

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
