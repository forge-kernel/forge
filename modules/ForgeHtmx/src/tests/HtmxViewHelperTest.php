<?php

declare(strict_types=1);

namespace Modules\ForgeHtmx\Tests;

use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeHtmx\Traits\HtmxViewHelper;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\DI\Container;

#[Group('htmx')]
final class HtmxViewHelperTest extends TestCase
{
    private function makeRequest(bool $isHtmx = false): Request
    {
        $server = ['REQUEST_URI' => '/test'];

        if ($isHtmx) {
            $server['HTTP_HX_REQUEST'] = 'true';
        }

        return new Request([], [], $server, 'GET', []);
    }

    private function runner(): object
    {
        return new class {
            use HtmxViewHelper;

            public ?string $lastView = null;
            public ?array $lastData = null;

            protected function view(string $view, array $data = [], ?string $layout = null): Response
            {
                $this->lastView = $view;
                $this->lastData = $data;

                return new Response('');
            }

            public function callHtmxView(string $view, array $data = [], ?string $partial = null): Response
            {
                return $this->htmxView($view, $data, $partial);
            }
        };
    }

    #[BeforeEach]
    public function setUp(): void
    {
        Container::getInstance();
    }

    #[Test('htmxView with HTMX request and partial renders partial view')]
    public function htmx_request_with_partial(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest(isHtmx: true));

        $runner = $this->runner();
        $runner->callHtmxView('products/index', ['foo' => 'bar'], partial: 'products/list');

        $this->assertEquals('products/list', $runner->lastView);
        $this->assertEquals(['foo' => 'bar'], $runner->lastData);
    }

    #[Test('htmxView with HTMX request and no partial renders original view')]
    public function htmx_request_without_partial(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest(isHtmx: true));

        $runner = $this->runner();
        $runner->callHtmxView('products/list', ['x' => 1]);

        $this->assertEquals('products/list', $runner->lastView);
        $this->assertEquals(['x' => 1], $runner->lastData);
    }

    #[Test('htmxView with full request renders original view regardless of partial')]
    public function full_request_ignores_partial(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest(isHtmx: false));

        $runner = $this->runner();
        $runner->callHtmxView('products/index', ['a' => 1], partial: 'products/list');

        $this->assertEquals('products/index', $runner->lastView);
        $this->assertEquals(['a' => 1], $runner->lastData);
    }

    #[Test('htmxView with no HX-Request header renders full view')]
    public function no_hx_header_renders_full(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest());

        $runner = $this->runner();
        $runner->callHtmxView('home/index');

        $this->assertEquals('home/index', $runner->lastView);
    }

    #[Test('htmxView delegates data array correctly')]
    public function passes_data_through(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest(isHtmx: true));

        $runner = $this->runner();
        $data = ['products' => [1, 2, 3], 'title' => 'Listing'];
        $runner->callHtmxView('full/view', $data, partial: 'partial/view');

        $this->assertEquals('partial/view', $runner->lastView);
        $this->assertSame($data, $runner->lastData);
    }

    #[Test('htmxView returns Response instance')]
    public function returns_response(): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $this->makeRequest());

        $runner = $this->runner();
        $res = $runner->callHtmxView('page');

        $this->assertInstanceOf(Response::class, $res);
    }
}
