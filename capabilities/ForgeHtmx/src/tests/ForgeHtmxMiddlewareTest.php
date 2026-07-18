<?php

declare(strict_types=1);

namespace Capability\ForgeHtmx\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Capability\ForgeHtmx\Middlewares\ForgeHtmxMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

#[Group('forge-htmx')]

final class ForgeHtmxMiddlewareTest extends TestCase
{
    private function makeRequest(array $server = [], string $method = 'GET'): Request
    {
        return new Request(
            [],
            [],
            array_merge(['REQUEST_URI' => '/test', 'REQUEST_METHOD' => $method], $server),
            $method,
            [],
        );
    }

    private function htmlResponse(string $body = '<html><head></head><body>hello</body></html>'): Response
    {
        return new Response($body, 200, ['Content-Type' => 'text/html']);
    }

    private function jsonResponse(): Response
    {
        return new Response('{"ok":true}', 200, ['Content-Type' => 'application/json']);
    }

    #[Test('injects CSRF config script into HTML response before closing head')]
    public function injects_into_html(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => $this->htmlResponse(),
        );

        $content = $res->getContent();
        $this->assertStringContainsString('htmx:configRequest', $content);
        $this->assertStringContainsString('meta[name="csrf-token"]', $content);
        $this->assertStringContainsString('X-CSRF-TOKEN', $content);
    }

    #[Test('does not inject when HX-Request header is present')]
    public function skips_htmx_partial(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(['HTTP_HX_REQUEST' => 'true']),
            fn($r) => $this->htmlResponse(),
        );

        $this->assertStringNotContainsString('htmx:configRequest', $res->getContent());
    }

    #[Test('does not inject into JSON responses')]
    public function skips_json(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => $this->jsonResponse(),
        );

        $this->assertStringNotContainsString('htmx:configRequest', $res->getContent());
        $this->assertEquals('{"ok":true}', $res->getContent());
    }

    #[Test('injects into responses missing Content-Type but with DOCTYPE')]
    public function injects_html_detection(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => new Response('<!DOCTYPE html><html><head></head><body>hi</body></html>'),
        );

        $this->assertStringContainsString('htmx:configRequest', $res->getContent());
    }

    #[Test('does not inject into responses without DOCTYPE or Content-Type')]
    public function skips_no_doctype(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => new Response('<html><head></head><body>hi</body></html>'),
        );

        $this->assertStringNotContainsString('htmx:configRequest', $res->getContent());
    }

    #[Test('does not inject into plain text responses')]
    public function skips_plain_text(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => new Response('just text', 200, ['Content-Type' => 'text/plain']),
        );

        $this->assertStringNotContainsString('htmx:configRequest', $res->getContent());
    }

    #[Test('passes through response unchanged when skipped')]
    public function preserves_content_when_skipped(): void
    {
        $original = '<p>some content</p>';
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(['HTTP_HX_REQUEST' => 'true']),
            fn($r) => new Response($original),
        );

        $this->assertEquals($original, $res->getContent());
    }

    #[Test('script is placed before </head>')]
    public function script_before_closing_head(): void
    {
        $m = new ForgeHtmxMiddleware();
        $res = $m->handle(
            $this->makeRequest(),
            fn($r) => $this->htmlResponse(),
        );

        $content = $res->getContent();
        $headPos = strrpos($content, '</head>');
        $scriptPos = strrpos($content, 'htmx:configRequest');

        $this->assertTrue($headPos !== false);
        $this->assertTrue($scriptPos !== false);
        $this->assertLessThan($headPos, $scriptPos);
    }
}
