<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\ProductDTO;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Session\SessionInterface;

#[Service]
final class ProductService
{
    /** @var ProductDTO[] */
    private array $items = [];

    public function __construct(private SessionInterface $session)
    {
        if (!$this->session->has('_demo_products')) {
            // seed
            $this->items = [
                new ProductDTO(1, 'Keyboard', 59.99),
                new ProductDTO(2, 'Mouse', 29.90),
                new ProductDTO(3, 'Monitor', 199.50),
                new ProductDTO(4, 'USB-C Cable', 9.99),
                new ProductDTO(5, 'Desk Lamp', 24.00),
                new ProductDTO(6, 'Laptop Stand', 38.00),
                new ProductDTO(7, 'Headset', 89.00),
                new ProductDTO(8, 'Webcam', 79.00),
                new ProductDTO(9, 'Microphone', 119.00),
                new ProductDTO(10, 'HDMI Switch', 34.50),
                new ProductDTO(11, 'SSD 1TB', 99.00),
            ];
        }
    }

    /** @return array{items: list<array>, total:int} */
    public function list(string $q, string $sort, string $dir, int $page, int $perPage): array
    {
        $data = array_map(fn (ProductDTO $p) => $p->toArray(), $this->items);

        if ($q !== '') {
            $qq = mb_strtolower($q);
            $data = array_values(array_filter(
                $data,
                fn ($p) =>
                str_contains(mb_strtolower($p['name']), $qq)
            ));
        }

        usort($data, function ($a, $b) use ($sort, $dir) {
            $va = $a[$sort] ?? null;
            $vb = $b[$sort] ?? null;
            if ($va == $vb) {
                return 0;
            }
            $cmp = $va <=> $vb;
            return $dir === 'desc' ? -$cmp : $cmp;
        });

        $total = count($data);
        $page  = max(1, $page);
        $start = ($page - 1) * $perPage;
        $items = array_slice($data, $start, $perPage);

        return ['items' => array_values($items), 'total' => $total];
    }

    public function find(int $id): ?ProductDTO
    {
        foreach ($this->items as $p) {
            if ($p->id === $id) {
                return $p;
            }
        }
        return null;
    }

    public function save(ProductDTO $draft): void
    {
        foreach ($this->items as $i => $p) {
            if ($p->id === $draft->id) {
                $this->items[$i] = $draft;
                return;
            }
        }
        $this->items[] = $draft;
    }

    public function delete(int $id): void
    {
        $this->items = array_values(array_filter($this->items, fn ($p) => $p->id !== $id));
    }
}
