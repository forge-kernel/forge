<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Traits;


use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use DateTimeImmutable;

trait HasTimeStamps
{
    #[Column(cast: Cast::DATETIME)]
    public ?DateTimeImmutable $created_at = null;

    #[Column(cast: Cast::DATETIME)]
    public ?DateTimeImmutable $updated_at = null;
}
