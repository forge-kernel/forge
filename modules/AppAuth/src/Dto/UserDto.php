<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Dto;

use Forge\Core\Dto\Attributes\Sanitize;
use Forge\Core\Dto\BaseDto;
use Forge\Traits\DTOHelper;
use Forge\Traits\HasMetadataToJson;

#[Sanitize(properties: ['password'])]
final class UserDto extends BaseDto
{
    use DTOHelper;
    use HasMetadataToJson;

    public int $id;
    public string $identifier;
    public string $email;
    public ?string $password;
    public ?\DateTimeImmutable $created_at;
    public ?\DateTimeImmutable $updated_at;
    public ?\DateTimeImmutable $deleted_at;
    public ?UserMetadataDto $metadata;
    public ?ProfileDto $profile;

    public function __construct(
        array $data
    ) {
        $this->id = $data['id'] ?? null;
        $this->identifier = $data['identifier'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->created_at = isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null;
        $this->updated_at = isset($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null;
        $this->deleted_at = isset($data['deleted_at']) ? new \DateTimeImmutable($data['deleted_at']) : null;

        $this->profile = null;
    }

    public static function getExampleUser(): array
    {
        return
            [
                'identifier' => 'example',
                'email' => 'test@example.com',
                'password' => password_hash('test1234', PASSWORD_BCRYPT),
                'status' => 'active',
                'metadata' => json_encode([]),
            ]
        ;
    }
}
