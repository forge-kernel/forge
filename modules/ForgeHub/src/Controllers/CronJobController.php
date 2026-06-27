<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\CronJobService;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub')]
#[RequiresRole(Role::ADMIN->value)]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]

final class CronJobController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly CronJobService $cronJobService,
        private readonly Container $container
    ) {
    }

    #[Endpoint("/cron-jobs")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $cronJobs = $this->cronJobService->getCronJobs();

        foreach ($cronJobs as &$job) {
            $job['schedule_readable'] = $this->cronJobService->getHumanReadableSchedule($job['cron_expression'] ?? '* * * * *');
            if (!isset($job['command_type'])) {
                $job['command_type'] = 'forge';
            }
            if (!isset($job['output_file'])) {
                $job['output_file'] = $this->cronJobService->getOutputFilePath($job['id']);
            }
            $job['has_output'] = file_exists($job['output_file']);
            $job['output_size'] = $this->cronJobService->getOutputFileSize($job['id']);

            if ($job['has_output']) {
                $job['last_output_preview'] = $this->cronJobService->getLastOutput($job['id'], 10);
            }
        }

        $phpInfo = $this->cronJobService->getPhpInfo();

        $data = [
            'title' => 'Cron Jobs',
            'cronJobs' => $cronJobs,
            'phpInfo' => $phpInfo,
        ];

        return $this->view(view: "cron-jobs", data: $data);
    }

    #[Endpoint("/cron-jobs", "POST")]
    public function create(Request $request): Response
    {
        $data = $request->json();
        $name = trim($data['name'] ?? '');
        $command = trim($data['command'] ?? '');
        $commandType = $data['command_type'] ?? 'forge';
        $advanced = isset($data['advanced']) && $data['advanced'] === true;
        $schedule = $data['schedule'] ?? [];

        if (empty($name)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Name is required',
            ], 400);
        }

        if (empty($command)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Command is required',
            ], 400);
        }

        if ($commandType === 'forge') {
            $baseCommand = explode(' ', $command)[0];
            $forbiddenCommands = ['down', 'serve', 'up'];
            $forbiddenPrefixes = ['dev:', 'structure:'];

            foreach ($forbiddenCommands as $forbidden) {
                if ($baseCommand === $forbidden) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'This command cannot be used in cron jobs',
                    ], 400);
                }
            }

            foreach ($forbiddenPrefixes as $prefix) {
                if (str_starts_with($baseCommand, $prefix)) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'This command cannot be used in cron jobs',
                    ], 400);
                }
            }
        }

        if (!in_array($commandType, ['forge', 'script'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid command type',
            ], 400);
        }

        if ($commandType === 'script') {
            $scriptPath = str_starts_with($command, '/') ? $command : BASE_PATH . '/' . ltrim($command, '/');
            if (!file_exists($scriptPath)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Script file does not exist',
                ], 400);
            }
        }

        if ($advanced) {
            $expression = trim($schedule['expression'] ?? '');
            if (empty($expression)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cron expression is required in advanced mode',
                ], 400);
            }

            if (!$this->cronJobService->validateCronExpression($expression)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid cron expression',
                ], 400);
            }

            $schedule = [
                'mode' => 'advanced',
                'expression' => $expression,
            ];
        } else {
            if (!isset($schedule['mode'])) {
                $schedule['mode'] = 'simple';
            }
        }

        $cronJob = $this->cronJobService->createCronJob($name, $command, $schedule, $advanced, $commandType);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Cron job created successfully',
            'cronJob' => $cronJob,
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}", "PUT")]
    public function update(Request $request, string $id): Response
    {

        $job = $this->cronJobService->getCronJob($id);
        if (!$job) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cron job not found',
            ], 404);
        }

        $data = $request->json();
        $name = trim($data['name'] ?? $job['name']);
        $command = trim($data['command'] ?? $job['command']);
        $commandType = $data['command_type'] ?? ($job['command_type'] ?? 'forge');
        $advanced = isset($data['advanced']) && $data['advanced'] === true;
        $schedule = $data['schedule'] ?? $job['schedule'];
        $enabled = isset($data['enabled']) ? (bool) $data['enabled'] : ($job['enabled'] ?? true);

        if (empty($name)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Name is required',
            ], 400);
        }

        if (empty($command)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Command is required',
            ], 400);
        }

        if ($commandType === 'forge') {
            $baseCommand = explode(' ', $command)[0];
            $forbiddenCommands = ['down', 'serve', 'up'];
            $forbiddenPrefixes = ['dev:', 'structure:'];

            foreach ($forbiddenCommands as $forbidden) {
                if ($baseCommand === $forbidden) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'This command cannot be used in cron jobs',
                    ], 400);
                }
            }

            foreach ($forbiddenPrefixes as $prefix) {
                if (str_starts_with($baseCommand, $prefix)) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'This command cannot be used in cron jobs',
                    ], 400);
                }
            }
        }

        if (!in_array($commandType, ['forge', 'script'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid command type',
            ], 400);
        }

        if ($commandType === 'script') {
            $scriptPath = str_starts_with($command, '/') ? $command : BASE_PATH . '/' . ltrim($command, '/');
            if (!file_exists($scriptPath)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Script file does not exist',
                ], 400);
            }
        }

        if ($advanced) {
            $expression = trim($schedule['expression'] ?? '');
            if (empty($expression)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cron expression is required in advanced mode',
                ], 400);
            }

            if (!$this->cronJobService->validateCronExpression($expression)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid cron expression',
                ], 400);
            }

            $schedule = [
                'mode' => 'advanced',
                'expression' => $expression,
            ];
        } else {
            $schedule['mode'] = 'simple';
        }

        $updated = $this->cronJobService->updateCronJob($id, [
            'name' => $name,
            'command' => $command,
            'command_type' => $commandType,
            'schedule' => $schedule,
            'enabled' => $enabled,
        ]);

        if (!$updated) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update cron job',
            ], 500);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Cron job updated successfully',
            'cronJob' => $updated,
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}/output", "GET")]
    public function getOutput(Request $request, string $id): Response
    {
        $maxLines = (int) ($request->query('lines') ?? 200);
        $output = $this->cronJobService->getLastOutput($id, $maxLines);

        return $this->jsonResponse([
            'success' => true,
            'output' => $output,
            'has_output' => $output !== null,
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}/output", "DELETE")]
    public function clearOutput(Request $request, string $id): Response
    {
        $this->cronJobService->clearOutput($id);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Output cleared successfully',
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}/run", "POST")]
    public function run(Request $request, string $id): Response
    {

        $job = $this->cronJobService->getCronJob($id);

        if (!$job) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cron job not found',
            ], 404);
        }

        if (!($job['enabled'] ?? true)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cron job is disabled',
            ], 400);
        }

        $command = $job['command'] ?? '';
        $forbiddenCommands = ['down', 'serve', 'up'];
        $forbiddenPrefixes = ['dev:', 'structure:'];

        foreach ($forbiddenCommands as $forbidden) {
            if ($command === $forbidden || str_starts_with($command, $forbidden . ' ')) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'This command cannot be run as a cron job',
                ], 400);
            }
        }

        foreach ($forbiddenPrefixes as $prefix) {
            if (str_starts_with($command, $prefix)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'This command cannot be run as a cron job',
                ], 400);
            }
        }

        $executableCommand = $this->cronJobService->getExecutableCommand($job);
        $outputFile = $this->cronJobService->getOutputFilePath($id);

        $this->cronJobService->appendOutput($id, "=== Manual execution started at " . date('Y-m-d H:i:s') . " ===\n");

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($executableCommand, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            $this->cronJobService->appendOutput($id, "ERROR: Failed to start process\n");
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to execute command',
            ], 500);
        }

        fclose($pipes[0]);

        $output = '';
        $errorOutput = '';

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $startTime = time();
        $timeout = 300;

        while (true) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                break;
            }

            if (time() - $startTime > $timeout) {
                proc_terminate($process);
                $this->cronJobService->appendOutput($id, "ERROR: Command timed out after {$timeout} seconds\n");
                break;
            }

            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) > 0) {
                if (in_array($pipes[1], $read)) {
                    $chunk = stream_get_contents($pipes[1]);
                    if ($chunk !== false) {
                        $output .= $chunk;
                        $this->cronJobService->appendOutput($id, $chunk);
                    }
                }
                if (in_array($pipes[2], $read)) {
                    $chunk = stream_get_contents($pipes[2]);
                    if ($chunk !== false) {
                        $errorOutput .= $chunk;
                        $this->cronJobService->appendOutput($id, $chunk);
                    }
                }
            }

            usleep(100000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        $this->cronJobService->appendOutput($id, "\n=== Execution completed with exit code: {$returnCode} ===\n\n");

        $this->cronJobService->updateCronJob($id, [
            'last_run' => date('Y-m-d H:i:s'),
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Command executed successfully',
            'exit_code' => $returnCode,
            'output_preview' => substr($output . $errorOutput, -500),
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}", "GET")]
    public function show(Request $request, string $id): Response
    {

        $job = $this->cronJobService->getCronJob($id);

        if (!$job) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cron job not found',
            ], 404);
        }

        $job['schedule_readable'] = $this->cronJobService->getHumanReadableSchedule($job['cron_expression'] ?? '* * * * *');
        if (!isset($job['output_file'])) {
            $job['output_file'] = $this->cronJobService->getOutputFilePath($job['id']);
        }
        $job['last_output'] = $this->cronJobService->getLastOutput($job['id'], 200);
        $job['has_output'] = file_exists($job['output_file']);
        $job['output_size'] = $this->cronJobService->getOutputFileSize($job['id']);

        return $this->jsonResponse([
            'success' => true,
            'cronJob' => $job,
        ]);
    }

    #[Endpoint("/cron-jobs/{id:[^/]+}", "DELETE")]
    public function delete(Request $request, string $id): Response
    {

        $deleted = $this->cronJobService->deleteCronJob($id);

        if (!$deleted) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cron job not found',
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Cron job deleted successfully',
        ]);
    }

    #[Endpoint("/cron-jobs/commands", "GET")]
    public function getCommands(Request $request): Response
    {
        try {
            $setup = \Forge\Core\Bootstrap\AppCommandSetup::getInstance($this->container);
            $classMap = $setup->getClassMap();

            $formattedCommands = [];
            $prefix = 'app';

            foreach ($classMap as $className => $filePath) {
                if (!file_exists($filePath)) {
                    continue;
                }

                clearstatcache(true, $filePath);

                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($filePath, true);
                }

                $fileContent = @file_get_contents($filePath);
                if ($fileContent === false) {
                    continue;
                }

                if (!class_exists($className)) {
                    include_once $filePath;
                } else {
                    $reflection = new \ReflectionClass($className);
                    $reflectionFile = $reflection->getFileName();
                    if ($reflectionFile && filemtime($reflectionFile) < filemtime($filePath)) {
                        if (function_exists('opcache_invalidate')) {
                            @opcache_invalidate($reflectionFile, true);
                        }
                        if (function_exists('runkit7_import')) {
                            @runkit7_import($filePath, RUNKIT7_IMPORT_CLASSES);
                        } else {
                            include_once $filePath;
                        }
                    }
                }

                if (!class_exists($className)) {
                    continue;
                }

                try {
                    $reflection = new \ReflectionClass($className);
                    $reflectionFile = $reflection->getFileName();

                    if ($reflectionFile && file_exists($reflectionFile)) {
                        clearstatcache(true, $reflectionFile);
                        if (function_exists('opcache_invalidate')) {
                            @opcache_invalidate($reflectionFile, true);
                        }
                    }

                    $cliAttrs = $reflection->getAttributes(\Forge\CLI\Attributes\Command::class) ?: $reflection->getAttributes(\Forge\CLI\Attributes\Cli::class);
                    if (empty($cliAttrs)) {
                        continue;
                    }

                    $cli = $cliAttrs[0]->newInstance();
                    $commandName = 'app:' . $cli->command;

                    $commandData = [
                        'name' => $commandName,
                        'description' => $cli->description,
                        'usage' => $cli->usage ?? null,
                        'examples' => $cli->examples ?? [],
                        'arguments' => [],
                    ];

                    if ($reflectionFile && file_exists($reflectionFile)) {
                        $freshContent = @file_get_contents($reflectionFile);
                        if ($freshContent !== false) {
                            $parsedCli = $this->parseCliAttributeFromFile($freshContent);
                            if ($parsedCli !== null) {
                                $commandData['usage'] = $parsedCli['usage'] ?? $commandData['usage'];
                                $commandData['examples'] = $parsedCli['examples'] ?? $commandData['examples'];
                            }
                        }
                    }

                    $args = [];
                    foreach ($reflection->getProperties() as $property) {
                        $argAttrs = $property->getAttributes(\Forge\CLI\Attributes\Arg::class);
                        if (!empty($argAttrs)) {
                            $arg = $argAttrs[0]->newInstance();
                            $args[] = [
                                'name' => $arg->name,
                                'description' => $arg->description,
                                'required' => $arg->required,
                                'default' => $arg->default,
                            ];
                        }
                    }
                    $commandData['arguments'] = $args;

                    if (!isset($formattedCommands[$prefix])) {
                        $formattedCommands[$prefix] = [];
                    }

                    $formattedCommands[$prefix][] = $commandData;
                } catch (\Throwable $e) {
                    error_log("Failed to get command details for {$className}: " . $e->getMessage());
                    continue;
                }
            }

            if (isset($formattedCommands[$prefix])) {
                usort($formattedCommands[$prefix], fn($a, $b) => strcmp($a['name'], $b['name']));
            }

            return $this->jsonResponse([
                'success' => true,
                'commands' => $formattedCommands,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load commands: ' . $e->getMessage(),
                'commands' => [],
            ], 500);
        }
    }

    private function parseCliAttributeFromFile(string $fileContent): ?array
    {
        if (!preg_match('/#\[Cli\s*\((.*?)\)\s*\]/s', $fileContent, $matches)) {
            return null;
        }

        $attributeContent = $matches[1];

        $result = [
            'usage' => null,
            'examples' => [],
        ];

        if (preg_match("/usage:\s*['\"](.*?)['\"]/s", $attributeContent, $usageMatch)) {
            $result['usage'] = $usageMatch[1];
        }

        if (preg_match("/examples:\s*\[(.*?)\]/s", $attributeContent, $examplesMatch)) {
            $examplesContent = $examplesMatch[1];
            if (preg_match_all("/['\"](.*?)['\"]/s", $examplesContent, $exampleMatches)) {
                $result['examples'] = $exampleMatches[1];
            } else {
                $result['examples'] = [];
            }
        }

        return $result;
    }
}
