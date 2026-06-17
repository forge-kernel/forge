<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\LogService;
use Forge\Core\DI\Attributes\Service;
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

final class LogController
{
    use ControllerHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Route("/hub/logs")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $selectedFile = $request->query('file');
        $entries = [];
        $error = null;

        if ($selectedFile) {
            try {
                // Convert Generator to array for the view
                foreach ($this->logService->getLogEntries(
                    $selectedFile,
                    $request->query('search'),
                    $request->query('date')
                ) as $entry) {
                    $entries[] = $entry;
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $data = [
            'files' => $this->logService->getLogFiles(),
            'entries' => $entries,
            'error' => $error,
            'selectedFile' => $selectedFile,
        ];

        return $this->view(view: "pages/logs", data: $data);
    }
}
