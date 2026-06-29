<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

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
