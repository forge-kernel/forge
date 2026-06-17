<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Services;

use Forge\CLI\Traits\OutputHelper;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Services\TemplateGenerator;
use Forge\Traits\NamespaceHelper;
use Forge\Traits\StringHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

#[Service]
final class ConfigGeneratorService
{
  use OutputHelper;
  use NamespaceHelper;
  use StringHelper;

  public function __construct(
    private readonly ?TemplateGenerator $templateGenerator = null
  ) {
  }

  public const MODE_MODULE_DEFAULTS = 'defaults';
  public const MODE_PUBLISH_CONFIG = 'publish';
  public const MODE_ENV_OVERRIDES = 'env';

  private const string CONFIG_DIR = BASE_PATH . '/config/';
  private const string ENV_FILE = BASE_PATH . '/.env';

  public function generateConfigFromModule(string $modulePath, string $moduleName, ?string $mode = null): void
  {
    $moduleClassName = $this->findModuleClass($modulePath);
    if (!$moduleClassName) {
      $this->warning("Could not find module class for {$moduleName}, skipping config generation.");
      return;
    }

    $configDefaults = $this->extractConfigDefaults($moduleClassName);
    if (!$configDefaults) {
      $this->info("Module {$moduleName} does not have ConfigDefaults attribute, skipping config generation.");
      return;
    }

    if ($mode === null && $this->templateGenerator !== null) {
      $this->generateConfigFromModuleInteractive($modulePath, $moduleName, $configDefaults);
      return;
    }

    $configKey = $this->convertModuleNameToConfigKey($moduleName);
    $effectiveMode = $mode ?? self::MODE_ENV_OVERRIDES;

    switch ($effectiveMode) {
      case self::MODE_PUBLISH_CONFIG:
        $this->info("Generating config file for module: {$moduleName}");
        $configFilePath = self::CONFIG_DIR . $configKey . '.php';
        $this->generateConfigFile($configKey, $configDefaults, $configFilePath);
        $this->success("Config file generated: {$configKey}.php");
        break;

      case self::MODE_ENV_OVERRIDES:
        $this->info("Using .env overrides for module: {$moduleName}");
        break;

      case self::MODE_MODULE_DEFAULTS:
      default:
        $this->info("Using module defaults for module: {$moduleName}");
        break;
    }
  }

  private function generateConfigFromModuleInteractive(string $modulePath, string $moduleName, array $configDefaults): void
  {
    $mode = $this->promptConfigGenerationMode();
    if ($mode === null) {
      $this->info("Config generation cancelled.");
      return;
    }

    $configKey = $this->convertModuleNameToConfigKey($moduleName);

    switch ($mode) {
      case self::MODE_PUBLISH_CONFIG:
        $this->info("Generating config file for module: {$moduleName}");
        $configFilePath = self::CONFIG_DIR . $configKey . '.php';
        $this->generateConfigFile($configKey, $configDefaults, $configFilePath);
        $this->success("Config file generated: {$configKey}.php");

        $envVars = $this->extractEnvVarsFromDefaults($configDefaults, $configKey);
        if (!empty($envVars)) {
          $selectedVars = $this->promptEnvVariableSelection($envVars);
          if ($selectedVars !== null && !empty($selectedVars)) {
            $this->updateEnvFile($selectedVars, true);
          } elseif ($selectedVars === null) {
            $this->showEnvVarDocumentation($moduleName, $envVars);
          }
        }
        break;

      case self::MODE_ENV_OVERRIDES:
        $envVars = $this->extractEnvVarsFromDefaults($configDefaults, $configKey);
        if (!empty($envVars)) {
          $selectedVars = $this->promptEnvVariableSelection($envVars);
          if ($selectedVars !== null && !empty($selectedVars)) {
            $this->updateEnvFile($selectedVars, true);
          } elseif ($selectedVars === null) {
            $this->showEnvVarDocumentation($moduleName, $envVars);
          }
        }
        break;

      case self::MODE_MODULE_DEFAULTS:
      default:
        $this->info("Using module defaults for module: {$moduleName}");
        break;
    }
  }

