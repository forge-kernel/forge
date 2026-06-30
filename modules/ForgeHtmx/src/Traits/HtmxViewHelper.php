<?php

declare(strict_types=1);

namespace Modules\ForgeHtmx\Traits;

use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeView\View;

trait HtmxViewHelper
{
    protected function htmxView(string $view, array $data = [], ?string $partial = null): Response
    {
        if ($this->isHtmxRequest()) {
            View::suppressLayout(true);
            return $this->view($partial ?? $view, $data);
        }

        return $this->view($view, $data);
    }

    private function isHtmxRequest(): bool
    {
        $container = Container::getInstance();

        if (!$container->has(Request::class)) {
            return false;
        }

        return $container->get(Request::class)->hasHeader('HX-Request');
    }
}
