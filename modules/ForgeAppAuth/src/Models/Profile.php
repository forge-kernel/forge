<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Models;

use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Attributes\Table;
use Modules\ForgeSqlOrm\ORM\Model;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use Modules\ForgeSqlOrm\Traits\HasTimeStamps;

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
