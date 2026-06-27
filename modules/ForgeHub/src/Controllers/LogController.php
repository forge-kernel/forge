<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\LogService;
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
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class LogController
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

        if ($selectedFile) {
            try {
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

        return $this->view(view: "logs", data: $data);
    }
}
