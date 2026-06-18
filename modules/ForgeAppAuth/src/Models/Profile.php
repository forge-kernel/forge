<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Models;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\HasTimeStamps;

#[Table("profiles")]
class Profile extends Model
{
    use HasTimeStamps;

    #[Column(primary: true, cast: Cast::INT)]
    public int $id;

    #[Column(cast: Cast::INT)]
    public int $user_id;

    #[Column(cast: Cast::STRING)]
    public string $first_name;

    #[Column(cast: Cast::STRING, nullable: true)]
    public ?string $last_name = null;

    #[Column(cast: Cast::STRING, nullable: true)]
    public ?string $avatar = null;

    #[Column(cast: Cast::STRING, nullable: true)]
    public ?string $email = null;

    #[Column(cast: Cast::STRING, nullable: true)]
    public ?string $phone = null;
}
