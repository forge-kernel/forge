<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

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
        $content = $this->router->dispatch($request);

        if ($content instanceof Response) {
            return $content;
        }

        return new Response((string) $content);
    }
}
