<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\Cookie;
use App\Modules\ForgeRouter\Http\Response;

#[Group('http')]
final class ResponseTest extends TestCase
{
    #[Test('Response defaults to 200 and empty content')]
    public function default_status_and_content(): void
    {
        $r = new Response('');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('', $r->getContent());
    }

    #[Test('Response stores content correctly')]
    public function stores_content(): void
    {
        $r = new Response('<h1>Hello</h1>');
        $this->assertEquals('<h1>Hello</h1>', $r->getContent());
    }

    #[Test('setStatusCode changes status and is chainable')]
    public function set_status_code_chainable(): void
    {
        $r = new Response('');
        $result = $r->setStatusCode(404);
        $this->assertSame($r, $result);
        $this->assertEquals(404, $r->getStatusCode());
    }

    #[Test('setContent updates content')]
    public function set_content_updates(): void
    {
        $r = new Response('old');
        $r->setContent('new');
        $this->assertEquals('new', $r->getContent());
    }

    #[Test('setHeader stores header and is chainable')]
    public function set_header_chainable(): void
    {
        $r = new Response('');
        $result = $r->setHeader('Content-Type', 'application/json');
        $this->assertSame($r, $result);
        $this->assertEquals('application/json', $r->getHeader('Content-Type'));
    }

    #[Test('hasHeader returns true for set header, false otherwise')]
    public function has_header(): void
    {
        $r = new Response('', 200, ['X-Custom' => 'value']);
        $this->assertTrue($r->hasHeader('X-Custom'));
        $this->assertFalse($r->hasHeader('X-Missing'));
    }

    #[Test('getHeaders returns all headers')]
    public function get_headers_all(): void
    {
        $r = new Response('', 200, ['A' => '1', 'B' => '2']);
        $headers = $r->getHeaders();
        $this->assertEquals('1', $headers['A']);
        $this->assertEquals('2', $headers['B']);
    }

    #[Test('setHeaders replaces all headers')]
    public function set_headers_replaces(): void
    {
        $r = new Response('', 200, ['Old' => 'value']);
        $r->setHeaders(['New' => 'replacement']);
        $this->assertFalse($r->hasHeader('Old'));
        $this->assertTrue($r->hasHeader('New'));
    }

    #[Test('setCookie stores cookie and is chainable')]
    public function set_cookie_chainable(): void
    {
        $r = new Response('');
        $cookie = new Cookie('session', 'abc123');
        $result = $r->setCookie($cookie);
        $this->assertSame($r, $result);
        $cookies = $r->getCookies();
        $this->assertEquals(1, count($cookies));
        $this->assertEquals('session', $cookies[0]->name);
        $this->assertEquals('abc123', $cookies[0]->value);
    }

    #[Test('withCookie also stores cookie')]
    public function with_cookie_stores(): void
    {
        $r = new Response('');
        $r->withCookie(new Cookie('tok', 'xyz'));
        $this->assertEquals(1, count($r->getCookies()));
    }

    #[Test('getHeader returns null for missing key')]
    public function get_header_missing_returns_null(): void
    {
        $r = new Response('');
        $this->assertNull($r->getHeader('X-Missing'));
    }
}
