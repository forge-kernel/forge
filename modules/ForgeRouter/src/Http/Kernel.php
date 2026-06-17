<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

use App\Modules\ForgeRouter\Events\RouterHookManager;
use App\Modules\ForgeRouter\Events\RouterHookName;
use App\Modules\ForgeRouter\Routing\Router;
use Throwable;

final readonly class Kernel
{
    public function __construct(private Router $router) {}

    /**
     * @throws Throwable
     */
    public function handler(Request $request): Response
    {
        RouterHookManager::triggerHook(RouterHookName::BEFORE_REQUEST, $request);

        $content = $this->router->dispatch($request);

        if ($content instanceof Response) {
            RouterHookManager::triggerHook(
                RouterHookName::AFTER_REQUEST,
                $request,
                $content,
            );
            return $content;
        }

        $response = new Response((string) $content);
        RouterHookManager::triggerHook(
            RouterHookName::AFTER_REQUEST,
            $request,
            $response,
        );
        return $response;
    }
}
