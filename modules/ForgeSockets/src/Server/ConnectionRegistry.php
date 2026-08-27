<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

/**
 * The live connection set, keyed by connection id. O(1) add/remove; iteration
 * order is insertion order so broadcasts are stable.
 */
final class ConnectionRegistry
{
    /** @var array<int, Connection> */
    private array $connections = [];

    public function add(Connection $connection): void
    {
        $this->connections[$connection->id()] = $connection;
    }

    public function remove(int $id): void
    {
        unset($this->connections[$id]);
    }

    public function get(int $id): ?Connection
    {
        return $this->connections[$id] ?? null;
    }

    /**
     * @return list<Connection>
     */
    public function all(): array
    {
        return array_values($this->connections);
    }

    public function count(): int
    {
        return count($this->connections);
    }
}