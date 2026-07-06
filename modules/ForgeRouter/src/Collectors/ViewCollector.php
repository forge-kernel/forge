<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Collectors;

use Modules\ForgeRouter\Contracts\RequestCollectorInterface;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Provides;
use Forge\Traits\DataFormatter;

/**
 * View collector that tracks all views rendered during a request.
 * This collector is independent of any specific module and can be used by any module.
 */
#[Provides(RequestCollectorInterface::class, version: '1.0.0')]
final class ViewCollector implements RequestCollectorInterface
{
    use DataFormatter;

    private array $views = [];

    /**
     * Collect view data for the request.
     * This is called by the Kernel during request handling.
     *
     * @param Request $request The current request
     * @return array The collected view data
     */
    public function collect(Request $request): array
    {
        return $this->views;
    }

    /**
     * Add a view to the collection.
     *
     * @param string $viewPath The view file path
     * @param array|object $data The data passed to the view
     * @return void
     */
    public function addView(string $viewPath, array|object $data = []): void
    {
        $filePath = $viewPath;

        // Normalize path to be relative to BASE_PATH
        if (str_starts_with($filePath, BASE_PATH)) {
            $filePath = substr($filePath, strlen(BASE_PATH));
        }

        $this->views[] = [
            'path' => $filePath,
            'data' => $this->formatDebugData($data),
        ];
    }

    /**
     * Get all collected views.
     *
     * @return array
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * Reset the collector (clear all views).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->views = [];
    }
}
