<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\CLI\Application;
use Forge\CLI\Attributes\Arg;
use Forge\Core\Bootstrap\AppCommandSetup;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use ReflectionClass;

#[Service]
final class CommandService
{
    private const PROCESS_TIMEOUT = 30;
    private const BUFFER_READ_SIZE = 4096;

    private ?array $cachedCommands = null;
    private array $processes = [];

    private const DISALLOWED_PATTERNS = [
        'dev:',
        'maintenance:down',
        'maintenance:up',
        'structure:',
        'asset:unlink',
        'serve',
        'down',
        'up',
    ];

    public function __construct(private CommandCacheService $cacheService)
    {
    }

    public function clearCache(): void
    {
        $this->cachedCommands = null;
        $this->cacheService->clearCache();
    }

    public function getCacheStats(): array
    {
        $this->cacheService->loadCache();
        
        $commandsCached = $this->cacheService->getCachedCommands() !== null;
        $phpExecutableCached = $this->cacheService->getCachedPhpExecutable() !== null;
        
        $stats = [
            'commands_cached' => $commandsCached,
            'php_executable_cached' => $phpExecutableCached,
            'cache_files_exist' => [
                'commands' => file_exists(CommandCacheService::COMMANDS_CACHE_FILE),
                'arguments' => file_exists(CommandCacheService::ARGUMENTS_CACHE_FILE),
                'php_executable' => file_exists(CommandCacheService::PHP_EXECUTABLE_CACHE_FILE),
            ]
        ];

        // Add cached arguments count if available
        $reflection = new ReflectionClass(CommandCacheService::class);
        $argsCacheProperty = $reflection->getProperty('argumentsCache');
        $argsCacheProperty->setAccessible(true);
        $argsCache = $argsCacheProperty->getValue($this->cacheService);
        $stats['cached_arguments_count'] = count($argsCache);

        return $stats;
    }

