<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Services;

use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\Structure\StructureResolver;

#[Injectable]
final class ErrorPageRenderer
{
    private const string DEFAULT_TEMPLATE = __DIR__ . '/../Templates/error_page.php';

    public function __construct(
        private readonly StructureResolver $structureResolver
    ) {
    }

    public function render(int $code, ?string $message = null): string
    {
        $template = $this->resolveTemplate($code);

        $errorCode = $code;
        $errorMessage = $message ?? $this->getDefaultMessage($code);
        $pageTitle = $this->getDefaultTitle($code);

        ob_start();
        require $template;
        return ob_get_clean();
    }

    public static function renderStatic(int $code, ?string $message = null): string
    {
        $template = self::DEFAULT_TEMPLATE;

        $errorCode = $code;
        $errorMessage = $message ?? self::getDefaultMessage($code);
        $pageTitle = self::getDefaultTitle($code);

        ob_start();
        require $template;
        return ob_get_clean();
    }

    private function resolveTemplate(int $code): string
    {
        $fileName = "{$code}.php";

        $appPath = $this->resolveAppErrorPagesPath();
        if ($appPath !== null) {
            $customTemplate = $appPath . '/' . $fileName;
            if (file_exists($customTemplate)) {
                return $customTemplate;
            }
        }

        $modulePath = $this->resolveModuleErrorPagesPath();
        if ($modulePath !== null) {
            $customTemplate = $modulePath . '/' . $fileName;
            if (file_exists($customTemplate)) {
                return $customTemplate;
            }
        }

        return self::DEFAULT_TEMPLATE;
    }

    private function resolveAppErrorPagesPath(): ?string
    {
        try {
            $paths = $this->structureResolver->getAppPaths('error_pages');
            foreach ($paths as $path) {
                $fullPath = BASE_PATH . '/' . $path;
                if (is_dir($fullPath)) {
                    return $fullPath;
                }
            }
        } catch (\InvalidArgumentException) {
        }

        return null;
    }

    private function resolveModuleErrorPagesPath(): ?string
    {
        try {
            $paths = $this->structureResolver->getModulePaths('ForgeRouter', 'error_pages');
            foreach ($paths as $path) {
                $fullPath = BASE_PATH . '/modules/ForgeRouter/' . $path;
                if (is_dir($fullPath)) {
                    return $fullPath;
                }
            }
        } catch (\InvalidArgumentException) {
        }

        return null;
    }

    public static function getDefaultMessage(int $code): string
    {
        return match ($code) {
            400 => 'The server could not understand your request.',
            401 => 'You are not authorized to view this page. Please log in.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for could not be found.',
            405 => 'The request method is not allowed for this resource.',
            408 => 'The request timed out. Please try again.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Something went wrong on our server. Please try again later.',
            502 => 'The server received an invalid response. Please try again later.',
            503 => 'The server is currently unavailable. Please try again later.',
            504 => 'The server timed out waiting for a response. Please try again later.',
            default => 'An unexpected error has occurred.',
        };
    }

    public static function getDefaultTitle(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'Error',
        };
    }
}
