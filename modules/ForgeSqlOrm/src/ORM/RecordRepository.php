<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Cache\QueryCache;

abstract class RecordRepository implements Repository
{
  protected QueryCache $cache;
  protected string $modelClass;
  protected string $tableName;

  public function __construct(QueryCache $cache)
  {
    $this->cache = $cache;
    $this->modelClass = $this->getModelClass();
    $this->tableName = $this->modelClass::table();
  }

  abstract protected function getModelClass(): string;

  public function create(mixed $data): Model
  {
    $record = new ($this->modelClass)();

    foreach ($data as $key => $value) {
      if (property_exists($record, $key)) {
        $record->{$key} = $value;
      } else {
        trigger_error('Unknown property "' . $key . '" on ' . $this->modelClass, E_USER_WARNING);
      }
    }

    $record->save();
    return $record;
  }

  public function update(Model $record, mixed $data): bool
  {
    foreach ($data as $key => $value) {
      if (property_exists($record, $key)) {
        $record->{$key} = $value;
      }
    }

    $result = $record->save();

    if ($result) {
      $pk = $this->modelClass::primaryProperty()->getName();
      $id = $record->{$pk};
      $this->cache->forget($this->cache->generateKey($this->tableName, 'find', $id));
      $this->cache->invalidate($this->tableName);
    }

    return $result;
  }

  public function delete(Model|int|string $record): bool
  {
    if (is_int($record) || is_string($record)) {
      $record = $this->find($record);
      if ($record === null) {
        return false;
      }
    }

    $pk = $this->modelClass::primaryProperty()->getName();
    $id = $record->{$pk};

    $result = $record->delete() > 0;

    if ($result) {
      $this->cache->forget($this->cache->generateKey($this->tableName, 'find', $id));
      $this->cache->invalidate($this->tableName);
    }

    return $result;
  }

  public function find(int|string $id): ?Model
  {
    $key = $this->cache->generateKey($this->tableName, 'find', $id);
    $cached = $this->cache->get($key);

    if ($cached !== null) {
      return clone $cached;
    }

    $record = $this->modelClass::query()->id($id)->first();

    if ($record !== null) {
      $this->cache->set($key, clone $record);
    }
    return $record;
  }

  public function findBy(string $field, mixed $value): ?Model
  {
    $key = $this->cache->generateKey($this->tableName, 'findBy', $field, $value);
    $cached = $this->cache->get($key);

    if ($cached !== null) {
      return clone $cached;
    }

    $record = $this->modelClass::query()->where($field, '=', $value)->first();

    if ($record !== null) {
      $this->cache->set($key, clone $record);
    }

    return $record;
  }

  public function findAll(): array
  {
    $key = $this->cache->generateKey($this->tableName, 'findAll');
    $cached = $this->cache->get($key);

    if ($cached !== null) {
      return $cached;
    }

    $records = $this->modelClass::query()->get();
    $this->cache->set($key, $records, 300);

    return $records;
  }

  public function query(): ModelQuery
  {
    return $this->modelClass::query();
  }

  public function createMany(array $data): array
  {
    $records = [];
    $pk = $this->modelClass::primaryProperty()->getName();

    $builder = $this->modelClass::query()->getBuilder();
    $builder->beginTransaction();
    try {
      foreach ($data as $row) {
        $record = new ($this->modelClass)();
        foreach ($row as $key => $value) {
          if (property_exists($record, $key)) {
            $record->{$key} = $value;
          }
        }
        $record->save();
        $records[] = $record;
      }
      $builder->commit();
    } catch (\Throwable $e) {
      $builder->rollback();
      throw $e;
    }

    $this->cache->invalidate($this->tableName);

    return $records;
  }

  public function updateMany(array $records, array $data): int
  {
    $count = 0;

    $builder = $this->modelClass::query()->getBuilder();
    $builder->beginTransaction();
    try {
      foreach ($records as $record) {
        if ($this->update($record, $data)) {
          $count++;
        }
      }
      $builder->commit();
    } catch (\Throwable $e) {
      $builder->rollback();
      throw $e;
    }

    return $count;
  }

  public function deleteMany(array $ids): int
  {
    $count = 0;

    $builder = $this->modelClass::query()->getBuilder();
    $builder->beginTransaction();
    try {
      foreach ($ids as $id) {
        if ($this->delete($id)) {
          $count++;
        }
      }
      $builder->commit();
    } catch (\Throwable $e) {
      $builder->rollback();
      throw $e;
    }

    return $count;
  }

  public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
  {
    return $this->modelClass::paginate($page, $perPage, 'created_at', 'ASC', '', $options);
  }
}

