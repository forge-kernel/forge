<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Models;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\{HasMetaData, HasTimeStamps};

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
