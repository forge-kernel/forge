<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Collectors;

use Modules\ForgeRouter\Contracts\RequestCollectorInterface;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Provides;

/**
 * View collector that tracks all views rendered during a request.
 * This collector is independent of any specific module and can be used by any module.
 */
#[Provides(RequestCollectorInterface::class, version: '1.0.0')]
final class ViewCollector implements RequestCollectorInterface
{
    private array $views = [];

    /**
     * Collect view data for the request.
     */
    public function collect(Request $request): array
    {
        return $this->views;
    }

    /**
     * Add a view to the collection.
     */
    public function addView(string $viewPath, array|object $data = []): void
    {
        $filePath = $viewPath;

        if (str_starts_with($filePath, BASE_PATH)) {
            $filePath = substr($filePath, strlen(BASE_PATH));
        }

        $this->views[] = [
            'path' => $filePath,
        ];
    }

    public function getViews(): array
    {
        return $this->views;
    }

    public function reset(): void
    {
        $this->views = [];
    }
}
