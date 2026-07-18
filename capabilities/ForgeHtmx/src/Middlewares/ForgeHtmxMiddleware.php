<?php

declare(strict_types=1);

namespace Capability\ForgeHtmx\Middlewares;

use Forge\Traits\InjectsAssets;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

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
