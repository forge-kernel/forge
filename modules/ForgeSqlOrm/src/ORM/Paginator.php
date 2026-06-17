<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Contracts\PaginatorInterface;
use Forge\Core\Helpers\Url;
use JsonSerializable;

/**
 * Comprehensive pagination system supporting both offset and cursor-based pagination
 * with filtering, search, ordering, meta information, and HATEOAS URLs.
 */
final class Paginator implements PaginatorInterface, JsonSerializable
{
  private array $items;
  private int $total;
  private int $perPage;
  private int $currentPage;
  private ?string $cursor = null;
  private bool $hasMore;
  private array $filters = [];
  private array $searchFields = [];
  private ?string $search = null;
  private string $sortColumn;
  private string $sortDirection;
  private string $baseUrl;
  private array $queryParams = [];

  public function __construct(
    array $items,
    int $total,
    int $perPage,
    int $currentPage = 1,
    ?string $cursor = null,
    string $sortColumn = 'created_at',
    string $sortDirection = 'ASC',
    array $filters = [],
    ?string $search = null,
    array $searchFields = [],
    string $baseUrl = '',
    array $queryParams = []
  ) {
    $this->items = $items;
    $this->total = $total;
    $this->perPage = $perPage;
    $this->currentPage = max(1, $currentPage);
    $this->cursor = $cursor;
    $this->hasMore = count($items) > $perPage || ($currentPage * $perPage) < $total;
    $this->filters = $filters;
    $this->search = $search;
    $this->searchFields = $searchFields;
    $this->sortColumn = $sortColumn;
    $this->sortDirection = $sortDirection;
    $this->baseUrl = $baseUrl ?: Url::baseUrl();
    $this->queryParams = $queryParams;
  }

  public function items(): array
  {
    return $this->items;
  }

  public function total(): int
  {
    return $this->total;
  }

  public function perPage(): int
  {
    return $this->perPage;
  }

  public function currentPage(): int
  {
    return $this->currentPage;
  }

  public function lastPage(): int
  {
    return (int) ceil($this->total / $this->perPage);
  }

  public function hasMorePages(): bool
  {
    return $this->hasMore;
  }

  public function hasPreviousPage(): bool
  {
    return $this->currentPage > 1;
  }

  public function previousPageUrl(): ?string
  {
    if (!$this->hasPreviousPage()) {
      return null;
    }
    return $this->url($this->currentPage - 1);
  }

  public function nextPageUrl(): ?string
  {
    if (!$this->hasMorePages()) {
      return null;
    }
    return $this->url($this->currentPage + 1);
  }

  public function firstPageUrl(): ?string
  {
    return $this->url(1);
  }

  public function lastPageUrl(): ?string
  {
    return $this->url($this->lastPage());
  }

  public function url(int $page): string
  {
    $params = array_merge($this->queryParams, [
      'page' => $page,
      'per_page' => $this->perPage,
      'sort' => $this->sortColumn,
      'direction' => $this->sortDirection,
    ]);

    if ($this->search) {
      $params['search'] = $this->search;
    }

    foreach ($this->filters as $key => $value) {
      if ($value !== null && $value !== '') {
        $params["filter[{$key}]"] = $value;
      }
    }

    $queryString = http_build_query($params);
    return $this->baseUrl . '?' . $queryString;
  }

  public function links(): array
  {
    return [
      'first' => $this->firstPageUrl(),
      'last' => $this->lastPageUrl(),
      'prev' => $this->previousPageUrl(),
      'next' => $this->nextPageUrl(),
      'self' => $this->url($this->currentPage),
    ];
  }

  public function meta(): array
  {
    return [
      'total' => $this->total,
      'per_page' => $this->perPage,
      'current_page' => $this->currentPage,
      'last_page' => $this->lastPage(),
      'from' => $this->from(),
      'to' => $this->to(),
      'has_more' => $this->hasMorePages(),
      'has_previous' => $this->hasPreviousPage(),
      'links' => $this->links(),
      'filters' => $this->filters,
      'search' => $this->search,
      'sort' => [
        'column' => $this->sortColumn,
        'direction' => $this->sortDirection,
      ],
    ];
  }

  public function toArray(): array
  {
    return [
      'data' => $this->items,
      'meta' => $this->meta(),
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }

  private function from(): int
  {
    if ($this->total === 0) {
      return 0;
    }
    return (($this->currentPage - 1) * $this->perPage) + 1;
  }

  private function to(): int
  {
    return min($this->currentPage * $this->perPage, $this->total);
  }

  public function cursor(): ?string
  {
    return $this->cursor;
  }

  public function setBaseUrl(string $url): self
  {
    $this->baseUrl = $url;
    return $this;
  }

  public function setQueryParams(array $params): self
  {
    $this->queryParams = $params;
    return $this;
  }
}
