<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeRouter\Http\ApiResponse;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Traits\ResponseHelper;

#[Group('http')]
final class ResponseHelperTest extends TestCase
{
    private function runner(): object
    {
        return new class {
            use ResponseHelper;

            public function error(Request $req, string $msg = 'Too Many Requests', int $code = 429): Response
            {
                return $this->createErrorResponse($req, $msg, $code);
            }

            public function respond(Request $req, mixed $content, int $code = 200): Response
            {
                return $this->createResponse($req, $content, $code);
            }
        };
    }

    private function makeRequest(array $server = []): Request
    {
        return new Request([], [], array_merge(['REQUEST_URI' => '/'], $server), 'GET', []);
    }

    #[Test('createErrorResponse returns ApiResponse when Accept is application/json')]
    public function error_response_json_accept(): void
    {
        $req = $this->makeRequest(['HTTP_ACCEPT' => 'application/json']);
        $res = $this->runner()->error($req, 'Oops', 422);
        $this->assertInstanceOf(ApiResponse::class, $res);
        $this->assertEquals(422, $res->getStatusCode());
    }

    #[Test('createErrorResponse returns plain Response when Accept is not json')]
    public function error_response_html_accept(): void
    {
        $req = $this->makeRequest(['HTTP_ACCEPT' => 'text/html']);
        $res = $this->runner()->error($req, 'Oops', 422);
        $this->assertFalse($res instanceof ApiResponse);
        $this->assertEquals('Oops', $res->getContent());
    }

    #[Test('createErrorResponse JSON body contains error key')]
    public function error_response_json_body(): void
    {
        $req = $this->makeRequest(['HTTP_ACCEPT' => 'application/json']);
        $res = $this->runner()->error($req, 'Rate limit exceeded', 429);
        $body = json_decode($res->getContent(), true);
        $this->assertEquals('Rate limit exceeded', $body['data']['error']);
    }

    #[Test('createResponse returns ApiResponse when Accept is application/json')]
    public function create_response_json(): void
    {
        $req = $this->makeRequest(['HTTP_ACCEPT' => 'application/json']);
        $res = $this->runner()->respond($req, ['id' => 1], 200);
        $this->assertInstanceOf(ApiResponse::class, $res);
    }

    #[Test('createResponse returns HTML Response when Accept is not json')]
    public function create_response_html(): void
    {
        $req = $this->makeRequest();
        $res = $this->runner()->respond($req, '<p>Hello</p>', 200);
        $this->assertFalse($res instanceof ApiResponse);
        $this->assertEquals('text/html', $res->getHeader('Content-Type'));
    }

    #[Test('createResponse respects custom status code')]
    public function create_response_custom_status(): void
    {
        $req = $this->makeRequest(['HTTP_ACCEPT' => 'application/json']);
        $res = $this->runner()->respond($req, null, 201);
        $this->assertEquals(201, $res->getStatusCode());
    }
}
