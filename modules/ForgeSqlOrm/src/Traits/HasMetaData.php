<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Traits;

use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Values\Cast;

trait HasMetaData
{
    #[Column(cast: Cast::JSON)]
    public ?array $metadata = null;
}
