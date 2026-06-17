<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Models;

use App\Modules\AppAuth\Dto\UserMetadataDto;
use App\Modules\ForgeAuth\Contracts\AuthUserInterface;
use App\Modules\ForgeAuth\Traits\HasRoles;
use App\Modules\ForgeSqlOrm\ORM\CanLoadRelations;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\ORM\Values\Relate;
use App\Modules\ForgeSqlOrm\ORM\Values\Relation;
use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use App\Modules\ForgeSqlOrm\Traits\{HasTimeStamps};
use App\Modules\ForgeSqlOrm\ORM\Attributes\{Table, Column, ProtectedFields};
use App\Modules\ForgeSqlOrm\ORM\Model;

#[Table("users")]
#[ProtectedFields(["password"])]
class User extends Model implements AuthUserInterface
{
    use HasTimeStamps;
    use CanLoadRelations;
    use HasRoles;

    #[Column(primary: true, cast: Cast::INT)]
    public ?int $id = null;

    #[Column(cast: Cast::STRING)]
    public string $status;

    #[Column(cast: Cast::STRING)]
    public string $identifier;

    #[Column(cast: Cast::STRING)]
    public string $email;

    #[Column(cast: Cast::STRING)]
    public string $password;

    #[Column(cast: Cast::JSON)]
    public ?UserMetadataDto $metadata;

    #[Relate(RelationKind::HasOne, Profile::class, "user_id")]
    public function profile(): Relation
    {
        return self::describe(__FUNCTION__);
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
