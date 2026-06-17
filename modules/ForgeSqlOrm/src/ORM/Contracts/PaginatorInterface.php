<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Contracts;

interface PaginatorInterface
{
  public function items(): array;
  public function total(): int;
  public function perPage(): int;
  public function currentPage(): int;
  public function lastPage(): int;
  public function hasMorePages(): bool;
  public function hasPreviousPage(): bool;
  public function previousPageUrl(): ?string;
  public function nextPageUrl(): ?string;
  public function firstPageUrl(): ?string;
  public function lastPageUrl(): ?string;
  public function url(int $page): string;
  public function links(): array;
  public function meta(): array;
  public function toArray(): array;
}
