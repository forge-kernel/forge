<?php

declare(strict_types=1);

namespace Modules\ForgeHtmx\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeHtmx\Traits\HtmxResponseHelper;
use Modules\ForgeRouter\Http\Response;

#[Group('htmx')]
final class HtmxResponseHelperTest extends TestCase
{
    private function runner(): object
    {
        return new class {
            use HtmxResponseHelper;

            public function callHtmxFragment(string $html, int $statusCode = 200): Response
            {
                return $this->htmxFragment($html, $statusCode);
            }

            public function callHtmxRedirect(string $url): Response
            {
                return $this->htmxRedirect($url);
            }

            public function callHtmxRefresh(): Response
            {
                return $this->htmxRefresh();
            }

            public function callHtmxTrigger(string|array $event, mixed $detail = null): Response
            {
                return $this->htmxTrigger($event, $detail);
            }

            public function callHtmxTriggerAfterSwap(string|array $event, mixed $detail = null): Response
            {
                return $this->htmxTriggerAfterSwap($event, $detail);
            }

            public function callHtmxTriggerAfterSettle(string|array $event, mixed $detail = null): Response
            {
                return $this->htmxTriggerAfterSettle($event, $detail);
            }

            public function callHtmxLocation(string $url, array $context = []): Response
            {
                return $this->htmxLocation($url, $context);
            }

            public function callHtmxPushUrl(string $url): Response
            {
                return $this->htmxPushUrl($url);
            }

            public function callHtmxReplaceUrl(string $url): Response
            {
                return $this->htmxReplaceUrl($url);
            }

            public function callHtmxRetarget(string $selector): Response
            {
                return $this->htmxRetarget($selector);
            }

            public function callHtmxReswap(string $swap): Response
            {
                return $this->htmxReswap($swap);
            }

            public function callHtmxStopPolling(): Response
            {
                return $this->htmxStopPolling();
            }
        };
    }

    #[Test('htmxFragment returns response with given html and default 200 status')]
    public function fragment_default_status(): void
    {
        $res = $this->runner()->callHtmxFragment('<div>hello</div>');
        $this->assertEquals('<div>hello</div>', $res->getContent());
        $this->assertEquals(200, $res->getStatusCode());
    }

    #[Test('htmxFragment respects custom status code')]
    public function fragment_custom_status(): void
    {
        $res = $this->runner()->callHtmxFragment('not found', 404);
        $this->assertEquals('not found', $res->getContent());
        $this->assertEquals(404, $res->getStatusCode());
    }

    #[Test('htmxRedirect sets HX-Redirect header')]
    public function redirect_sets_header(): void
    {
        $res = $this->runner()->callHtmxRedirect('/products');
        $this->assertEquals('/products', $res->getHeader('HX-Redirect'));
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('', $res->getContent());
    }

    #[Test('htmxRefresh sets HX-Refresh header to true')]
    public function refresh_sets_header(): void
    {
        $res = $this->runner()->callHtmxRefresh();
        $this->assertEquals('true', $res->getHeader('HX-Refresh'));
    }

    #[Test('htmxTrigger with string event sets HX-Trigger to event name')]
    public function trigger_string_event(): void
    {
        $res = $this->runner()->callHtmxTrigger('product-created');
        $this->assertEquals('product-created', $res->getHeader('HX-Trigger'));
    }

    #[Test('htmxTrigger with string event and detail sets HX-Trigger to JSON object')]
    public function trigger_string_event_with_detail(): void
    {
        $res = $this->runner()->callHtmxTrigger('product-created', ['id' => 42]);
        $expected = json_encode(['product-created' => ['id' => 42]]);
        $this->assertJsonStringEqualsJsonString($expected, $res->getHeader('HX-Trigger'));
    }

    #[Test('htmxTrigger with array sets HX-Trigger to JSON object with multiple events')]
    public function trigger_array_events(): void
    {
        $res = $this->runner()->callHtmxTrigger(['event-a' => [], 'event-b' => ['key' => 'val']]);
        $expected = json_encode(['event-a' => [], 'event-b' => ['key' => 'val']]);
        $this->assertJsonStringEqualsJsonString($expected, $res->getHeader('HX-Trigger'));
    }

    #[Test('htmxTriggerAfterSwap sets HX-Trigger-After-Swap header')]
    public function trigger_after_swap(): void
    {
        $res = $this->runner()->callHtmxTriggerAfterSwap('refresh-list');
        $this->assertEquals('refresh-list', $res->getHeader('HX-Trigger-After-Swap'));
    }

    #[Test('htmxTriggerAfterSettle sets HX-Trigger-After-Settle header')]
    public function trigger_after_settle(): void
    {
        $res = $this->runner()->callHtmxTriggerAfterSettle('reload');
        $this->assertEquals('reload', $res->getHeader('HX-Trigger-After-Settle'));
    }

    #[Test('htmxLocation with url only sets HX-Location to url string')]
    public function location_url_only(): void
    {
        $res = $this->runner()->callHtmxLocation('/products/2');
        $this->assertEquals('/products/2', $res->getHeader('HX-Location'));
    }

    #[Test('htmxLocation with context sets HX-Location to JSON with path and context')]
    public function location_with_context(): void
    {
        $res = $this->runner()->callHtmxLocation('/products/2', ['target' => '#main']);
        $expected = json_encode(['path' => '/products/2', 'target' => '#main']);
        $this->assertJsonStringEqualsJsonString($expected, $res->getHeader('HX-Location'));
    }

    #[Test('htmxPushUrl sets HX-Push-Url header')]
    public function push_url(): void
    {
        $res = $this->runner()->callHtmxPushUrl('/products');
        $this->assertEquals('/products', $res->getHeader('HX-Push-Url'));
    }

    #[Test('htmxReplaceUrl sets HX-Replace-Url header')]
    public function replace_url(): void
    {
        $res = $this->runner()->callHtmxReplaceUrl('/products');
        $this->assertEquals('/products', $res->getHeader('HX-Replace-Url'));
    }

    #[Test('htmxRetarget sets HX-Retarget header')]
    public function retarget(): void
    {
        $res = $this->runner()->callHtmxRetarget('#main-content');
        $this->assertEquals('#main-content', $res->getHeader('HX-Retarget'));
    }

    #[Test('htmxReswap sets HX-Reswap header')]
    public function reswap(): void
    {
        $res = $this->runner()->callHtmxReswap('beforeend');
        $this->assertEquals('beforeend', $res->getHeader('HX-Reswap'));
    }

    #[Test('htmxStopPolling returns status 286 with empty content')]
    public function stop_polling(): void
    {
        $res = $this->runner()->callHtmxStopPolling();
        $this->assertEquals(286, $res->getStatusCode());
        $this->assertEquals('', $res->getContent());
    }

    #[Test('htmxTrigger with null detail should not JSON encode')]
    public function trigger_null_detail(): void
    {
        $res = $this->runner()->callHtmxTrigger('simple-event', null);
        $this->assertEquals('simple-event', $res->getHeader('HX-Trigger'));
    }

    #[Test('multiple chained methods work independently')]
    public function multiple_calls_independent(): void
    {
        $r1 = $this->runner()->callHtmxRedirect('/a');
        $r2 = $this->runner()->callHtmxRefresh();
        $r3 = $this->runner()->callHtmxLocation('/b');

        $this->assertEquals('/a', $r1->getHeader('HX-Redirect'));
        $this->assertNull($r1->getHeader('HX-Refresh'));
        $this->assertEquals('true', $r2->getHeader('HX-Refresh'));
        $this->assertEquals('/b', $r3->getHeader('HX-Location'));
    }
}
