<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Models;

use Modules\ForgeSqlOrm\ORM\Values\Cast;
use Modules\ForgeSqlOrm\Traits\HasTimeStamps;
use Modules\ForgeSqlOrm\ORM\Attributes\{Table, Column};

#[Table("permissions")]
class Permission extends \Modules\ForgeSqlOrm\ORM\Model
{
    use HasTimeStamps;

    #[Column(primary: true, cast: Cast::INT)]
    public ?int $id = null;

    #[Column(cast: Cast::STRING)]
    public string $name;

    #[Column(cast: Cast::STRING)]
    public ?string $description = null;
}