<?php

declare(strict_types=1);

namespace App\Modules\ForgeStaticGen;

use Forge\Core\Helpers\FileExistenceCache;

class LayoutBuilder
{
    public function render(string $layout, array $data): string
    {
        extract($data);
        ob_start();
        $layoutPath = $this->getLayoutPath($layout);
        if (!FileExistenceCache::exists($layoutPath)) {
            echo "Error: Layout file not found: " . $layoutPath . "\n";
        }
        include $layoutPath;
        $output = ob_get_clean();
        return $output;
    }

    private function getLayoutPath(string $name): string
    {
        return BASE_PATH . "/modules/ForgeStaticGen/src/templates/{$name}.layout.php";
    }

    public function renderComponent(string $componentName, array $data = []): string
    {
        extract($data);
        ob_start();
        $componentPath = $this->getComponentPath($componentName);
        if (!FileExistenceCache::exists($componentPath)) {
            echo "Error: Component file not found: " . $componentPath . "\n";
        }
        include $componentPath;
        $output = ob_get_clean();
        return $output;
    }

    private function getComponentPath(string $name): string
    {
        return BASE_PATH . "/modules/ForgeStaticGen/src/templates/components/{$name}.component.php";
    }
}
