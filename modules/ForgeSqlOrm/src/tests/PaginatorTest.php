<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeSqlOrm\ORM\Paginator;

#[Group("forgesql-paginator")]
final class PaginatorTest extends TestCase
{
    #[Test("basic pagination properties")]
    public function basic_properties(): void
    {
        $p = new Paginator(
            items: [['id' => 1], ['id' => 2]],
            total: 10,
            perPage: 2,
            currentPage: 1,
        );

        $this->assertCount(2, $p->items());
        $this->assertSame(10, $p->total());
        $this->assertSame(2, $p->perPage());
        $this->assertSame(1, $p->currentPage());
        $this->assertSame(5, $p->lastPage());
        $this->assertTrue($p->hasMorePages());
        $this->assertNull($p->cursor());
    }

    #[Test("last page calculation")]
    public function last_page(): void
    {
        $p = new Paginator(
            items: [],
            total: 0,
            perPage: 15,
            currentPage: 1,
        );
        $this->assertSame(1, $p->lastPage());
        $this->assertFalse($p->hasMorePages());
    }

    #[Test("empty result set")]
    public function empty_results(): void
    {
        $p = new Paginator(
            items: [],
            total: 0,
            perPage: 15,
            currentPage: 1,
        );
        $this->assertCount(0, $p->items());
        $this->assertSame(0, $p->total());
        $this->assertSame(1, $p->lastPage());
    }

    #[Test("last page of results")]
    public function last_page_no_more(): void
    {
        $items = array_map(fn($i) => ['id' => $i], range(1, 3));
        $p = new Paginator(
            items: $items,
            total: 13,
            perPage: 3,
            currentPage: 5,
        );
        $this->assertCount(3, $p->items());
        $this->assertSame(5, $p->lastPage());
        $this->assertFalse($p->hasMorePages());
    }

    #[Test("cursor is null when not provided")]
    public function cursor_null(): void
    {
        $p = new Paginator(
            items: [],
            total: 0,
            perPage: 15,
            currentPage: 1,
        );
        $this->assertNull($p->cursor());
    }

    #[Test("sort and filter options stored")]
    public function sort_filter_options(): void
    {
        $p = new Paginator(
            items: [['id' => 1]],
            total: 1,
            perPage: 15,
            currentPage: 1,
            cursor: null,
            sortColumn: 'name',
            sortDirection: 'DESC',
            filters: ['age' => 25],
            search: 'alice',
            searchFields: ['name'],
        );
        $this->assertSame('name', $p->sortColumn());
        $this->assertSame('DESC', $p->sortDirection());
        $this->assertSame(['age' => 25], $p->filters());
        $this->assertSame('alice', $p->search());
    }

    #[Test("base url and query params for pagination links")]
    public function url_options(): void
    {
        $p = new Paginator(
            items: [['id' => 1]],
            total: 10,
            perPage: 2,
            currentPage: 1,
            cursor: null,
            baseUrl: '/users',
            queryParams: ['role' => 'admin'],
        );
        $this->assertSame('/users', $p->baseUrl());
        $this->assertSame(['role' => 'admin'], $p->queryParams());
    }
}
