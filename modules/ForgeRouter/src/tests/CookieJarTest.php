<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\Cookie;
use App\Modules\ForgeRouter\Http\CookieJar;

#[Group('http')]
final class CookieJarTest extends TestCase
{
    #[Test('make creates Cookie with correct name and value')]
    public function make_creates_cookie(): void
    {
        $jar = new CookieJar();
        $cookie = $jar->make('session', 'abc');
        $this->assertEquals('session', $cookie->name);
        $this->assertEquals('abc', $cookie->value);
    }

    #[Test('make sets expires to 0 when minutes is 0')]
    public function make_zero_minutes_no_expiry(): void
    {
        $jar = new CookieJar();
        $cookie = $jar->make('tok', 'val', 0);
        $this->assertEquals(0, $cookie->expires);
    }

    #[Test('make sets expires when minutes > 0')]
    public function make_with_minutes_sets_expiry(): void
    {
        $jar = new CookieJar();
        $before = time();
        $cookie = $jar->make('tok', 'val', 60);
        $this->assertTrue($cookie->expires >= $before + 3600);
    }

    #[Test('make applies options for path, domain, secure, httponly, samesite')]
    public function make_applies_options(): void
    {
        $jar = new CookieJar();
        $cookie = $jar->make('c', 'v', 0, [
            'path' => '/admin',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        $this->assertEquals('/admin', $cookie->path);
        $this->assertEquals('example.com', $cookie->domain);
        $this->assertTrue($cookie->secure);
        $this->assertTrue($cookie->httponly);
        $this->assertEquals('Strict', $cookie->samesite);
    }

    #[Test('queue stores cookies, getQueuedCookies returns them')]
    public function queue_and_retrieve(): void
    {
        $jar = new CookieJar();
        $jar->queue(new Cookie('a', '1'));
        $jar->queue(new Cookie('b', '2'));
        $cookies = $jar->getQueuedCookies();
        $this->assertEquals(2, count($cookies));
        $this->assertEquals('a', $cookies[0]->name);
        $this->assertEquals('b', $cookies[1]->name);
    }

    #[Test('getQueuedCookies returns empty array initially')]
    public function initially_empty(): void
    {
        $jar = new CookieJar();
        $this->assertEquals([], $jar->getQueuedCookies());
    }
}
