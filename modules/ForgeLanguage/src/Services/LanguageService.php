<?php

declare(strict_types=1);

namespace Modules\ForgeLanguage\Services;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\Helpers\Path;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Structure\StructureResolver;
use Forge\Core\Module\ModuleResourceResolver;
use InvalidArgumentException;

#[Injectable]
final class LanguageService
{
    private ?string $currentLanguage = null;

    /**
     * @var array<string, array<string, mixed>>
     */

    private array $loadedLanguages = [];

    public function __construct(
        private readonly Request $request,
        private readonly SessionInterface $session,
        private readonly Config $config,
        private readonly StructureResolver $structureResolver,
    ) {

    }

    /**
     * Current active language.
     */
    public function current(): string
    {
        if ($this->currentLanguage !== null) {
            return $this->currentLanguage;
        }

        return $this->currentLanguage = $this->resolveLanguage();
    }

    /**
     * Set current language.
     */
    public function set(string $language): void
    {
        if (!$this->isSupported($language)) {
            throw new InvalidArgumentException("Unsupported language: {$language}");
        }

        $this->session->set('language', $language);
        $this->currentLanguage = $language;
    }

    /**
     * Available languages.
     */
    public function available(): array
    {
        return $this->config->get('forge_language.languages', []);
    }

    public function language(string $code): ?array
    {
        return $this->available()[$code] ?? null;
    }

    /**
     * Check if language exists.
     */
    public function isSupported(string $language): bool
    {
        return array_key_exists($language, $this->available());
    }

    /**
     * Get translated term.
     */
    public function term(
        string $key,
        ?string $fallback = null,
        array $args = []
    ): string|array {
        $resource = ModuleResourceResolver::parse($key);
        $module = $resource->module;
        $termKey = $resource->name;

        $language = $this->current();

        $filePath = '';
        $dotNotation = $termKey;

        if (str_contains($termKey, '/')) {
            $lastSlashPos = strrpos($termKey, '/');
            $filePath = substr($termKey, 0, $lastSlashPos);
            $dotNotation = substr($termKey, $lastSlashPos + 1);
        }

        $cacheKey = ($module ?? 'app') . ':' . $language . ':' . $filePath;

        if (!isset($this->loadedLanguages[$cacheKey])) {
            $this->loadedLanguages[$cacheKey] = $this->loadLanguage(
                $language,
                $module,
                $filePath
            );
        }

        $terms = $this->loadedLanguages[$cacheKey];
        $value = $this->getNestedValue($terms, $dotNotation);

        if ($value === null) {
            return $fallback ?? $termKey;
        }

        if (is_callable($value)) {
            return (string) $value(...$args);
        }

        return is_array($value) ? $value : (string) $value;
    }

    private function getNestedValue(array $array, ?string $key): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Resolve active language.
     */
    private function resolveLanguage(): string
    {
        $queryLanguage = $this->request->query('lang');

        if (
            $queryLanguage !== null &&
            $this->isSupported($queryLanguage)
        ) {
            $this->session->set('language', $queryLanguage);
            return $queryLanguage;
        }

        $sessionLanguage = $this->session->get('language');

        if (
            is_string($sessionLanguage) &&
            $this->isSupported($sessionLanguage)
        ) {
            return $sessionLanguage;
        }


        $cookie = $this->request->cookie('language');

        if (
            $cookie !== null &&
            $this->isSupported($cookie->value)
        ) {
            return $cookie->value;
        }

        $browserLanguage = $this->detectBrowserLanguage();

        if ($browserLanguage !== null) {
            return $browserLanguage;
        }

        return $this->defaultLanguage();
    }

    /**
     * Detect browser language from Accept-Language header.
     */
    private function detectBrowserLanguage(): ?string
    {
        $header = $this->request->getHeader('accept-language');

        if ($header === null) {
            return null;
        }

        $language = strtolower(
            substr(trim($header), 0, 2)
        );

        return $this->isSupported($language)
            ? $language
            : null;
    }

    /**
     * Load language definitions.
     *
     * App definitions are loaded first.
     * Module definitions override app definitions.
     */
    private function loadLanguage(string $language, ?string $module = null, string $filePath = ''): array
    {
        $path = $this->resolveLanguagePath($module);

        $file = $filePath !== ''
            ? Path::resolve($path, $filePath, "{$language}.php")
            : Path::resolve($path, "{$language}.php");

        if (!file_exists($file)) {
            return [];
        }

        $loaded = require $file;
        return is_array($loaded) ? $loaded : [];
    }

    private function resolveLanguagePath(?string $module = null): string
    {
        if ($module === null) {
            return Path::resolve(
                BASE_PATH,
                $this->structureResolver->getAppPath('languages')
            );
        }

        $modulesRoot = $this->structureResolver->findModuleRoot($module);
        if ($modulesRoot === null) {
            $modulesRoot = $this->structureResolver->getModulesRoot();
        }

        return Path::resolve(
            BASE_PATH,
            $modulesRoot,
            $module,
            $this->structureResolver->getModulePath($module, 'languages')
        );
    }

    /**
     * Default language.
     */
    private function defaultLanguage(): string
    {
        return $this->config->get('forge_language.default', 'en');
    }
}
