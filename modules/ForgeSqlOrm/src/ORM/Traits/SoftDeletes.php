<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\ORM\Traits;

use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use DateTimeImmutable;

trait SoftDeletes
{
    #[Column(cast: Cast::DATETIME)]
    public ?DateTimeImmutable $deleted_at = null;
}
