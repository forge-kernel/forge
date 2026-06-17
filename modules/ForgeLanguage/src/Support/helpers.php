<?php
use App\Modules\ForgeLanguage\Services\LanguageService;
use Forge\Core\DI\Container;

if (!function_exists('languageTerm')) {
    function languageTerm(string $key, ?string $fallback = null, ?array $args = []): string|array
    {
        /**
         * @var LanguageService $langService
         */
        $langService = Container::getInstance()->get(LanguageService::class);

        return $langService->term($key, $fallback, $args);
    }
}

if (!function_exists('current_language')) {
    function current_language(): string
    {
        /**
         * @var LanguageService $langService
         */
        $langService = Container::getInstance()->get(LanguageService::class);

        return $langService->current();
    }
}

if (!function_exists('available_languages')) {
    function available_languages(): array
    {
        /**
         * @var LanguageService $langService
         */
        $langService = Container::getInstance()->get(LanguageService::class);

        return $langService->available();
    }
}