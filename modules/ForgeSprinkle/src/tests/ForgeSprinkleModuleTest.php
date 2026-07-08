<?php

declare(strict_types=1);

namespace Modules\ForgeSprinkle\Tests;

use Modules\ForgeSprinkle\ForgeSprinkleModule;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

#[Group('forge-sprinkle')]
final class ForgeSprinkleModuleTest extends TestCase
{
    private function makeRequest(): Request
    {
        return new Request([], [], [
            'REQUEST_URI' => '/test',
            'REQUEST_METHOD' => 'GET',
        ], 'GET', []);
    }

    private function htmlResponse(string $body = '<!DOCTYPE html><html><head></head><body>hello</body></html>'): Response
    {
        return new Response($body, 200, ['Content-Type' => 'text/html']);
    }

    private function jsonResponse(): Response
    {
        return new Response('{"ok":true}', 200, ['Content-Type' => 'application/json']);
    }

    #[Test('injects CSS and JS into HTML response')]
    public function injects_assets_into_html(): void
    {
        $response = $this->htmlResponse();
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $this->assertStringContainsString(
            needle: 'sprinkle.min.css',
            haystack: $content,
            message: 'CSS asset should be injected',
        );

        $this->assertStringContainsString(
            needle: 'sprinkle.min.js',
            haystack: $content,
            message: 'JS asset should be injected',
        );
    }

    #[Test('skips asset injection for JSON responses')]
    public function skips_assets_for_json(): void
    {
        $response = $this->jsonResponse();
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            needle: 'forge-sprinkle',
            haystack: $content,
            message: 'Assets should not appear in JSON responses',
        );
    }

    #[Test('CSS placed before closing head, JS before closing body')]
    public function assets_placed_correctly(): void
    {
        $response = $this->htmlResponse();
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $cssPos = strpos($content, 'sprinkle.min.css');
        $headPos = strpos($content, '</head>');
        $jsPos = strpos($content, 'sprinkle.min.js');
        $bodyPos = strpos($content, '</body>');

        $this->assertTrue($cssPos !== false && $cssPos < $headPos, 'CSS should be before </head>');
        $this->assertTrue($jsPos !== false && $jsPos < $bodyPos, 'JS should be before </body>');
    }

    #[Test('does not inject duplicate assets on multiple calls')]
    public function no_duplicate_assets(): void
    {
        $response = $this->htmlResponse();
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $this->assertEquals(
            expected: 1,
            actual: substr_count($content, 'sprinkle.min.css'),
            message: 'CSS should appear only once',
        );

        $this->assertEquals(
            expected: 1,
            actual: substr_count($content, 'sprinkle.min.js'),
            message: 'JS should appear only once',
        );
    }

    #[Test('skips injection when Content-Type is not HTML and no DOCTYPE')]
    public function skips_for_non_html_no_doctype(): void
    {
        $response = new Response('plain text', 200, ['Content-Type' => 'text/plain']);
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            needle: 'forge-sprinkle',
            haystack: $content,
            message: 'Assets should not appear in plain text responses',
        );
    }

    #[Test('module registers assets without any response Content-Type header by checking DOCTYPE')]
    public function injects_when_content_type_missing_but_doctype_present(): void
    {
        $response = new Response('<!DOCTYPE html><html><head></head><body>hello</body></html>', 200, []);
        $module = new ForgeSprinkleModule();

        $request = $this->makeRequest();
        $module->onAfterRequest($request, $response);

        $content = $response->getContent();

        $this->assertStringContainsString(
            needle: 'sprinkle.min.js',
            haystack: $content,
            message: 'JS should be injected when DOCTYPE is present even without Content-Type header',
        );
    }
}
