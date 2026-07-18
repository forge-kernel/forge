<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Http;

use Modules\ForgeAuth\Enums\Role;
use Modules\ForgeHub\Services\LogService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Attributes\RequiresRole;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub')]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class LogEndpoint
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Endpoint("/logs")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $selectedFile = $request->query('file');
        $entries = [];
        $error = null;
        $stats = null;
        $modules = [];

        $filters = [
            'search' => $request->query('search'),
            'date' => $request->query('date'),
            'level' => $request->query('level'),
            'module' => $request->query('module'),
            'fingerprint' => $request->query('fingerprint'),
        ];

        if ($selectedFile) {
            try {
                foreach ($this->logService->getLogEntries(
                    $selectedFile,
                    $filters['search'],
                    $filters['date'],
                    $filters['level'],
                    $filters['module'],
                    $filters['fingerprint'],
                ) as $entry) {
                    $entries[] = $entry;
                }

                $stats = $this->logService->getStats($selectedFile);
                $modules = $this->logService->getModules($selectedFile);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $data = [
            'files' => $this->logService->getLogFiles(),
            'entries' => $entries,
            'error' => $error,
            'selectedFile' => $selectedFile,
            'stats' => $stats,
            'modules' => $modules,
            'filters' => $filters,
        ];

        return $this->view(view: "logs/home", data: $data);
    }
}
