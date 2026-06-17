<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Request;

#[Group('helpers')]
final class RedirectTest extends TestCase
{
    #[Test('Redirect::to returns Response with Location header and 302 default')]
    public function to_returns_redirect_response(): void
    {
        $response = Redirect::to('/login');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
    }

    #[Test('Redirect::to respects custom status and additional headers')]
    public function to_with_custom_status_and_headers(): void
    {
        $response = Redirect::to('/dashboard', 301, ['X-Custom' => 'Redirect']);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->getHeader('Location'));
        $this->assertEquals('Redirect', $response->getHeader('X-Custom'));
    }

    #[Test('Redirect::back uses Referer header if present')]
    public function back_uses_referer(): void
    {
        $request = new Request([], [], ['HTTP_REFERER' => '/previous-page'], 'GET', []);
        $response = Redirect::back($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/previous-page', $response->getHeader('Location'));
    }

    #[Test('Redirect::back falls back to root if Referer missing')]
    public function back_falls_back_to_root(): void
    {
        $request = new Request([], [], [], 'GET', []);
        $response = Redirect::back($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getHeader('Location'));
    }

    #[Test('Redirect::back merges additional headers')]
    public function back_with_headers(): void
    {
        $request = new Request([], [], ['HTTP_REFERER' => '/home'], 'GET', []);
        $response = Redirect::back($request, ['Cache-Control' => 'no-cache']);
        $this->assertEquals('/home', $response->getHeader('Location'));
        $this->assertTrue($response->hasHeader('Cache-Control'));
    }
}
