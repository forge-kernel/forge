<?php

declare(strict_types=1);

namespace Modules\ForgeView;

use Forge\Core\DI\Container;
use Forge\Core\Debug\Metrics;
use Forge\Core\Structure\StructureResolver;
use Modules\ForgeView\ViewState;
use RuntimeException;

final class View
{
    private static ?ViewState $sharedState = null;
    public readonly ViewFinder $finder;

    public function __construct(
        private readonly Container $container,
        private ?string $viewPath = null,
        private ?string $componentPath = null,
        private readonly ?string $module = null,
    ) {
        $structureResolver = $this->container->has(StructureResolver::class)
            ? $this->container->get(StructureResolver::class)
            : null;

        if ($this->viewPath === null || $this->componentPath === null) {
            $basePath = defined("BASE_PATH") ? BASE_PATH : dirname(__DIR__, 5);

            try {
                $relViewPath = $structureResolver
                    ? $structureResolver->getAppPath("views")
                    : "app/UI/views";

                $relCompPath = $structureResolver
                    ? $structureResolver->getAppPath("components")
                    : "app/UI/views/components";

                if (str_starts_with($relViewPath, 'app/') && !is_dir(BASE_PATH . "/" . $relViewPath)) {
                    $altViewPath = 'src/' . substr($relViewPath, 4);
                    if (is_dir(BASE_PATH . "/" . $altViewPath)) {
                        $relViewPath = $altViewPath;
                    }
                }

                if (str_starts_with($relCompPath, 'app/') && !is_dir(BASE_PATH . "/" . $relCompPath)) {
                    $altCompPath = 'src/' . substr($relCompPath, 4);
                    if (is_dir(BASE_PATH . "/" . $altCompPath)) {
                        $relCompPath = $altCompPath;
                    }
                }

                $this->viewPath ??= BASE_PATH . "/" . $relViewPath;
                $this->componentPath ??= BASE_PATH . "/" . $relCompPath;
            } catch (\InvalidArgumentException $e) {
                $this->viewPath ??= $basePath . "/app/UI/views";
                $this->componentPath ??= $basePath . "/app/UI/views/components";
            }
        }

        $this->finder = new ViewFinder($structureResolver, $this->viewPath, $this->componentPath);
    }

    private static function getState(): ViewState
    {
        return self::$sharedState ??= new ViewState();
    }

    public static function suppressLayout(bool $suppress = true): void
    {
        self::getState()->setShouldSuppressLayout($suppress);
    }

    /**
     * @deprecated Use #[Layout] attribute on controller method instead.
     */
    public static function layout(
        string $name,
        array $props = [],
        array $slots = [],
        bool $loadFromModule = false,
        ?string $moduleName = null,
    ): void {
        if (self::getState()->shouldSuppressLayout()) {
            return;
        }
        if ($loadFromModule || $moduleName !== null) {
            @trigger_error(
                "The loadFromModule and moduleName parameters in View::layout() are deprecated. " .
                "Please use the Module:layout_name syntax instead.",
                E_USER_DEPRECATED
            );
            if ($moduleName && !str_contains($name, ':')) {
                $name = "{$moduleName}:{$name}";
            }
        }

        self::getState()->setLayout([
            "name" => $name,
            "props" => $props,
            "slots" => $slots,
        ]);
    }

    /**
     * @deprecated Use $layoutSlots array in view files instead.
     */
    public static function startSection(string $name): void
    {
        self::getState()->startSection($name);
    }

    /**
     * @deprecated Use $layoutSlots array in view files instead.
     */
    public static function endSection(): void
    {
        self::getState()->endSection();
    }

    /**
     * @deprecated Use $layoutSlots array in layout files instead.
     */
    public static function section(string $name): string
    {
        return self::getState()->getSection($name);
    }

    public static function slot(
        string $name = "default",
        string $default = "",
    ): string {
        return self::getState()->getSlot($name, $default);
    }

    public function render(string $view, array $data = [], ?string $layoutName = null): string
    {
        $state = self::getState();
        try {
            Metrics::start("view_render");
            $viewResult = $this->compileView($view, $data);
            Metrics::stop("view_render");

            $viewContent = $viewResult["content"];
            $layoutSlots = $viewResult["layoutSlots"];
            $layoutSections = $viewResult["layoutSections"];
            $layoutProps = $viewResult["layoutProps"];

            $resolvedLayoutName = $layoutName;

            if ($resolvedLayoutName === null) {
                $layoutInfo = $state->getLayout();
                if ($layoutInfo !== null) {
                    $resolvedLayoutName = $layoutInfo["name"] ?? null;
                    if ($resolvedLayoutName !== null) {
                        $layoutSlots = array_merge(
                            $layoutSlots,
                            $layoutInfo["slots"] ?? [],
                        );
                        $layoutProps = array_merge(
                            $layoutProps,
                            $layoutInfo["props"] ?? [],
                        );
                        $layoutSections = array_merge(
                            $layoutSections,
                            $state->getSections(),
                        );
                    }
                }
            }

            if ($resolvedLayoutName && !$state->shouldSuppressLayout()) {
                Metrics::start("view_layout_chain");
                $viewContent = $this->renderLayoutChain(
                    $viewContent,
                    $resolvedLayoutName,
                    $layoutSlots,
                    $layoutSections,
                    $layoutProps,
                );
                Metrics::stop("view_layout_chain");
            }

            return $viewContent;
        } finally {
            $state->reset();
        }
    }

