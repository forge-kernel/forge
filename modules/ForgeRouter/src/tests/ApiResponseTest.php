<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\ApiResponse;

#[Group('http')]
final class ApiResponseTest extends TestCase
{
    #[Test('ApiResponse sets Content-Type to application/json')]
    public function sets_json_content_type(): void
    {
        $r = new ApiResponse(['id' => 1]);
        $this->assertEquals('application/json', $r->getHeader('Content-Type'));
    }

    #[Test('ApiResponse wraps data in JSON envelope')]
    public function wraps_data(): void
    {
        $r = new ApiResponse(['name' => 'forge']);
        $body = json_decode($r->getContent(), true);
        $this->assertEquals('forge', $body['data']['name']);
    }

    #[Test('ApiResponse defaults to 200')]
    public function defaults_to_200(): void
    {
        $r = new ApiResponse([]);
        $this->assertEquals(200, $r->getStatusCode());
    }

    #[Test('ApiResponse respects custom status code')]
    public function custom_status(): void
    {
        $r = new ApiResponse(['error' => 'not found'], 404);
        $this->assertEquals(404, $r->getStatusCode());
    }

    #[Test('ApiResponse meta is empty by default')]
    public function meta_empty_by_default(): void
    {
        $r = new ApiResponse(['x' => 1]);
        $body = json_decode($r->getContent(), true);
        $this->assertEquals([], $body['meta']);
    }

    #[Test('withMeta merges meta into response body')]
    public function with_meta_merges(): void
    {
        $r = new ApiResponse(['item' => 'a']);
        $r->withMeta(['total' => 10, 'page' => 1]);
        $body = json_decode($r->getContent(), true);
        $this->assertEquals(10, $body['meta']['total']);
        $this->assertEquals(1, $body['meta']['page']);
    }

    #[Test('withMeta preserves data')]
    public function with_meta_preserves_data(): void
    {
        $r = new ApiResponse(['value' => 42]);
        $r->withMeta(['count' => 1]);
        $body = json_decode($r->getContent(), true);
        $this->assertEquals(42, $body['data']['value']);
    }

    #[Test('toArray returns structured array with data, meta, status, and headers')]
    public function to_array_structure(): void
    {
        $r = new ApiResponse(['key' => 'val'], 201);
        $arr = $r->toArray();
        $this->assertEquals(201, $arr['status']);
        $this->assertTrue(isset($arr['data']));
        $this->assertTrue(isset($arr['meta']));
        $this->assertTrue(isset($arr['headers']));
    }

    #[Test('ApiResponse merges extra headers while keeping Content-Type')]
    public function merges_extra_headers(): void
    {
        $r = new ApiResponse([], 200, ['X-Request-Id' => 'abc123']);
        $this->assertEquals('application/json', $r->getHeader('Content-Type'));
        $this->assertEquals('abc123', $r->getHeader('X-Request-Id'));
    }
}
