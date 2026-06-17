<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use LogicException;
use ReflectionException;

final class ModelQuery
{
  private QueryBuilderInterface $builder;
  private string $model;
  private array $withRelations = [];

  public function __construct(string $model)
  {
    if (!class_exists($model)) {
      throw new LogicException("Model class '{$model}' does not exist.");
    }

    if (!is_subclass_of($model, Model::class)) {
      throw new LogicException("{$model} must extend base Model.");
    }

    $this->model = $model;
    $this->builder = Container::getInstance()->get(QueryBuilderInterface::class)
      ->table($model::table());
  }

  /** @return array<Model> */
  public function get(): array
  {
    $results = array_map(
      $this->model::fromRow(...),
      $this->builder->get()
    );

    if (!empty($this->withRelations)) {
      $this->loadRelations($results);
    }

    return $results;
  }

  private function loadRelations(array $models): void
  {
    if (empty($models)) {
      return;
    }

    $loader = new RelationLoader(...$models);
    $loader->load(...$this->withRelations);
  }

  public function id(int|string $id): self
  {
    $pk = $this->model::primaryProperty()->getName();
    return $this->where($pk, '=', $id);
  }

  public function where(string $column, mixed $operator = '=', mixed $value = null): self
  {
    $this->builder = $this->builder->where($column, $operator, $value);
    return $this;
  }

  public function whereNull(string $column): self
  {
    $this->builder = $this->builder->whereNull($column);
    return $this;
  }

  /**
   * @throws ReflectionException
   */
  public function onlyTrashed(): self
  {
    $col = $this->model::softDeleteColumn();
    if ($col === null) {
      throw new LogicException('Model is not soft-deletable');
    }
    $this->builder = $this->builder->whereNotNull($col);
    return $this;
  }

  public function first(): ?Model
  {
    $row = $this->builder->first();
    if ($row === null) {
      return null;
    }

    $model = $this->model::fromRow($row);

    if (!empty($this->withRelations)) {
      (new RelationLoader($model))->load(...$this->withRelations);
    }

    return $model;
  }

  public function insert(array $data): int|false
  {
    return $this->builder->insert($data);
  }

  public function forceDelete(): int
  {
    return $this->builder->delete();
  }

  /**
   * @throws ReflectionException
   */
  public function softDelete(): int
  {
    $col = $this->model::softDeleteColumn()
      ?? throw new LogicException('Model is not soft-deletable');

    return $this->builder->update([$col => date('Y-m-d H:i:s')]);
  }

  public function update(array $data): int
  {
    return $this->builder->update($data);
  }

  public function whereIn(string $column, array $values): self
  {
    $this->builder = $this->builder->whereIn($column, $values);
    return $this;
  }

  public function with(string ...$relations): self
  {
    $this->withRelations = array_merge($this->withRelations, $relations);
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->builder = $this->builder->orderBy($column, $direction);
    return $this;
  }

  public function limit(int $limit): self
  {
    $this->builder = $this->builder->limit($limit);
    return $this;
  }

  public function offset(int $offset): self
  {
    $this->builder = $this->builder->offset($offset);
    return $this;
  }

  public function whereNotNull(string $column): self
  {
    $this->builder = $this->builder->whereNotNull($column);
    return $this;
  }

  /**
   * Paginate the query results using offset-based pagination
   *
   * @param int $perPage Number of items per page
   * @param int $page Current page number (default: 1)
   * @param array $options Additional options (filters, search, searchFields, baseUrl, queryParams, sort, direction)
   * @return Paginator
   */
  public function paginate(int $perPage = 15, int $page = 1, array $options = []): Paginator
  {
    $page = max(1, $page);
    $perPage = max(1, $perPage);
    $offset = ($page - 1) * $perPage;

    $search = $options['search'] ?? null;
    $searchFields = $options['searchFields'] ?? [];
    if ($search && !empty($searchFields)) {
      $this->applySearch($search, $searchFields);
    }

    $filters = $options['filters'] ?? [];
    if (!empty($filters)) {
      $this->applyFilters($filters);
    }

    $total = $this->getTotalCount();

    $sortColumn = $options['sort'] ?? $options['column'] ?? 'created_at';
    $sortDirection = $options['direction'] ?? 'ASC';
    $this->orderBy($sortColumn, $sortDirection);

    $items = $this->builder
      ->limit($perPage)
      ->offset($offset)
      ->get();

    $models = array_map(
      $this->model::fromRow(...),
      $items
    );

    if (!empty($this->withRelations)) {
      $this->loadRelations($models);
    }

    $itemsArray = array_map(fn($model) => $model->toArray(), $models);

    return new Paginator(
      items: $itemsArray,
      total: $total,
      perPage: $perPage,
      currentPage: $page,
      cursor: null,
      sortColumn: $sortColumn,
      sortDirection: $sortDirection,
      filters: $filters,
      search: $search,
      searchFields: $searchFields,
      baseUrl: $options['baseUrl'] ?? '',
      queryParams: $options['queryParams'] ?? []
    );
  }

  private function getTotalCount(): int
  {
    return (int) $this->builder->count();
  }

  private function applySearch(string $search, array $searchFields): void
  {
    if (empty($searchFields) || empty($search)) {
      return;
    }

    $searchTerms = [];
    $searchParams = [];
    $paramIndex = 0;

    foreach ($searchFields as $field) {
      $paramKey = "search_{$paramIndex}";
      $searchTerms[] = "{$field} LIKE :{$paramKey}";
      $searchParams[$paramKey] = "%{$search}%";
      $paramIndex++;
    }

    $searchSql = '(' . implode(' OR ', $searchTerms) . ')';
    $this->builder = $this->builder->whereRaw($searchSql, $searchParams);
  }

  private function applyFilters(array $filters): void
  {
    foreach ($filters as $column => $value) {
      if ($value !== null && $value !== '') {
        $this->builder = $this->builder->where($column, '=', $value);
      }
    }
  }
}
