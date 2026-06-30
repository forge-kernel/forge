<?php
declare(strict_types=1);

namespace Modules\ForgeAdminConsole\Common;

use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Forge\Core\DI\Attributes\Injectable;

#[Injectable]
final class UserProvider
{
    public function __construct(
        private readonly UserProviderInterface $userProvider,
    ) {
    }

    public function getUsersTableData(int $page = 1, int $perPage = 10): array
    {
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'identifier', 'label' => 'Identifier'],
            ['key' => 'email', 'label' => 'Email'],
        ];

        $rows = [];
        try {
            $users = $this->userProvider->paginate($page, $perPage);
            foreach ($users->items() as $user) {
                $rows[] = $this->userToRow($user);
            }
        } catch (\Throwable) {
        }

        return ['columns' => $columns, 'rows' => $rows];
    }

    public function getUserDetails(int $id): ?array
    {
        $user = $this->userProvider->findById($id);
        if (!$user) {
            return null;
        }

        return $this->userToArray($user);
    }

    private function userToRow(array $user): array
    {
        return [
            'id' => (string) ($user['id'] ?? ''),
            'identifier' => $user['identifier'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }

    private function userToArray(array $user): array
    {
        return [
            'id' => (string) ($user['id'] ?? ''),
            'identifier' => $user['identifier'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }
}
