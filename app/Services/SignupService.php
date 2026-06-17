<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SignupDTO;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Session\SessionInterface;

#[Service]
final class SignupService
{
    private const KEY = '_demo_signups';

    public function __construct(private SessionInterface $session)
    {
        if (!$this->session->has(self::KEY)) {
            $this->session->set(self::KEY, []);
        }
    }

    /** @return list<array> */
    public function all(): array
    {
        return $this->session->get(self::KEY, []);
    }

    public function register(SignupDTO $dto): int
    {
        $rows = $this->all();
        $id = random_int(1000, 999999);
        $rows[] = ['id'=>$id] + $dto->toArray();
        $this->session->set(self::KEY, $rows);
        return $id;
    }
}
