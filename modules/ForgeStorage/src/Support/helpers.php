<?php

declare(strict_types=1);

use App\Modules\ForgeStorage\Utils\UploadSignature;
use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;

if (!function_exists('upload_input')) {
  function upload_input(string $name, ?string $location = null, array $options = []): string
  {
    $container = Container::getInstance();
    $config = $container->make(Config::class);
    $session = $container->make(SessionInterface::class);
    $signatureService = $container->make(UploadSignature::class);

    $locationConfig = [];
    if ($location !== null) {
      $locations = $config->get('forge_storage.locations', []);
      $locationConfig = $locations[$location] ?? [];
    }

    $signature = $signatureService->generate($location ?? '', $locationConfig, $session);

    $accept = $options['accept'] ?? null;
    if ($accept === null && !empty($locationConfig['allowed_types'])) {
      $allowedTypes = $locationConfig['allowed_types'];
      if (is_array($allowedTypes)) {
        $accept = implode(',', $allowedTypes);
      }
    }

    $multiple = $options['multiple'] ?? false;
    $multipleAttr = $multiple ? ' multiple' : '';

    $includeCsrf = $options['csrf'] ?? true;

    $attrs = [];
    foreach ($options as $key => $value) {
      if (!in_array($key, ['accept', 'multiple', 'csrf'])) {
        if (is_bool($value)) {
          if ($value) {
            $attrs[] = $key;
          }
        } else {
          $attrs[] = $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }
      }
    }

    $attrsStr = !empty($attrs) ? ' ' . implode(' ', $attrs) : '';

    $acceptAttr = $accept ? ' accept="' . htmlspecialchars($accept, ENT_QUOTES, 'UTF-8') . '"' : '';

    $html = '<input type="file" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' data-upload-endpoint="/__upload"';
    $html .= ' data-signature="' . htmlspecialchars($signature, ENT_QUOTES, 'UTF-8') . '"';
    $html .= $acceptAttr;
    $html .= $multipleAttr;
    $html .= $attrsStr;
    $html .= '>';

    $html .= '<input type="hidden" name="signature" value="' . htmlspecialchars($signature, ENT_QUOTES, 'UTF-8') . '">';

    if ($includeCsrf) {
      $html .= csrf_input();
    }

    return $html;
  }
}
