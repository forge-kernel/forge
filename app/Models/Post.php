<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\HasMetaData;
use App\Modules\ForgeSqlOrm\Traits\HasTimeStamps;

#[Table("posts")]
class Post extends Model
{
    use HasTimeStamps;
    use HasMetaData;

    #[Column(primary: true, cast: Cast::STRING)]
    public int $id;

    #[Column(cast: Cast::STRING)]
    public string $title;

    #[Column(cast: Cast::STRING)]
    public string $content;
    #[Column(cast: Cast::STRING)]
    public string $tenant_id;

    public function metadata(): array
    {
        return [];
    }

}
