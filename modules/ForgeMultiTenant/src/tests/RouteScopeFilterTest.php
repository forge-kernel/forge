<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeMultiTenant\Services\RouteScopeFilter;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use ReflectionClass;

#[Group('multi-tenant')]
final class RouteScopeFilterTest extends TestCase
{
    private RouteScopeFilter $filter;

    #[BeforeEach]
    public function setup(): void
    {
        $this->filter = new RouteScopeFilter();
    }

    // --- extractScope ---

    #[Test('extractScope returns null when no attribute is present')]
    public function extract_scope_no_attribute(): void
    {
        $ref = new ReflectionClass($this);
        $this->assertNull($this->filter->extractScope($ref));
    }

    #[Test('extractScope returns TenantScope when attribute is present')]
    public function extract_scope_with_attribute(): void
    {
        $ref = new ReflectionClass(ScopeTestController::class);
        $scope = $this->filter->extractScope($ref);
        $this->assertNotNull($scope);
        $this->assertInstanceOf(TenantScope::class, $scope);
        $this->assertSame('central', $scope->value);
    }

    #[Test('extractScope extracts from method reflection')]
    public function extract_scope_from_method(): void
    {
        $ref = new ReflectionClass(ScopeTestController::class);
        $method = $ref->getMethod('tenantOnly');
        $scope = $this->filter->extractScope($method);
        $this->assertNotNull($scope);
        $this->assertSame('tenant', $scope->value);
    }

    // --- allowedHere ---

    #[Test('allowedHere central scope on central domain returns true')]
    public function allowed_central_on_central(): void
    {
        $scope = new TenantScope('central');
        $this->assertTrue($this->filter->allowedHere($scope, true));
    }

    #[Test('allowedHere central scope on tenant domain returns false')]
    public function allowed_central_on_tenant(): void
    {
        $scope = new TenantScope('central');
        $this->assertFalse($this->filter->allowedHere($scope, false));
    }

    #[Test('allowedHere tenant scope on tenant domain returns true')]
    public function allowed_tenant_on_tenant(): void
    {
        $scope = new TenantScope('tenant');
        $this->assertTrue($this->filter->allowedHere($scope, false));
    }

    #[Test('allowedHere tenant scope on central domain returns false')]
    public function allowed_tenant_on_central(): void
    {
        $scope = new TenantScope('tenant');
        $this->assertFalse($this->filter->allowedHere($scope, true));
    }

    #[Test('allowedHere both scope on any domain returns true')]
    public function allowed_both_anywhere(): void
    {
        $scope = new TenantScope('both');
        $this->assertTrue($this->filter->allowedHere($scope, true));
        $this->assertTrue($this->filter->allowedHere($scope, false));
    }

    #[Test('allowedHere non-TenantScope returns true')]
    public function allowed_non_tenant_scope(): void
    {
        $scope = new \stdClass();
        $this->assertTrue($this->filter->allowedHere($scope, true));
        $this->assertTrue($this->filter->allowedHere($scope, false));
    }
}

#[TenantScope('central')]
class ScopeTestController
{
    #[TenantScope('tenant')]
    public function tenantOnly(): void {}
}
