<?php

namespace App\Modules\ForgeDebugBar\Collectors;

use Forge\Traits\DataFormatter;

class ViewCollector implements CollectorInterface
{
    use DataFormatter;

    private array $views = [];

    public static function collect(...$args): array
    {
        return self::instance()->views;
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function addView(string $viewPath, array|object $data = []): void
    {
        $filePath = $viewPath;
        if ($filePath !== null) {
            if (strpos($filePath, BASE_PATH) === 0) {
                $filePath = substr($filePath, strlen(BASE_PATH));
            }
        }

        $this->views[] = [
            'path' => $filePath,
            'data' => $this->formatDebugData($data),
        ];
    }
}
