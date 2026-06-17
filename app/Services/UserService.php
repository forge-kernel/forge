<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\AppAuth\Models\User;
use App\Modules\ForgeSqlOrm\ORM\Model;
use Forge\Core\Cache\Attributes\Cache;
use Forge\Core\DI\Attributes\Service;
use Forge\Traits\CacheLifecycleHooks;

#[Service]
class UserService
{
    use CacheLifecycleHooks;

    //    #[Cache(
//        key: 'user->{id}',
//        ttl: 120,
//        stale: 60,
//        onSave:[self::class, 'onCacheSave'],
//        onHit:[self::class, 'onCacheHit'],
//    )]
    public static function onCacheSave($instance, $args, $key, $data): void
    {
        echo "Custom cache save logic for user {$data->id}\n";
    }

    public function findUser(int $id): ?Model
    {
        return User::with('profiles')->id($id)->first();
    }

    public function all(): array
    {
        return [];
    }
}
