<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\ApiResponse;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Traits\ResponseHelper;

#[Group('http')]
final class ControllerHelperTest extends TestCase
{
    private function runner(): object
    {
        return new class {
            use ResponseHelper;

            public function callJsonResponse(array $data, int $status = 200): Response
            {
                return $this->jsonResponse($data, $status);
            }

            public function callApiResponse(mixed $data, int $status = 200): ApiResponse
            {
                return $this->apiResponse($data, $status);
            }

            public function callApiError(string $msg, int $status = 400, array $errors = [], string $code = 'ERROR_CODE'): ApiResponse
            {
                return $this->apiError($msg, $status, $errors, $code);
            }

            public function callCsvResponse(array $data, string $filename = 'export.csv'): Response
            {
                return $this->csvResponse($data, $filename);
            }
        };
    }

    #[Test('jsonResponse returns JSON-encoded body')]
    public function json_response_encodes_data(): void
    {
        $res = $this->runner()->callJsonResponse(['key' => 'value']);
        $decoded = json_decode($res->getContent(), true);
        $this->assertEquals('value', $decoded['key']);
    }

    #[Test('jsonResponse sets Content-Type to application/json')]
    public function json_response_content_type(): void
    {
        $res = $this->runner()->callJsonResponse([]);
        $this->assertEquals('application/json', $res->getHeader('Content-Type'));
    }

    #[Test('jsonResponse defaults to 200')]
    public function json_response_default_status(): void
    {
        $res = $this->runner()->callJsonResponse([]);
        $this->assertEquals(200, $res->getStatusCode());
    }

    #[Test('jsonResponse respects custom status code')]
    public function json_response_custom_status(): void
    {
        $res = $this->runner()->callJsonResponse([], 201);
        $this->assertEquals(201, $res->getStatusCode());
    }

    #[Test('apiResponse returns ApiResponse instance')]
    public function api_response_is_api_response(): void
    {
        $res = $this->runner()->callApiResponse(['id' => 1]);
        $this->assertInstanceOf(ApiResponse::class, $res);
    }

    #[Test('apiError returns ApiResponse with error meta')]
    public function api_error_has_error_meta(): void
    {
        $res = $this->runner()->callApiError('Not found', 404, [], 'NOT_FOUND');
        $this->assertEquals(404, $res->getStatusCode());
        $arr = $res->toArray();
        $this->assertEquals('Not found', $arr['meta']['error']['message']);
        $this->assertEquals('NOT_FOUND', $arr['meta']['error']['code']);
    }

    #[Test('apiError includes validation errors array')]
    public function api_error_includes_errors(): void
    {
        $res = $this->runner()->callApiError('Validation failed', 422, ['name' => 'required']);
        $arr = $res->toArray();
        $this->assertEquals('required', $arr['meta']['error']['errors']['name']);
    }

    #[Test('csvResponse sets Content-Type to text/csv')]
    public function csv_response_content_type(): void
    {
        $res = $this->runner()->callCsvResponse([['a', 'b'], ['1', '2']]);
        $this->assertEquals('text/csv', $res->getHeader('Content-Type'));
    }

    #[Test('csvResponse body contains CSV rows')]
    public function csv_response_contains_rows(): void
    {
        $res = $this->runner()->callCsvResponse([['name', 'email'], ['Alice', 'a@a.com']]);
        $this->assertTrue(str_contains($res->getContent(), 'Alice'));
        $this->assertTrue(str_contains($res->getContent(), 'name'));
    }
}