    public function getAvailableCommands(): array
    {
        if ($this->cachedCommands !== null) {
            return $this->cachedCommands;
        }

        // Try to load from cache first
        $cachedCommands = $this->cacheService->getCachedCommands();
        if ($cachedCommands !== null) {
            $this->cachedCommands = $cachedCommands;
            return $this->cachedCommands;
        }

        try {
            $container = Container::getInstance();

            AppCommandSetup::getInstance($container);

            $app = Application::getInstance($container);
            $allCommands = $app->getCommands();

            if (empty($allCommands)) {
                error_log('CommandService: No commands found in Application. Instance ID: ' . $app->getInstanceId());
                return [];
            }

            error_log('CommandService: Found ' . count($allCommands) . ' commands');

            $filtered = [];
            foreach ($allCommands as $name => $commandInfo) {
                $isDisallowed = false;
                foreach (self::DISALLOWED_PATTERNS as $pattern) {
                    if (str_starts_with($name, $pattern)) {
                        $isDisallowed = true;
                        break;
                    }
                }
                if (!$isDisallowed) {
                    $filtered[$name] = $commandInfo;
                }
            }

            if (!isset($filtered['help'])) {
                $filtered['help'] = ['', 'Displays help for available commands.'];
            }

            $grouped = [];
            foreach ($filtered as $name => $commandInfo) {
                $parts = explode(':', $name, 2);
                $category = count($parts) > 1 ? ucfirst($parts[0]) : 'General';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][$name] = is_array($commandInfo) ? ($commandInfo[1] ?? '') : '';
            }

            ksort($grouped);
            foreach ($grouped as $category => $commands) {
                ksort($grouped[$category]);
            }

            // Cache the results with file timestamps
            $this->cachedCommands = $grouped;
            $this->cacheService->setCachedCommands(
                $grouped,
                $this->cacheService->getCommandFilesTimestamps()
            );

            return $this->cachedCommands;
        } catch (\Throwable $e) {
            error_log('CommandService::getAvailableCommands error: ' . $e->getMessage());
            error_log('CommandService::getAvailableCommands trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    public function getCommandArguments(string $commandName): array
    {
        // Try cache first
        $cachedArguments = $this->cacheService->getCachedArguments($commandName);
        if ($cachedArguments !== null) {
            return $cachedArguments;
        }

        try {
            $container = Container::getInstance();
            AppCommandSetup::getInstance($container);

            if (!$container->has(Application::class)) {
                $container->singleton(Application::class, function () use ($container) {
                    return Application::getInstance($container);
                });
            }

            $app = $container->get(Application::class);
            $allCommands = $app->getCommands();

            if (!isset($allCommands[$commandName])) {
                return [];
            }

            $commandClass = $allCommands[$commandName][0];
            $reflection = new ReflectionClass($commandClass);

            $arguments = [];
            foreach ($reflection->getProperties() as $property) {
                $argAttributes = $property->getAttributes(Arg::class);
                if (empty($argAttributes)) {
                    continue;
                }

                $arg = $argAttributes[0]->newInstance();
                $arguments[] = [
                    'name' => $arg->name,
                    'description' => $arg->description,
                    'required' => $arg->required,
                    'default' => $arg->default,
                ];
            }

            // Cache the arguments
            $this->cacheService->setCachedArguments(
                $commandName,
                $arguments,
                $this->cacheService->getCommandFilesTimestamps()
            );

            return $arguments;
        } catch (\Throwable $e) {
            error_log('CommandService::getCommandArguments error: ' . $e->getMessage());
            return [];
        }
    }

    public function validateCommandArguments(string $command, array $arguments): array
    {
        $commandParts = explode(' ', $command, 2);
        $commandName = $commandParts[0];
        $requiredArgs = $this->getCommandArguments($commandName);

        if (empty($requiredArgs)) {
            return ['valid' => true, 'errors' => []];
        }

        $errors = [];
        $argString = isset($commandParts[1]) ? $commandParts[1] : '';
        $providedArgs = [];

        if (!empty($argString)) {
            preg_match_all('/--([^=]+)=([^\s]+)/', $argString, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $providedArgs[$match[1]] = $match[2];
            }
        }

        foreach ($requiredArgs as $arg) {
            if ($arg['required'] && !isset($providedArgs[$arg['name']])) {
                $errors[] = "Required argument --{$arg['name']} is missing";
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function isCommandAllowed(string $command): bool
    {
        $command = trim($command);
        if (empty($command)) {
            return false;
        }

        $parts = explode(' ', $command, 2);
        $commandName = $parts[0];

        if ($commandName === 'help') {
            return true;
        }

        foreach (self::DISALLOWED_PATTERNS as $pattern) {
            if (str_starts_with($commandName, $pattern)) {
                return false;
            }
        }

        $availableCommands = $this->getAvailableCommands();
        if (empty($availableCommands)) {
            return false;
        }

        foreach ($availableCommands as $category => $commands) {
            if (isset($commands[$commandName])) {
                return true;
            }
        }

        return false;
    }

    public function startCommand(string $command, string $processId): array
    {
        $command = trim($command);
        if (empty($command)) {
            return [
                'output' => 'Command cannot be empty',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        if (!$this->isCommandAllowed($command)) {
            return [
                'output' => 'Command is not allowed',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        $phpExecutable = $this->getPhpExecutable();
        $forgePath = BASE_PATH . '/forge.php';

        $fullCommand = sprintf(
            'cd %s && %s %s %s',
            escapeshellarg(BASE_PATH),
            escapeshellarg($phpExecutable),
            escapeshellarg($forgePath),
            $this->escapeCommand($command)
        );

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($fullCommand, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            return [
                'output' => 'Error starting process',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $this->processes[$processId] = [
            'process' => $process,
            'pipes' => $pipes,
            'command' => $command,
            'startTime' => time(),
        ];

        return $this->readProcessOutput($processId, $process, $pipes);
    }

    private function getPhpExecutable(): string
    {
        // Try cache first
        $cachedPath = $this->cacheService->getCachedPhpExecutable();
        if ($cachedPath !== null) {
            return $cachedPath;
        }

        $phpPath = $this->discoverPhpExecutable();
        
        // Cache the result
        $this->cacheService->setCachedPhpExecutable($phpPath);
        
        return $phpPath;
    }

    private function discoverPhpExecutable(): string
    {
        $possiblePaths = [];

        $whichOutput = [];
        $whichReturnCode = 0;
        @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
        if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
            $whichPath = trim($whichOutput[0]);
            if ($whichPath && !str_contains($whichPath, 'fpm') && file_exists($whichPath)) {
                $possiblePaths[] = $whichPath;
            }
        }

        if (defined('PHP_BINARY') && PHP_BINARY) {
            $phpBinary = PHP_BINARY;
            if (!str_contains(strtolower($phpBinary), 'fpm') && file_exists($phpBinary)) {
                $possiblePaths[] = $phpBinary;
            }
        }

        $envPhpBinary = $_ENV['PHP_BINARY'] ?? getenv('PHP_BINARY');
        if ($envPhpBinary && file_exists($envPhpBinary) && !str_contains(strtolower($envPhpBinary), 'fpm')) {
            $possiblePaths[] = $envPhpBinary;
        }

        $commonPaths = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php'];
        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path) && !str_contains($path, 'fpm')) {
                $possiblePaths[] = $path;
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }

                $testOutput = [];
                $testReturnCode = 0;
                @exec(escapeshellarg($path) . ' -v 2>/dev/null', $testOutput, $testReturnCode);
                if ($testReturnCode === 0 && !empty($testOutput[0])) {
                    if (str_contains($testOutput[0], 'cli')) {
                        return $path;
                    }
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }
                return $path;
            }
        }

        error_log('CommandService: Could not find PHP CLI executable, falling back to "php" command');
        return 'php';
    }

    private function escapeCommand(string $command): string
    {
        $parts = preg_split('/\s+/', $command, -1, PREG_SPLIT_NO_EMPTY);
        $escaped = [];
        foreach ($parts as $part) {
            $escaped[] = escapeshellarg($part);
        }
        return implode(' ', $escaped);
    }

    public function sendInput(string $processId, string $input): array
    {
        if (!isset($this->processes[$processId])) {
            return [
                'output' => 'No active process found for this ID.',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        $processInfo = $this->processes[$processId];
        $process = $processInfo['process'];
        $pipes = $processInfo['pipes'];

        if (is_resource($process) && isset($pipes[0]) && is_resource($pipes[0])) {
            fwrite($pipes[0], $input . PHP_EOL);
            fflush($pipes[0]);
            return $this->readProcessOutput($processId, $process, $pipes);
        }

        return [
            'output' => 'No active process or stdin pipe available.',
            'needsInput' => false,
            'prompt' => '',
            'status' => 'error'
        ];
    }

    private function readProcessOutput(string $processId, $process, array $pipes): array
    {
        $needsInput = false;
        $prompt = '';
        $outputBuffer = '';
        $lastOutputTime = time();
        $maxIterations = 500;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $iteration++;
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            $numStreams = @stream_select($read, $write, $except, 1);

            if ($numStreams > 0) {
                $lastOutputTime = time();
                if (in_array($pipes[1], $read)) {
                    $output = stream_get_contents($pipes[1], self::BUFFER_READ_SIZE);
                    if ($output !== false && $output !== '') {
                        $outputBuffer .= $output;
                    }
                }
                if (in_array($pipes[2], $read)) {
                    $errorOutput = stream_get_contents($pipes[2], self::BUFFER_READ_SIZE);
                    if ($errorOutput !== false && $errorOutput !== '') {
                        $outputBuffer .= $errorOutput;
                    }
                }

                if (!$needsInput) {
                    $detectedPrompt = $this->detectPrompt($outputBuffer);
                    if ($detectedPrompt !== null) {
                        $prompt = $detectedPrompt;
                        $needsInput = true;
                        return $this->buildResponse($needsInput, $prompt, 'waiting_for_input', $outputBuffer);
                    }
                }
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                stream_set_blocking($pipes[1], true);
                stream_set_blocking($pipes[2], true);

                $remainingOutput = stream_get_contents($pipes[1]);
                if ($remainingOutput !== false) {
                    $outputBuffer .= $remainingOutput;
                }

                $remainingError = stream_get_contents($pipes[2]);
                if ($remainingError !== false) {
                    $outputBuffer .= $remainingError;
                }

                $this->cleanupProcess($processId, $process, $pipes);
                return $this->buildResponse($needsInput, $prompt, 'completed', $outputBuffer);
            }

            if (time() - $lastOutputTime > self::PROCESS_TIMEOUT) {
                proc_terminate($process);
                stream_set_blocking($pipes[1], true);
                stream_set_blocking($pipes[2], true);

                $remainingOutput = stream_get_contents($pipes[1]);
                if ($remainingOutput !== false) {
                    $outputBuffer .= $remainingOutput;
                }

                $remainingError = stream_get_contents($pipes[2]);
                if ($remainingError !== false) {
                    $outputBuffer .= $remainingError;
                }

                $outputBuffer .= "\nProcess timed out due to inactivity.";
                $this->cleanupProcess($processId, $process, $pipes);
                return $this->buildResponse(false, '', 'timeout', $outputBuffer);
            }

            usleep(100000);
        }

        $status = proc_get_status($process);
        if (!$status['running']) {
            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);

            $remainingOutput = stream_get_contents($pipes[1]);
            if ($remainingOutput !== false) {
                $outputBuffer .= $remainingOutput;
            }

            $remainingError = stream_get_contents($pipes[2]);
            if ($remainingError !== false) {
                $outputBuffer .= $remainingError;
            }

            $this->cleanupProcess($processId, $process, $pipes);
            return $this->buildResponse($needsInput, $prompt, 'completed', $outputBuffer);
        }

        return $this->buildResponse($needsInput, $prompt, 'running', $outputBuffer);
    }


    private function detectPrompt(string $buffer): ?string
    {
        $patterns = [
            '/([a-zA-Z0-9_\-]+[>:]\s*)$/',
            '/([?]\s*)$/',
            '/\[([^\]]+)\]\s*$/',
            '/(password|passphrase|credentials?)[^:]*:\s*$/i',
            '/((enter|type|input)[^:]*:)\s*$/i',
            '/(\.\.\.|>+)\s*$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $buffer, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function cleanupProcess(string $processId, $process, array $pipes): void
    {
        if (isset($this->processes[$processId])) {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }
            if (is_resource($process)) {
                @proc_close($process);
            }
            unset($this->processes[$processId]);
        }
    }

    private function buildResponse(bool $needsInput, string $prompt, string $status, string $outputBuffer): array
    {
        return [
            'output' => $outputBuffer,
            'needsInput' => $needsInput,
            'prompt' => $prompt,
            'status' => $status,
        ];
    }
}
