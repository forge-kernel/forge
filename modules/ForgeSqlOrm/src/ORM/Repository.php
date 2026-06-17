<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

interface Repository
{
    public function create(mixed $data): Model;
    
    public function update(Model $record, mixed $data): bool;
    
    public function delete(Model|int $record): bool;
    
    public function find(int $id): ?Model;
    
    public function findBy(string $field, mixed $value): ?Model;
    
    public function findAll(): array;
    
    public function query(): ModelQuery;
}

