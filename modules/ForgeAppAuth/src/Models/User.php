<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Models;

use Modules\ForgeAppAuth\Dto\UserMetadataDto;
use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Attributes\ProtectedFields;
use Modules\ForgeSqlOrm\ORM\Attributes\Table;
use Modules\ForgeSqlOrm\ORM\CanLoadRelations;
use Modules\ForgeSqlOrm\ORM\Model;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use Modules\ForgeSqlOrm\ORM\Values\Relate;
use Modules\ForgeSqlOrm\ORM\Values\Relation;
use Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use Modules\ForgeSqlOrm\Traits\HasTimeStamps;

#[Table("users")]
#[ProtectedFields(["password"])]
class User extends Model implements AuthUserInterface
{
    use HasTimeStamps;
    use CanLoadRelations;

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
