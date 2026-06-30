<?php

declare(strict_types=1);

namespace Modules\ForgeHtmx\Middlewares;

use Forge\Core\DI\Attributes\Injectable;
use Forge\Traits\InjectsAssets;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware as MiddlewareAttribute;

#[Injectable]
#[MiddlewareAttribute(group: 'web', order: 2)]
final class ForgeHtmxMiddleware extends Middleware
{
    use InjectsAssets;

    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        if ($request->hasHeader('HX-Request')) {
            return $response;
        }

        $this->registerAsset(
            assetHtml: '<script>
document.addEventListener("htmx:configRequest",function(e){
    var t=document.querySelector(\'meta[name="csrf-token"]\');
    if(t)e.detail.headers["X-CSRF-TOKEN"]=t.getAttribute("content");
});
</script>',
            beforeTag: '</head>',
        );

        $this->injectAssets($response);

        return $response;
    }
}
