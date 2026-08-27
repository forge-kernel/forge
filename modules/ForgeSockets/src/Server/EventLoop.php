<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

/**
 * A small non-blocking event loop over sockets and timers, built on
 * stream_select (present in every PHP build; stream_poll is not available on
 * this platform's runtime).
 *
 * Read interest is steady per connection; write interest is toggled on by the
 * server only when a connection has queued bytes (backpressure-driven). The
 * loop never blocks longer than the nearest timer deadline, so heartbeats and
 * game ticks always fire on schedule.
 */
final class EventLoop
{
    /** @var array<int, resource> keyed by (int) stream */
    private array $streams = [];

    /** @var array<int, \Closure(resource): void> */
    private array $readCbs = [];

    /** @var array<int, \Closure(resource): void> */
    private array $writeCbs = [];

    /** @var array<int, bool> whether a registered write callback is active */
    private array $writeActive = [];

    /** @var list<array{when: float, id: int, cb: \Closure(): void}> */
    private array $timers = [];

    private int $timerSeq = 0;

    private bool $running = false;

    /**
     * @param resource $stream
     */
    public function addRead($stream, \Closure $cb): void
    {
        $id = (int) $stream;
        $this->streams[$id] = $stream;
        $this->readCbs[$id] = $cb;
    }

    /**
     * @param resource $stream
     */
    public function addWrite($stream, \Closure $cb): void
    {
        $id = (int) $stream;
        $this->streams[$id] = $stream;
        $this->writeCbs[$id] = $cb;
        $this->writeActive[$id] = false;
    }

    /**
     * Toggle whether the registered write callback participates in the poll.
     *
     * @param resource $stream
     */
    public function setWriteInterest($stream, bool $active): void
    {
        $id = (int) $stream;
        $this->writeActive[$id] = $active;
        if ($active) {
            $this->streams[$id] = $stream;
        }
    }

    /**
     * @param resource $stream
     */
    public function remove($stream): void
    {
        $id = (int) $stream;
        unset($this->streams[$id], $this->readCbs[$id], $this->writeCbs[$id], $this->writeActive[$id]);
    }

    public function addTimer(float $seconds, \Closure $cb): int
    {
        $id = ++$this->timerSeq;
        $this->timers[] = ['when' => microtime(true) + $seconds, 'id' => $id, 'cb' => $cb];

        return $id;
    }

    public function cancelTimer(int $id): void
    {
        foreach ($this->timers as $i => $timer) {
            if ($timer['id'] === $id) {
                unset($this->timers[$i]);
            }
        }
        $this->timers = array_values($this->timers);
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function run(?callable $shouldStop = null): void
    {
        $this->running = true;
        $shouldStop ??= static fn (): bool => false;

        while ($this->running && !$shouldStop()) {
            $this->tick();
        }
    }

    private function tick(): void
    {
        $now = microtime(true);

        $read = [];
        $write = [];
        foreach ($this->streams as $id => $stream) {
            if (isset($this->readCbs[$id])) {
                $read[] = $stream;
            }
            if (isset($this->writeCbs[$id]) && ($this->writeActive[$id] ?? false)) {
                $write[] = $stream;
            }
        }

        [$seconds, $micros] = $this->selectTimeout($now);

        if ($read !== [] || $write !== []) {
            $selectedRead = $read;
            $selectedWrite = $write;
            $except = null;
            $result = @stream_select($selectedRead, $selectedWrite, $except, $seconds, $micros);

            if ($result === false) {
                return; // interrupted (signal) — the next tick re-evaluates
            }

            foreach ($selectedRead as $stream) {
                $cb = $this->readCbs[(int) $stream] ?? null;
                if ($cb !== null) {
                    $cb($stream);
                }
            }

            foreach ($selectedWrite as $stream) {
                $cb = $this->writeCbs[(int) $stream] ?? null;
                if ($cb !== null) {
                    $cb($stream);
                }
            }
        } else {
            usleep(max(1, $micros));
        }

        $this->runTimers($now);
    }

    private function runTimers(float $now): void
    {
        foreach ($this->timers as $i => $timer) {
            if ($timer['when'] <= $now) {
                unset($this->timers[$i]);
                $timer['cb']();
            }
        }
        $this->timers = array_values($this->timers);
    }

    /**
     * The longest select wait that still meets the nearest timer deadline.
     *
     * @return array{0: int, 1: int} seconds + microseconds
     */
    private function selectTimeout(float $now): array
    {
        $next = null;
        foreach ($this->timers as $timer) {
            if ($next === null || $timer['when'] < $next) {
                $next = $timer['when'];
            }
        }

        if ($next === null) {
            return [0, 1000000];
        }

        $micros = (int) round(max(0.0, ($next - $now) * 1_000_000));
        $micros = min($micros, 1000000);

        return [0, max(1, $micros)];
    }
}