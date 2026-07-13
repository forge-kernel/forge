<?php

declare(strict_types=1);

use Forge\Core\DI\Container;
use Modules\ForgeTemplates\Injectable\TemplateManager;

if (!function_exists('useTemplate')) {
    function useTemplate(string $template, array|object $data = []): string
    {
        return Container::getInstance()
            ->get(TemplateManager::class)
            ->useTemplate($template, $data);
    }
}