    private function renderLayoutChain(
        string $content,
        string $layoutName,
        array $slots,
        array $sections,
        array $props,
        array $visited = [],
    ): string {
        Metrics::start("view_finder_findLayout");
        $resolvedPath = $this->finder->findLayout($layoutName, $this->module);
        Metrics::stop("view_finder_findLayout");

        if (isset($visited[$resolvedPath])) {
            $cycle = array_values($visited);
            $cycle[] = $layoutName;
            throw new \RuntimeException(
                "Circular layout reference detected: " . implode(' → ', $cycle)
            );
        }

        $visited[$resolvedPath] = $layoutName;

        Metrics::start("view_layout_execute");
        $result = $this->executeFile($resolvedPath, [
            "content" => $content,
            "layoutSlots" => $slots,
            "layoutSections" => $sections,
            "layoutProps" => $props,
        ], true);
        Metrics::stop("view_layout_execute");

        $parentLayout = $result["parentLayout"] ?? null;
        if ($parentLayout) {
            $mergedSlots = array_merge($slots, $result["layoutSlots"]);
            $mergedSections = array_merge($sections, $result["layoutSections"]);
            $mergedProps = array_merge($props, $result["layoutProps"]);
            return $this->renderLayoutChain(
                $result["content"],
                $parentLayout,
                $mergedSlots,
                $mergedSections,
                $mergedProps,
                $visited,
            );
        }

        return $result["content"];
    }

    private function compileView(
        string $view,
        array $data,
    ): array {
        Metrics::start("view_finder_findView");
        $viewFile = $this->finder->findView($view, $this->module);
        Metrics::stop("view_finder_findView");

        Metrics::start("view_execute_file");
        $result = $this->executeFile($viewFile, $data, true);
        Metrics::stop("view_execute_file");
        return $result;
    }

    private function executeFile(string $file, array|object|null $data, bool $captureLayoutVars = false): string|array
    {
        $vars = is_object($data) ? get_object_vars($data) : $data;
        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }

        if (is_object($data)) {
            $props = $data;
        } else {
            $props = $data ?? [];
        }

        ob_start();
        try {
            include $file;
            $content = ob_get_clean();

            if ($captureLayoutVars) {
                return [
                    "content" => $content,
                    "layoutSlots" => $layoutSlots ?? [],
                    "layoutSections" => $layoutSections ?? [],
                    "layoutProps" => $layoutProps ?? [],
                    "parentLayout" => $parentLayout ?? null,
                ];
            }

            return $content;
        } catch (\Throwable $e) {
            $buffer = ob_get_clean();
            throw new RuntimeException(
                "Error rendering view [{$file}]: {$e->getMessage()}\n\nBuffer Content:\n{$buffer}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    public static function viewComponent(
        string $path,
        array|object|null $props = [],
        ?string $module = null,
        array $slots = [],
    ): string {
        $view = new self(container: Container::getInstance(), module: $module);
        $processedSlots = $view->processSlots($slots);
        return $view->renderComponentView($path, $props, $processedSlots);
    }

    private function processSlots(array $slots): array
    {
        if (empty($slots)) {
            return [];
        }

        $processed = [];

        foreach ($slots as $name => $content) {
            if (is_array($content) && isset($content["name"])) {
                $processed[$name] = function () use ($content) {
                    $componentName = $content["name"];
                    $componentProps = $content["props"] ?? [];
                    $componentSlots = $content["slots"] ?? [];

                    $componentModule = null;
                    if (str_contains($componentName, ":")) {
                        [$componentModule, $componentName] = explode(
                            ":",
                            $componentName,
                            2,
                        );
                    }

                    $nestedSlots = $this->processSlots($componentSlots);

                    return $this->renderComponentView(
                        $componentName,
                        $componentProps,
                        $nestedSlots,
                        $componentModule,
                    );
                };

                continue;
            }

            $processed[$name] = fn() => (string) $content;
        }

        return $processed;
    }

    public function renderComponentView(
        string $viewSubPath,
        array|object|null $data = [],
        array $slots = [],
        ?string $module = null,
    ): string {
        $state = self::getState();
        $previousSlots = $state->getSlots();
        $state->setSlots($slots);

        $view =
            $module !== null
            ? new self(container: $this->container, module: $module)
            : $this;

        Metrics::start("view_finder_findComponent");
        $file = $view->finder->findComponent($viewSubPath, $view->module);
        Metrics::stop("view_finder_findComponent");

        Metrics::start("view_component_execute");
        $result = $view->executeFile($file, $data);
        Metrics::stop("view_component_execute");

        $state->setSlots($previousSlots);

        return $result;
    }
}
