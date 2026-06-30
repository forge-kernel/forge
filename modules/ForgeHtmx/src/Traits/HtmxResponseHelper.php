<?php

declare(strict_types=1);

namespace Modules\ForgeHtmx\Traits;

use Modules\ForgeRouter\Http\Response;

trait HtmxResponseHelper
{
    protected function htmxFragment(string $html, int $statusCode = 200): Response
    {
        return new Response($html, $statusCode);
    }

    protected function htmxRedirect(string $url): Response
    {
        return new Response('', 200, ['HX-Redirect' => $url]);
    }

    protected function htmxRefresh(): Response
    {
        return new Response('', 200, ['HX-Refresh' => 'true']);
    }

    protected function htmxTrigger(string|array $event, mixed $detail = null): Response
    {
        $header = 'HX-Trigger';

        if (is_array($event)) {
            return new Response('', 200, [$header => json_encode($event)]);
        }

        if ($detail !== null) {
            return new Response('', 200, [$header => json_encode([$event => $detail])]);
        }

        return new Response('', 200, [$header => $event]);
    }

    protected function htmxTriggerAfterSwap(string|array $event, mixed $detail = null): Response
    {
        $header = 'HX-Trigger-After-Swap';

        if (is_array($event)) {
            return new Response('', 200, [$header => json_encode($event)]);
        }

        if ($detail !== null) {
            return new Response('', 200, [$header => json_encode([$event => $detail])]);
        }

        return new Response('', 200, [$header => $event]);
    }

    protected function htmxTriggerAfterSettle(string|array $event, mixed $detail = null): Response
    {
        $header = 'HX-Trigger-After-Settle';

        if (is_array($event)) {
            return new Response('', 200, [$header => json_encode($event)]);
        }

        if ($detail !== null) {
            return new Response('', 200, [$header => json_encode([$event => $detail])]);
        }

        return new Response('', 200, [$header => $event]);
    }

    protected function htmxLocation(string $url, array $context = []): Response
    {
        if (empty($context)) {
            return new Response('', 200, ['HX-Location' => $url]);
        }

        $context['path'] = $url;
        return new Response('', 200, ['HX-Location' => json_encode($context)]);
    }

    protected function htmxPushUrl(string $url): Response
    {
        return new Response('', 200, ['HX-Push-Url' => $url]);
    }

    protected function htmxReplaceUrl(string $url): Response
    {
        return new Response('', 200, ['HX-Replace-Url' => $url]);
    }

    protected function htmxRetarget(string $selector): Response
    {
        return new Response('', 200, ['HX-Retarget' => $selector]);
    }

    protected function htmxReswap(string $swap): Response
    {
        return new Response('', 200, ['HX-Reswap' => $swap]);
    }

    protected function htmxStopPolling(): Response
    {
        return new Response('', 286);
    }
}
