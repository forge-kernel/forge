<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Traits;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use DateTimeImmutable;

trait SoftDeletes
{
    #[Column(cast: Cast::DATETIME)]
    public ?DateTimeImmutable $deleted_at = null;
}
