<?php

declare(strict_types=1);

namespace Modules\ForgeView\Traits;

use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Router;
use Forge\Core\Contracts\ViewInterface;
use Forge\Core\DI\Container;
use Forge\Core\Structure\StructureResolver;
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

        $modulesNamespace = 'Modules';
        try {
            $container = Container::getInstance();
            if ($container->has(StructureResolver::class)) {
                $structureResolver = $container->get(StructureResolver::class);
                $modulesNamespace = $structureResolver->getModulesNamespace();
            }
        } catch (\Throwable) {
        }

        return ($namespaceParts[0] ?? null) === $modulesNamespace
            ? $namespaceParts[1]
            : null;
    }
}
