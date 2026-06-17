<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Models;

use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\HasTimeStamps;
use App\Modules\ForgeSqlOrm\ORM\Attributes\{Table, Column};

#[Table("roles")]
class Role extends \App\Modules\ForgeSqlOrm\ORM\Model
{
    use HasTimeStamps;

    #[Column(primary: true, cast: Cast::INT)]
    public ?int $id = null;

    #[Column(cast: Cast::STRING)]
    public string $name;

    #[Column(cast: Cast::STRING)]
    public ?string $description = null;
}
