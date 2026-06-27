<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Traits;

use App\Modules\ForgeRouter\Http\Request;
use Forge\Core\Helpers\Url;

trait PaginationHelper
{
  protected function getPaginationParams(Request $request): array
  {
    $page = isset($request->queryParams['page']) && is_numeric($request->queryParams['page'])
      ? (int) $request->queryParams['page']
      : 1;

    $limit = isset($request->queryParams['per_page']) && is_numeric($request->queryParams['per_page'])
      ? (int) $request->queryParams['per_page']
      : 15;

    $sortColumn = isset($request->queryParams['sort']) && is_string($request->queryParams['sort'])
      ? (string) $request->queryParams['sort']
      : 'created_at';

    $sortDirection = isset($request->queryParams['direction']) && is_string($request->queryParams['direction'])
      ? (string) $request->queryParams['direction']
      : 'ASC';

    $search = isset($request->queryParams['search']) && is_string($request->queryParams['search'])
      ? (string) $request->queryParams['search']
      : '';

    $filters = [];
    foreach ($request->queryParams as $key => $value) {
      if (str_starts_with($key, 'filter[') && str_ends_with($key, ']')) {
        $field = substr($key, 7, -1);
        $filters[$field] = $value;
      }
    }

    $page = max(1, $page);
    $limit = max(1, min($limit, 100));

    return [
      'page' => $page,
      'limit' => $limit,
      'column' => $sortColumn,
      'direction' => $sortDirection,
      'search' => $search,
      'filters' => $filters,
    ];
  }

  protected function getPaginationParamsForApi(Request $request): array
  {
    $params = $this->getPaginationParams($request);

    $baseUrl = Url::baseUrl();

    $queryParams = [];
    foreach ($request->queryParams as $key => $value) {
      if (
        !in_array($key, ['page', 'per_page', 'sort', 'direction', 'search'], true)
        && !str_starts_with($key, 'filter[')
      ) {
        $queryParams[$key] = $value;
      }
    }

    return array_merge($params, [
      'baseUrl' => $baseUrl,
      'queryParams' => $queryParams,
    ]);
  }

  protected static function getPaginationLinks(
    int $page,
    int $perPage,
    int $totalPages,
    ?string $sortColumn = 'created_at',
    ?string $sortDirection = 'ASC',
    ?string $search = ''
  ): array {
    $baseUrl = Url::baseUrl();
    $links = [];
    $queryParams = http_build_query([
      'per_page' => $perPage,
      'sort' => $sortColumn,
      'direction' => $sortDirection,
      'search' => $search
    ]);
    $links['self'] = "$baseUrl?page=$page&$queryParams";

    if ($page > 1) {
      $links['first'] = "$baseUrl?page=1&$queryParams";
      $links['prev'] = "$baseUrl?page=" . ($page - 1) . "&$queryParams";
    }

    if ($page < $totalPages) {
      $links['next'] = "$baseUrl?page=" . ($page + 1) . "&$queryParams";
      $links['last'] = "$baseUrl?page=$totalPages&$queryParams";
    }

    return $links;
  }
}