  private function promptConfigGenerationMode(): ?string
  {
    if ($this->templateGenerator === null) {
      return self::MODE_ENV_OVERRIDES;
    }

    $options = [
      'Use module defaults' => self::MODE_MODULE_DEFAULTS,
      'Publish config file' => self::MODE_PUBLISH_CONFIG,
      'Use .env overrides' => self::MODE_ENV_OVERRIDES,
    ];

    $descriptions = [
      'Use module defaults' => 'No config files cluttering config/',
      'Publish config file' => 'For editing config files or versioning them',
      'Use .env overrides' => 'Keep dynamic values outside the code',
    ];

    $this->line("");
    $this->info("How would you like to handle configuration for this module?");
    $this->line("");

    foreach ($options as $label => $value) {
      $desc = $descriptions[$label] ?? '';
      $this->line("  • {$label}: {$desc}");
    }

    $this->line("");

    $selected = $this->templateGenerator->selectFromList(
      "Select configuration mode",
      array_keys($options),
      'Use .env overrides'
    );

    if ($selected === null) {
      return null;
    }

    return $options[$selected] ?? null;
  }

  private function promptEnvVariableSelection(array $envVars): ?array
  {
    if ($this->templateGenerator === null || empty($envVars)) {
      return null;
    }

    $this->line("");
    $choice = $this->templateGenerator->selectFromList(
      "Would you like to populate your .env file?",
      ['Add all variables', 'Select by category', 'Select individual variables', 'Skip'],
      'Skip'
    );

    if ($choice === null || $choice === 'Skip') {
      return null;
    }

    if ($choice === 'Add all variables') {
      return $envVars;
    }

    $categorized = $this->categorizeEnvVars($envVars);

    if ($choice === 'Select by category') {
      $categories = array_keys($categorized);
      if (empty($categories)) {
        return $envVars;
      }

      $selectedCategories = $this->templateGenerator->selectMultipleFromList(
        "Select categories to add",
        $categories
      );

      if ($selectedCategories === null || empty($selectedCategories)) {
        return null;
      }

      $selectedVars = [];
      foreach ($selectedCategories as $category) {
        if (isset($categorized[$category])) {
          $selectedVars = array_merge($selectedVars, $categorized[$category]);
        }
      }

      return $selectedVars;
    }

    if ($choice === 'Select individual variables') {
      $allVarKeys = array_keys($envVars);
      $selectedKeys = $this->templateGenerator->selectMultipleFromList(
        "Select variables to add",
        $allVarKeys
      );

      if ($selectedKeys === null || empty($selectedKeys)) {
        return null;
      }

      $selectedVars = [];
      foreach ($selectedKeys as $key) {
        if (isset($envVars[$key])) {
          $selectedVars[$key] = $envVars[$key];
        }
      }

      return $selectedVars;
    }

    return null;
  }

  private function categorizeEnvVars(array $envVars): array
  {
    $categorized = [];

    foreach ($envVars as $key => $value) {
      $parts = explode('_', $key);
      $category = 'General';

      if (count($parts) > 1) {
        $firstPart = strtoupper($parts[0]);
        if (in_array($firstPart, ['EMAIL', 'SMS', 'PUSH', 'S3', 'AWS', 'JWT', 'PASSWORD', 'AUTH'])) {
          $category = ucfirst(strtolower($firstPart));
          if ($firstPart === 'AWS') {
            $category = 'S3';
          }
        } elseif (count($parts) > 2) {
          $category = ucfirst(strtolower($parts[0])) . ' > ' . ucfirst(strtolower($parts[1]));
        } else {
          $category = ucfirst(strtolower($parts[0]));
        }
      }

      if (!isset($categorized[$category])) {
        $categorized[$category] = [];
      }

      $categorized[$category][$key] = $value;
    }

    return $categorized;
  }

