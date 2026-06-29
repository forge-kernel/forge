<?php

declare(strict_types=1);

namespace Modules\AppAuth\Models;

use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Attributes\Table;
use Modules\ForgeSqlOrm\ORM\Model;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use Modules\ForgeSqlOrm\Traits\{HasMetaData, HasTimeStamps};

#[Table("profiles")]
class Profile extends Model
{
    use HasTimeStamps;
    use HasMetaData;

    #[Column(primary: true, cast: Cast::INT)]
    public int $id;

    #[Column(cast: Cast::INT)]
    public int $user_id;

    #[Column(cast: Cast::STRING)]
    public string $first_name;

    #[Column(cast: Cast::STRING)]
    public ?string $last_name;

    #[Column(cast: Cast::STRING)]
    public ?string $avatar;

    #[Column(cast: Cast::STRING)]
    public ?string $email;

    #[Column(cast: Cast::STRING)]
    public ?string $phone;

    #[Column(cast: Cast::STRING)]
    public ?string $pending_email;

    #[Column(cast: Cast::STRING)]
    public ?string $pending_phone;

    #[Column(cast: Cast::STRING)]
    public ?string $email_confirmed;
}
