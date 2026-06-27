<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Http\Request;

#[Group('http')]
final class RequestTest extends TestCase
{
    private function makeRequest(
        array $query = [],
        array $post = [],
        array $server = [],
        string $method = 'GET',
        array $cookies = [],
    ): Request {
        $server = array_merge(['REQUEST_URI' => '/test?foo=bar', 'REQUEST_METHOD' => $method], $server);
        return new Request($query, $post, $server, $method, $cookies);
    }

    #[Test('getMethod returns the HTTP method')]
    public function get_method_returns_method(): void
    {
        $req = $this->makeRequest(method: 'POST');
        $this->assertEquals('POST', $req->getMethod());
    }

    #[Test('getUri returns parsed path without query string')]
    public function get_uri_strips_query(): void
    {
        $req = $this->makeRequest(server: ['REQUEST_URI' => '/some/path?q=1']);
        $this->assertEquals('/some/path', $req->getUri());
    }

    #[Test('getPath always starts with slash')]
    public function get_path_starts_with_slash(): void
    {
        $req = $this->makeRequest(server: ['REQUEST_URI' => '/about']);
        $this->assertEquals('/about', $req->getPath());
    }

    #[Test('getHeader is case-insensitive')]
    public function get_header_case_insensitive(): void
    {
        $req = $this->makeRequest(server: ['HTTP_ACCEPT' => 'application/json']);
        $this->assertEquals('application/json', $req->getHeader('Accept'));
        $this->assertEquals('application/json', $req->getHeader('accept'));
        $this->assertEquals('application/json', $req->getHeader('ACCEPT'));
    }

    #[Test('hasHeader returns false for missing header')]
    public function has_header_false_for_missing(): void
    {
        $req = $this->makeRequest();
        $this->assertFalse($req->hasHeader('X-Custom-Header'));
    }

    #[Test('input prefers POST over query params')]
    public function input_prefers_post(): void
    {
        $req = $this->makeRequest(query: ['key' => 'from_query'], post: ['key' => 'from_post']);
        $this->assertEquals('from_post', $req->input('key'));
    }

    #[Test('input falls back to query params')]
    public function input_falls_back_to_query(): void
    {
        $req = $this->makeRequest(query: ['q' => 'search']);
        $this->assertEquals('search', $req->input('q'));
    }

    #[Test('all merges query and post data')]
    public function all_merges_data(): void
    {
        $req = $this->makeRequest(query: ['a' => '1'], post: ['b' => '2']);
        $all = $req->all();
        $this->assertEquals('1', $all['a']);
        $this->assertEquals('2', $all['b']);
    }

    #[Test('getUri and getPath share the same parsed result')]
    public function get_uri_and_path_consistent(): void
    {
        $req = $this->makeRequest(server: ['REQUEST_URI' => '/hello/world']);
        $this->assertEquals('/hello/world', $req->getUri());
        $this->assertEquals('/hello/world', $req->getPath());
    }
}
