<?php

declare(strict_types=1);

namespace Modules\ForgeTemplates\Injectable;

use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\Structure\StructureResolver;
use Modules\ForgeTemplates\TemplateFinder;
use RuntimeException;

#[Injectable]
final class TemplateManager
{
    private readonly TemplateFinder $finder;

    public function __construct(
        private readonly StructureResolver $structureResolver,
    ) {
        $appTemplatePath = BASE_PATH . "/" . $this->structureResolver->getAppPath("templates");

        $this->finder = new TemplateFinder($this->structureResolver, $appTemplatePath);
    }

    public function useTemplate(string $template, array|object $data = []): string
    {
        $file = $this->finder->find($template);

        $content = $this->renderFile($file, $data);

        $layout = $this->extractedLayout;
        $slots = $this->extractedSlots;

        $this->extractedLayout = null;
        $this->extractedSlots = [];

        if ($layout !== null) {
            $layoutFile = $this->finder->findLayout($layout);
            $content = $this->renderFile($layoutFile, [
                'content' => $content,
                'slots' => $slots,
            ]);
        }

        return $content;
    }

    private ?string $extractedLayout = null;
    private array $extractedSlots = [];

    public function layout(string $name): void
    {
        $this->extractedLayout = $name;
    }

    public function slot(string $name, string $default = ''): string
    {
        return $this->extractedSlots[$name] ?? $default;
    }

    private function renderFile(string $file, array|object $data): string
    {
        $props = is_object($data) ? $data : (object) $data;
        $vars = get_object_vars($props);

        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }

        $props = $props;

        ob_start();
        try {
            include $file;
            $content = ob_get_clean();
        } catch (\Throwable $e) {
            $buffer = ob_get_clean();
            $runtimeException = new RuntimeException(
                "Error rendering template [{$file}]: {$e->getMessage()}\n\nBuffer Content:\n{$buffer}",
                (int) $e->getCode(),
                $e,
            );
            if (function_exists('collect_exception')) {
                collect_exception($runtimeException);
            }
            throw $runtimeException;
        }

        if (isset($layout) && $layout !== null) {
            $this->extractedLayout = $layout;
        }

        return $content;
    }
}
