<?php

declare(strict_types=1);

namespace Modules\ForgeTemplates\Traits;

use Forge\Core\DI\Container;
use Modules\ForgeTemplates\Injectable\TemplateManager;

trait TemplateHelper
{
    protected function useTemplate(string $template, array|object $data = []): string
    {
        return Container::getInstance()
            ->get(TemplateManager::class)
            ->useTemplate($template, $data);
    }
}
