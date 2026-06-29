<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeAuth\Enums\Role;
use Modules\ForgeHub\Services\HubItemRegistry;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Attributes\RequiresRole;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\ModuleLoader\Loader;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use ReflectionClass;

#[Routable(prefix: '/hub')]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class ModuleController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly HubItemRegistry $registry,
        private readonly Loader $loader
    ) {
    }

    #[Endpoint("/modules")]
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

        return $this->view(view: "modules", data: $data);
    }
}
