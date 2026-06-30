<?php

declare(strict_types=1);

namespace Modules\ForgeView\Traits;

use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Router;
use Forge\Core\Contracts\ViewInterface;
use Forge\Core\DI\Container;
use ReflectionClass;

trait ViewHelper
{
    protected function view(string $view, array $data = [], ?string $layout = null): Response
    {
        $view = 'pages/' . $view;

        if ($layout === null) {
            try {
                $route = Router::getInstance()->getCurrentRoute();
                $layout = $route["layout"] ?? null;
            } catch (\Throwable) {
                $layout = null;
            }
        }

        $module = $this->detectModule();

        $viewName = $module ? "{$module}:{$view}" : $view;
        $content = Container::getInstance()
            ->get(ViewInterface::class)
            ->render($viewName, $data, $layout);
        return new Response($content);
    }

    protected function detectModule(): ?string
    {
        $namespaceParts = explode(
            "\\",
            (new ReflectionClass($this))->getNamespaceName()
        );
        return ($namespaceParts[0] ?? null) === "Modules"
            ? $namespaceParts[1]
            : null;
    }
}
