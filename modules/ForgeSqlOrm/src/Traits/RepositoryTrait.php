<?php

namespace Modules\ForgeSqlOrm\Traits;

use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Forge\Core\Dto\BaseDto;
use Forge\Core\Helpers\Url;

trait RepositoryTrait
{
    /** @return static[]|BaseDto[] */
    public static function findAll(): array
    {
        return static::fetch(static::query()->select("*"));
    }

    protected static function fetch(QueryBuilderInterface $query): array
    {
        $results = $query->get();
        $items = [];
        if (is_array($results)) {
            foreach ($results as $result) {
                $dtoClass = static::$dtoClass ?? null;
                if ($dtoClass && class_exists($dtoClass)) {
                    $dto = new $dtoClass($result);
                    $items[] = static::sanitizeDto($dto);
                } else {
                    $items[] = $result;
                }
            }
        } elseif ($results) {
            $dtoClass = static::$dtoClass ?? null;
            if ($dtoClass && class_exists($dtoClass)) {
                $dto = new $dtoClass($results);
                $items[] = static::sanitizeDto($dto);
            } else {
                $items[] = $results;
            }
        }
        return $items;
    }

    protected static function query(): QueryBuilderInterface
    {
        /*** @var QueryBuilderInterface $queryBuilder */
        $queryBuilder = Container::getInstance()->get(QueryBuilderInterface::class);
        $queryBuilder->reset()->setTable(static::getTable());
        return $queryBuilder;
    }

    public static function findById(mixed $id): static|null
    {
        $result = static::query()
            ->where(static::getPrimaryKey(), '=', $id)
            ->first();

        return new static($result);
    }

    public static function findBy(string $property, mixed $value): static|null
    {
        $result = static::query()
            ->where($property, '=', $value)
            ->first();
        if ($result) {
            return new static($result);
        } else {
            return null;
        }
    }

    public static function paginate(
        int    $page,
        int    $perPage,
        string $column = 'created_at',
        string $direction = 'ASC',
        string $search = ''
    ): array
    {
        $offset = ($page - 1) * $perPage;
        $query = static::query();

        $baseQuery = clone $query;

        if ($search && static::$searchableFields) {
            static::applySearch($baseQuery, $search);
        }

        $dataQuery = clone $baseQuery;
        $results = static::fetch(
            $dataQuery
                ->select("*")
                ->limit($perPage)
                ->offset($offset)
                ->orderBy($column, $direction)
        );

        $items = $results;

        $total = static::getTotalCount($baseQuery);

        return static::formatPaginationResult(
            $items,
            $total,
            $page,
            $perPage,
            $column,
            $direction,
            $search
        );
    }

    private static function applySearch(QueryBuilderInterface $query, string $search): void
    {
        $searchTerms = array_map(fn($field) => "$field LIKE :search", static::$searchableFields);
        $query->whereRaw('(' . implode(' OR ', $searchTerms) . ')', [
            'search' => "%$search%"
        ]);
    }

    private static function getTotalCount(QueryBuilderInterface $baseQuery): int
    {
        $countQuery = clone $baseQuery;
        return $countQuery
            ->select(static::getModelPrimaryKey())
            ->count();
    }

    protected static function getModelPrimaryKey(): string
    {
        return self::getPrimaryKey();
    }

    private static function formatPaginationResult(
        array  $items,
        int    $total,
        int    $page,
        int    $perPage,
        string $column,
        string $direction,
        string $search
    ): array
    {
        $totalPages = (int)ceil($total / $perPage);

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'links' => static::getPaginationLinks(
                    $page,
                    $perPage,
                    $totalPages,
                    $column,
                    $direction,
                    $search
                )
            ]
        ];
    }

    private static function getPaginationLinks(
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
