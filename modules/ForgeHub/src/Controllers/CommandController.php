<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\CommandService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\ApiResponse;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class CommandController
{
    use ControllerHelper;

    public function __construct(private CommandService $commandService)
    {
    }

    #[Route(
        path: "/hub/commands",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        $whoami = trim(shell_exec('whoami') ?? '');
        $pwd = trim(shell_exec('pwd') ?? '');

        return $this->view(view: "pages/commands", data: ['whoami' => $whoami, 'pwd' => $pwd]);
    }

    #[Route(
        path: "/hub/commands/list",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    #[Layout("ForgeHub:hub")]
    public function getCommands(): Response
    {
        $commands = $this->commandService->getAvailableCommands();
        return new ApiResponse(['commands' => $commands]);
    }

    #[Route(
        path: "/hub/commands/refresh",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function refreshCommands(): Response
    {
        $this->commandService->clearCache();
        $commands = $this->commandService->getAvailableCommands();
        return new ApiResponse(['commands' => $commands, 'message' => 'Commands refreshed successfully']);
    }

    #[Route(
        path: "/hub/commands/cache-stats",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function getCacheStats(): Response
    {
        $stats = $this->commandService->getCacheStats();
        return new ApiResponse(['cache_stats' => $stats]);
    }

    #[Route(
        path: "/hub/commands/arguments",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function getCommandArguments(Request $request): Response
    {
        $commandName = $request->query('command') ?? '';
        if (empty($commandName)) {
            return new ApiResponse(['arguments' => []], 400);
        }

        $arguments = $this->commandService->getCommandArguments($commandName);
        return new ApiResponse(['arguments' => $arguments]);
    }

    #[Route(
        path: "/hub/commands/execute",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function execute(Request $request): Response
    {
        $command = trim($request->postData['command'] ?? '');

        if (empty($command)) {
            return new ApiResponse([
                'output' => 'Command cannot be empty',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => '',
                'commandHistory' => $this->updateCommandHistory('')
            ], 400);
        }

        if (!$this->commandService->isCommandAllowed($command)) {
            return new ApiResponse([
                'output' => 'Command is not allowed',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => $command,
                'commandHistory' => $this->updateCommandHistory($command)
            ], 403);
        }

        $commandParts = explode(' ', $command, 2);
        $commandName = $commandParts[0];
        $providedArgs = isset($commandParts[1]) ? explode(' ', $commandParts[1]) : [];

        $validation = $this->commandService->validateCommandArguments($command, $providedArgs);
        if (!$validation['valid']) {
            return new ApiResponse([
                'output' => 'Missing required arguments: ' . implode(', ', $validation['errors']),
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => $command,
                'commandHistory' => $this->updateCommandHistory($command)
            ], 400);
        }

        $processId = uniqid('cmd_', true);
        $this->updateCommandHistory($command);

        $result = $this->commandService->startCommand($command, $processId);

        return new ApiResponse([
            'output' => $result['output'] ?? '',
            'needsInput' => $result['needsInput'] ?? false,
            'prompt' => $result['prompt'] ?? '',
            'processId' => $processId,
            'status' => $result['status'] ?? 'error',
            'command' => $command,
            'commandHistory' => $_SESSION['command_history'] ?? []
        ]);
    }

    #[Route(
        path: "/hub/commands/send-input",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function sendInput(Request $request): Response
    {
        $processId = $request->postData['process_id'] ?? '';
        $input = $request->postData['input'] ?? '';

        if (empty($processId)) {
            return new ApiResponse([
                'output' => 'Process ID is required',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ], 400);
        }

        $result = $this->commandService->sendInput($processId, $input);

        return new ApiResponse([
            'output' => $result['output'] ?? '',
            'needsInput' => $result['needsInput'] ?? false,
            'prompt' => $result['prompt'] ?? '',
            'processId' => $processId,
            'status' => $result['status'] ?? 'error',
            'commandHistory' => $_SESSION['command_history'] ?? []
        ]);
    }

    #[Route(
        path: "/hub/commands/status",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function status(Request $request): Response
    {
        $processId = $request->query('process_id') ?? null;

        if (!$processId) {
            return new ApiResponse(['status' => 'not_found'], 404);
        }

        return new ApiResponse([
            'status' => 'running',
            'processId' => $processId
        ]);
    }

    private function updateCommandHistory(string $command): array
    {
        $_SESSION['command_history'] = array_slice(
            array_merge($_SESSION['command_history'] ?? [], [$command]),
            -50
        );
        return $_SESSION['command_history'];
    }
}
