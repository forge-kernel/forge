<?php

declare(strict_types=1);

namespace App\Dto;

final class ProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $price
    ) {
    }

    public static function fromArray(array $a): self
    {
        return new self(
            (int)($a['id'] ?? 0),
            (string)($a['name'] ?? ''),
            (float)($a['price'] ?? 0.0),
        );
    }

    public function toArray(): array
    {
        return ['id'=>$this->id, 'name'=>$this->name, 'price'=>$this->price];
    }
}