  private function showEnvVarDocumentation(string $moduleName, array $envVars): void
  {
    if (empty($envVars)) {
      return;
    }

    $categorized = $this->categorizeEnvVars($envVars);
    $messages = [];
    $messages[] = "The following environment variables can be added to your .env file:";
    $messages[] = "";

    $totalVars = count($envVars);
    $showAll = $totalVars <= 10;

    if ($showAll) {
      foreach ($envVars as $key => $defaultValue) {
        $formattedValue = is_string($defaultValue) && !empty($defaultValue) ? $defaultValue : '""';
        $messages[] = "  {$key}={$formattedValue}";
      }
    } else {
      foreach ($categorized as $category => $vars) {
        $messages[] = "  {$category}:";
        $varCount = count($vars);
        if ($varCount <= 5) {
          foreach ($vars as $key => $defaultValue) {
            $formattedValue = is_string($defaultValue) && !empty($defaultValue) ? $defaultValue : '""';
            $messages[] = "    {$key}={$formattedValue}";
          }
        } else {
          $messages[] = "    ({$varCount} variables)";
          $sample = array_slice($vars, 0, 3, true);
          foreach ($sample as $key => $defaultValue) {
            $formattedValue = is_string($defaultValue) && !empty($defaultValue) ? $defaultValue : '""';
            $messages[] = "    {$key}={$formattedValue}";
          }
          $messages[] = "    ... and " . ($varCount - 3) . " more";
        }
        $messages[] = "";
      }
    }

    $this->showInfoBox("Environment Variables for {$moduleName}", $messages);
  }

  private function extractEnvVarsFromDefaults(array $configDefaults, string $configKey): array
  {
    $configData = $configDefaults;
    if (isset($configDefaults[$configKey]) && is_array($configDefaults[$configKey])) {
      $configData = $configDefaults[$configKey];
    }

    $envVars = [];
    $this->traverseConfigForEnvVarsFromDefaults($configData, '', $envVars, $configKey);
    return $envVars;
  }

  private function traverseConfigForEnvVarsFromDefaults(array $config, string $prefix, array &$envVars, string $configKey): void
  {
    foreach ($config as $key => $value) {
      $currentKey = $prefix ? "{$prefix}.{$key}" : $key;

      if (is_array($value)) {
        $this->traverseConfigForEnvVarsFromDefaults($value, $currentKey, $envVars, $configKey);
      } else {
        $envKey = $this->buildEnvKey($configKey, $currentKey);
        if ($envKey !== null) {
          $defaultValue = is_string($value) ? $value : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
          if (!isset($envVars[$envKey])) {
            $envVars[$envKey] = $defaultValue;
          }
        }
      }
    }
  }

  private function buildEnvKey(string $configKey, string $configPath): string
  {
    $configKeyNormalized = str_replace('-', '_', $configKey);
    $moduleUpper = strtoupper($configKeyNormalized);

    $pathParts = explode('.', $configPath);
    $variableParts = [];
    foreach ($pathParts as $part) {
      $partNormalized = str_replace('-', '_', $part);
      $variableParts[] = strtoupper($partNormalized);
    }
    $variableName = implode('_', $variableParts);

    return "{$moduleUpper}_{$variableName}";
  }

