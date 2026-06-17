<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\HubItemRegistry;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\ModuleLoader\Loader;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use ReflectionClass;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class ModuleController
{
    use ControllerHelper;

    public function __construct(
        private readonly HubItemRegistry $registry,
        private readonly Loader $loader
    ) {
    }

    #[Route("/hub/modules")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $modules = $this->loader->getSortedModuleRegistry();
        $modulesData = [];

        foreach ($modules as $moduleInfo) {
            $className = $moduleInfo['name'];
            $modulePath = $moduleInfo['path'] ?? null;

            try {
                $reflection = new ReflectionClass($className);
                $moduleAttributes = $reflection->getAttributes(Module::class);

                if (empty($moduleAttributes)) {
                    continue;
                }

                $moduleInstance = $moduleAttributes[0]->newInstance();
                $hubItems = $this->registry->getHubItemsForModule($className);

                $modulesData[] = [
                    'name' => $moduleInstance->name ?? $className,
                    'version' => $moduleInstance->version ?? '0.0.0',
                    'description' => $moduleInstance->description ?? '',
                    'author' => $moduleInstance->author ?? '',
                    'license' => $moduleInstance->license ?? '',
                    'type' => $moduleInstance->type ?? 'generic',
                    'tags' => $moduleInstance->tags ?? [],
                    'className' => $className,
                    'path' => $modulePath,
                    'hubItems' => $hubItems,
                ];
            } catch (\ReflectionException) {
                continue;
            }
        }

        $data = [
            'title' => 'Modules',
            'modules' => $modulesData,
        ];

        return $this->view(view: "pages/modules", data: $data);
    }
}
