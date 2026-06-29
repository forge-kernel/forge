<?php
declare(strict_types=1);

namespace Modules\ForgeRouter\Http;

final class Session
{
    private bool $started = false;

    /**
     * Start the session.
     *
     * @param array<string,mixed> $options
     * @throws \Exception
     */
    public function start(array $options = []): void
    {
        if ($this->started) {
            return;
        }

        $defaults = [
        ];

        $sessionOptions = array_merge($defaults, $options);

        $attempt = 0;
        $maxAttempts = 3;

        while ($attempt < $maxAttempts) {
            if (@session_start($sessionOptions)) {
                $this->started = true;
                return;
            }
            $attempt++;
        }

        throw new \Exception("Failed to start session after {$maxAttempts} attempts.");
    }

    /**
     * Get a session value.
     *
     * @param string $key The session key.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key The session key.
     * @param mixed $value The value to set.
     * @return void
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Remove a session value.
     *
     * @param string $key The session key.
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if a session key exist
     *
     * @param string $key The session key.
     * @return void
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $deleteOldSession Whether to delete the old session file (default: true).
     * @return void
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Destroy the session (logout).
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->started) {
            session_unset();
            session_destroy();
            $this->started = false;
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', 0, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
        }
    }

    /**
     * Check if session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Set a flash message. There messages are stored in the session and are meatn to display once
     * the automatic removed on the next request
     *
     * @param string $key Flash message key, example suscess, error, warning etc
     * @param mixed $message Message value
     * @return void
     */
    public function setFlash(string $key, $message): void
    {
        $_SESSION['_flash_messages'][$key] = $message;
    }

    /**
     * Get a flash message and clear it from the session, if message dont exists return null
     *
     * @param string $key flash message key
     * @return mixed|null flash message value or null if not found
     */
    public function getFlash(string $key): mixed
    {
        $messages = $_SESSION['_flash_messages'] ?? [];
        $message = $messages[$key] ?? null;
        if ($message !== null) {
            unset($_SESSION['_flash_messages'][$key]);
        }
        return $message;
    }

    /**
     * Get all flash messages and clear them from the session.
     *
     * @return array<string, string> An associative array of flash messages, where keys are types and values are messages.
     */
    public function getFlashMessages(): array
    {
        $messages = $_SESSION['_flash_messages'] ?? [];
        unset($_SESSION['_flash_messages']);
        return $messages;
    }

    /**
     * Check if flash message exits
     *
     * @param string $key
     * @return bool
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash_messages'][$key]);
    }
}