  private function findModuleClass(string $modulePath): ?string
  {
    $srcPath = $modulePath . '/src';
    if (!is_dir($srcPath)) {
      return null;
    }

    $directoryIterator = new RecursiveDirectoryIterator($srcPath);
    $iterator = new RecursiveIteratorIterator($directoryIterator);

    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        $namespace = $this->getNamespaceFromFile($file->getRealPath(), BASE_PATH);
        if ($namespace) {
          $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
          try {
            if (class_exists($className)) {
              $reflectionClass = new ReflectionClass($className);
              $attributes = $reflectionClass->getAttributes(Module::class);
              if (!empty($attributes)) {
                return $className;
              }
            }
          } catch (ReflectionException $e) {
            // Continue searching
            continue;
          }
        }
      }
    }

    return null;
  }

  private function extractConfigDefaults(string $moduleClassName): ?array
  {
    try {
      $reflectionClass = new ReflectionClass($moduleClassName);
      $attributes = $reflectionClass->getAttributes(ConfigDefaults::class);

      if (empty($attributes)) {
        return null;
      }

      $configDefaultsInstance = $attributes[0]->newInstance();
      return $configDefaultsInstance->defaults ?? null;
    } catch (ReflectionException $e) {
      $this->error("Error extracting ConfigDefaults from {$moduleClassName}: " . $e->getMessage());
      return null;
    }
  }

  private function convertModuleNameToConfigKey(string $moduleName): string
  {
    $normalized = str_replace('-', '_', $moduleName);
    return $this->toSnakeCase($normalized);
  }

  private function generateConfigFile(string $configKey, array $defaults, string $configFilePath): void
  {
    $existingConfig = [];
    if (file_exists($configFilePath)) {
      $existingConfig = require $configFilePath;
      if (!is_array($existingConfig)) {
        $existingConfig = [];
      }
    }

    if (isset($defaults[$configKey]) && is_array($defaults[$configKey])) {
      $defaults = $defaults[$configKey];
    }

    $mergedConfig = $this->mergeConfigArrays($defaults, $existingConfig);
    $phpCode = $this->generateConfigPhpCode($mergedConfig);

    $configDir = dirname($configFilePath);
    if (!is_dir($configDir)) {
      mkdir($configDir, 0755, true);
    }

    file_put_contents($configFilePath, $phpCode);
  }

  private function mergeConfigArrays(array $defaults, array $existing): array
  {
    $result = $existing;

    foreach ($defaults as $key => $value) {
      if (!isset($result[$key])) {
        // Key doesn't exist, add it
        $result[$key] = $value;
      } elseif (is_array($value) && is_array($result[$key])) {
        // Both are arrays, recursively merge
        $result[$key] = $this->mergeConfigArrays($value, $result[$key]);
      }
      // If key exists and is not an array, keep existing value
    }

    return $result;
  }

  private function generateConfigPhpCode(array $config): string
  {
    $code = "<?php\n\n";
    $code .= "declare(strict_types=1);\n\n";
    $code .= "return " . $this->arrayToPhpCode($config, 0) . ";\n";

    return $code;
  }

  private function arrayToPhpCode(array $array, int $indent = 0, string $prefix = ''): string
  {
    $indentStr = str_repeat('  ', $indent);
    $nextIndentStr = str_repeat('  ', $indent + 1);
    $lines = ["[\n"];

    foreach ($array as $key => $value) {
      $keyStr = is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)
        ? "'{$key}'"
        : var_export($key, true);

      $currentPrefix = $prefix ? "{$prefix}.{$key}" : $key;

      if (is_array($value)) {
        $valueStr = $this->arrayToPhpCode($value, $indent + 1, $currentPrefix);
        $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},\n";
      } else {
        $envKey = $this->inferEnvKey($key, $prefix);
        $valueStr = $this->valueToPhpCode($value, $envKey);
        $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},\n";
      }
    }

    $lines[] = "{$indentStr}";
    return implode('', $lines);
  }

  private function valueToPhpCode($value, ?string $envKey = null): string
  {
    if (is_string($value) && preg_match('/^env\s*\(/', $value)) {
      return $value;
    }

    if (is_string($value) && str_contains($value, 'BASE_PATH')) {
      if (preg_match('/BASE_PATH\s*\./', $value)) {
        return $value;
      }
      return var_export($value, true);
    }

    if ($envKey !== null && !is_array($value)) {
      $defaultValue = $this->formatDefaultValue($value);
      return "env('{$envKey}', {$defaultValue})";
    }

    return var_export($value, true);
  }

  private function formatDefaultValue($value): string
  {
    if (is_string($value)) {
      return var_export($value, true);
    }
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
      return 'null';
    }
    return var_export($value, true);
  }

  /**
   * Infer environment variable key from config key path
   * Matches the actual env() usage patterns in modules:
   * - ForgeWire: forge_wire.use_minified -> FORGE_WIRE_USE_MINIFIED (includes full module prefix)
   * - ForgeLogger: forge_logger.driver -> LOGGER_DRIVER (removes "forge_" prefix)
   * - ForgeAuth: forge_auth.jwt.enabled -> JWT_ENABLED (uses nested path only)
   *
   * @param string $key Current key
   * @param string $prefix Key prefix (e.g., "forge_wire" or "forge_auth.jwt")
   * @return string|null Inferred env key or null
   */
  private function inferEnvKey(string $key, string $prefix): ?string
  {
    $fullPath = $prefix ? "{$prefix}.{$key}" : $key;
    $parts = explode('.', $fullPath);

    if (count($parts) === 1) {
      return strtoupper($key);
    }

    $firstPart = $parts[0];
    $isModulePrefix = str_starts_with($firstPart, 'forge_');

    if (!$isModulePrefix) {
      return strtoupper(implode('_', $parts));
    }

    if (count($parts) === 2) {
      $moduleName = str_replace('forge_', '', $firstPart);
      $secondPart = $parts[1];

      $simpleKeys = ['driver', 'path', 'provider', 'root_path', 'public_path', 'use_minified'];
      if (in_array($secondPart, $simpleKeys)) {
        if ($secondPart === 'use_minified') {
          return strtoupper($firstPart . '_' . $secondPart);
        }
        return strtoupper($moduleName . '_' . $secondPart);
      }

      return strtoupper(implode('_', $parts));
    }

    $relevantParts = array_slice($parts, 1);
    return strtoupper(implode('_', $relevantParts));
  }

  private function extractEnvVars(array $config): array
  {
    $envVars = [];
    $this->traverseConfigForEnvVars($config, '', $envVars);
    return $envVars;
  }

  private function traverseConfigForEnvVars(array $config, string $prefix, array &$envVars): void
  {
    foreach ($config as $key => $value) {
      $currentKey = $prefix ? "{$prefix}.{$key}" : $key;

      if (is_array($value)) {
        $this->traverseConfigForEnvVars($value, $currentKey, $envVars);
      } elseif (is_string($value) && preg_match("/env\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*(.+?)\s*\)/", $value, $matches)) {
        $envKey = $matches[1];
        $defaultValue = trim($matches[2], " '\"");

        if (!isset($envVars[$envKey])) {
          $envVars[$envKey] = $defaultValue;
        }
      }
    }
  }

  private function updateEnvFile(array $envVars, bool $interactive = false): void
  {
    if (empty($envVars)) {
      return;
    }

    $envContent = '';
    $existingVars = [];
    $existingVarValues = [];

    if (file_exists(self::ENV_FILE)) {
      $envContent = file_get_contents(self::ENV_FILE);
      $lines = explode("\n", $envContent);

      foreach ($lines as $line) {
        $lineTrimmed = trim($line);
        if (empty($lineTrimmed) || str_starts_with($lineTrimmed, '#')) {
          continue;
        }

        if (strpos($lineTrimmed, '=') !== false) {
          [$key, $value] = explode('=', $lineTrimmed, 2);
          $keyNormalized = trim($key);
          $existingVars[$keyNormalized] = true;
          $existingVarValues[$keyNormalized] = trim($value ?? '');
        }
      }
    }

    $varsToAdd = [];
    $varsToUpdate = [];
    $varsToSkip = [];

    foreach ($envVars as $key => $defaultValue) {
      $keyNormalized = trim($key);
      $formattedValue = is_string($defaultValue) && !empty($defaultValue) ? $defaultValue : '""';

      if (!isset($existingVars[$keyNormalized])) {
        $varsToAdd[$keyNormalized] = $formattedValue;
      } elseif ($interactive && $this->templateGenerator !== null) {
        $existingValue = $existingVarValues[$keyNormalized] ?? '""';
        $action = $this->promptExistingVarAction($keyNormalized, $existingValue, $formattedValue);

        if ($action === 'skip') {
          $varsToSkip[] = $keyNormalized;
        } elseif ($action === 'override') {
          $varsToUpdate[$keyNormalized] = $formattedValue;
        } elseif ($action === 'keep') {
          $varsToUpdate[$keyNormalized] = $existingValue;
        }
      }
    }

    if (empty($varsToAdd) && empty($varsToUpdate)) {
      if (!empty($varsToSkip)) {
        $this->info("Skipped " . count($varsToSkip) . " existing environment variable(s).");
      }
      return;
    }

    $lines = explode("\n", $envContent);
    $updatedLines = [];
    $processedKeys = [];

    foreach ($lines as $line) {
      $lineTrimmed = trim($line);
      $shouldKeep = true;

      if (!empty($lineTrimmed) && !str_starts_with($lineTrimmed, '#') && strpos($lineTrimmed, '=') !== false) {
        [$key, $value] = explode('=', $lineTrimmed, 2);
        $keyNormalized = trim($key);

        if (isset($varsToUpdate[$keyNormalized])) {
          $updatedLines[] = "{$keyNormalized}={$varsToUpdate[$keyNormalized]}";
          $processedKeys[$keyNormalized] = true;
          $shouldKeep = false;
        }
      }

      if ($shouldKeep) {
        $updatedLines[] = $line;
      }
    }

    $newContent = rtrim(implode("\n", $updatedLines));
    $appendContent = "";

    if (!empty($varsToAdd) || !empty(array_diff_key($varsToUpdate, $processedKeys))) {
      $hasAutoGeneratedComment = false;
      foreach ($updatedLines as $line) {
        if (str_contains($line, '# Auto-generated from module config')) {
          $hasAutoGeneratedComment = true;
          break;
        }
      }

      if (!$hasAutoGeneratedComment && (!empty($varsToAdd) || !empty(array_diff_key($varsToUpdate, $processedKeys)))) {
        $appendContent .= "\n# Auto-generated from module config\n";
      }

      foreach ($varsToAdd as $key => $value) {
        $appendContent .= "{$key}={$value}\n";
      }

      foreach ($varsToUpdate as $key => $value) {
        if (!isset($processedKeys[$key])) {
          $appendContent .= "{$key}={$value}\n";
        }
      }
    }

    $finalContent = $newContent . $appendContent;
    file_put_contents(self::ENV_FILE, $finalContent);

    $addedCount = count($varsToAdd);
    $updatedCount = count($varsToUpdate);
    $skippedCount = count($varsToSkip);

    $messages = [];
    if ($addedCount > 0) {
      $messages[] = "Added {$addedCount} new environment variable(s)";
    }
    if ($updatedCount > 0) {
      $messages[] = "Updated {$updatedCount} existing environment variable(s)";
    }
    if ($skippedCount > 0) {
      $messages[] = "Skipped {$skippedCount} existing environment variable(s)";
    }

    if (!empty($messages)) {
      $this->info(implode(', ', $messages) . " to .env file");
    }
  }

  private function promptExistingVarAction(string $varName, string $existingValue, string $newValue): string
  {
    if ($this->templateGenerator === null) {
      return 'skip';
    }

    $this->line("");
    $this->warning("Environment variable '{$varName}' already exists:");
    $this->line("  Current value: {$existingValue}");
    $this->line("  New value: {$newValue}");
    $this->line("");

    $choice = $this->templateGenerator->selectFromList(
      "What would you like to do?",
      ['Skip (keep current value)', 'Override (use new value)', 'Override but keep current value'],
      'Skip (keep current value)'
    );

    if ($choice === null) {
      return 'skip';
    }

    if ($choice === 'Skip (keep current value)') {
      return 'skip';
    }

    if ($choice === 'Override (use new value)') {
      return 'override';
    }

    if ($choice === 'Override but keep current value') {
      return 'keep';
    }

    return 'skip';
  }
}
