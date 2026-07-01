<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\Services\CentralDomain;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group('multi-tenant')]
final class CentralDomainTest extends TestCase
{
    #[Test('get returns a non-empty string')]
    public function get_returns_string(): void
    {
        $domain = CentralDomain::get();
        $this->assertNotEmpty($domain);
    }

    #[Test('stripPort removes port from host:port')]
    public function strip_port_removes_port(): void
    {
        $this->assertSame('example.com', CentralDomain::stripPort('example.com:8080'));
    }

    #[Test('stripPort returns host unchanged when no port')]
    public function strip_port_no_port(): void
    {
        $this->assertSame('example.com', CentralDomain::stripPort('example.com'));
    }

    #[Test('stripPort handles IPv6 without port')]
    public function strip_port_ipv6_no_port(): void
    {
        $this->assertSame('::1', CentralDomain::stripPort('::1'));
    }

    #[Test('stripPort handles IPv6 loopback')]
    public function strip_port_ipv6_loopback(): void
    {
        $this->assertSame('[::1]', CentralDomain::stripPort('[::1]'));
    }

    #[Test('isLocal returns true for localhost')]
    public function is_local_localhost(): void
    {
        $this->assertTrue(CentralDomain::isLocal('localhost'));
    }

    #[Test('isLocal returns true for 127.0.0.1')]
    public function is_local_ipv4(): void
    {
        $this->assertTrue(CentralDomain::isLocal('127.0.0.1'));
    }

    #[Test('isLocal returns true for IPv6 loopback')]
    public function is_local_ipv6(): void
    {
        $this->assertTrue(CentralDomain::isLocal('[::1]'));
    }

    #[Test('isLocal returns true for IPv6 without brackets')]
    public function is_local_ipv6_unbracketed(): void
    {
        $this->assertTrue(CentralDomain::isLocal('::1'));
    }

    #[Test('isLocal returns false for external host')]
    public function is_local_external(): void
    {
        $this->assertFalse(CentralDomain::isLocal('example.com'));
    }

    #[Test('isLocal strips port before checking')]
    public function is_local_strips_port(): void
    {
        $this->assertTrue(CentralDomain::isLocal('localhost:3000'));
        $this->assertTrue(CentralDomain::isLocal('127.0.0.1:8080'));
    }
}
