<?php

namespace App\Modules\ForgeDebugBar;

use Forge\Core\Config\Config;
use App\Modules\ForgeRouter\Contracts\DebugBarInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Response;

class DebugBar implements DebugBarInterface
{
  private static ?self $instance = null;
  private array $collectors = [];
  private float $startTime;
  private int $startMemory;

  private function __construct()
  {
    $this->startTime = microtime(true);
    $this->startMemory = memory_get_usage();
  }

  public static function getInstance(): self
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function addCollector(string $name, callable $collector): void
  {
    $this->collectors[$name] = $collector;
  }

  public function injectDebugBarIfEnabled(Response $response, Container $container): Response
  {
    if (!$this->shouldEnableDebugBar($container)) {
      return $response;
    }

    $contentType = $response->getHeader('Content-Type') ?? '';

    if (
      !empty($contentType) &&
      !str_contains(strtolower($contentType), 'text/html')
    ) {
      return $response;
    }

    $content = $response->getContent();

    if (is_string($content) && str_starts_with(trim($content), '{"html":')) {
      return $response;
    }

    if (!is_string($content) || str_contains($content, '</body>') === false) {
      return $response;
    }

    $debugBarHtml = $this->render();
    $injected = $this->injectDebugBarIntoHtml($content, $debugBarHtml, $container);
    $response->setContent($injected);

    return $response;
  }

  public function shouldEnableDebugBar(Container $container): bool
  {
    $forgeDebug = env('APP_DEBUG');
    /** @var Config $config */
    $config = $container->get(Config::class);
    $configEnabled = $config->get('forge_debug_bar.enabled', true);
    return $configEnabled && $forgeDebug;
  }

  public function render(): string
  {
    $modulePath = BASE_PATH . '/modules/ForgeDebugBar/src/views/debugbar.php';
    if (!file_exists($modulePath)) {
      return '';
    }
    ob_start();
    extract(['data' => $this->getData()]);
    include $modulePath;
    return ob_get_clean();
  }

  public function getData(): array
  {
    $data = [];

    foreach ($this->collectors as $name => $collectorCallable) {
      $collectorData = call_user_func($collectorCallable, $this->startTime);
      $data[$name] = $collectorData;
    }

    $data['php_version'] = phpversion();

    return $data;
  }

  public function injectDebugBarIntoHtml(string $htmlContent, string $debugBarHtml, Container $container): string
  {
    $cssLinkTag = sprintf('<link rel="stylesheet" href="/assets/modules/ForgeDebugBar/css/debugbar.css">');
    $jsScriptTag = sprintf('<script src="/assets/modules/ForgeDebugBar/js/debugbar.js"></script>');

    if (!is_string($htmlContent)) {
      return $debugBarHtml;
    }

    $injectionPoint = strripos($htmlContent, '</body>');

    if ($injectionPoint !== false) {
      $injectedContent = substr($htmlContent, 0, $injectionPoint) .
        $cssLinkTag . "\n" .
        $debugBarHtml . "\n" .
        $jsScriptTag . "\n" .
        substr($htmlContent, $injectionPoint);
      return $injectedContent;
    } else {
      return $htmlContent . "\n" . $cssLinkTag . "\n" . $debugBarHtml . "\n" . $jsScriptTag;
    }
  }
}
