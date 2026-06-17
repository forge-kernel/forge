<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

/**
 * HTTP Response class for building and sending HTTP responses.
 *
 * This class provides a fluent interface for setting response content, status code,
 * headers, and cookies. It handles the HTTP response lifecycle by setting the appropriate
 * status code, headers, and cookies, then outputs the response content.
 *
 * @property-read string $content The response content to be sent
 * @property-read int $status The HTTP status code of the response
 * @property-read array $headers The headers to be sent with the response
 * @property-read array $cookies The cookies to be sent with the response
 */
class Response
{
    /**
     * @var array List of cookies to be sent with the response
     */
    private array $cookies = [];

    /**
     * Constructs a new HTTP response.
     *
     * @param string $content The response content to be sent
     * @param int $status The HTTP status code (default is 200)
     * @param array $headers Associative array of headers to send with the response
     */
    public function __construct(
        protected string $content,
        protected int $status = 200,
        protected array $headers = [],
    ) {
        //
    }

    /**
     * Sends the HTTP response.
     *
     * Sets the HTTP status code, sends headers and cookies, outputs the content,
     * and flushes any output buffers.
     *
     * @return void
     */
    public function send(): void
    {
        if (ob_get_level() === 0) {
            ob_start();
        }

        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("$name: $v", false);
                }
            } else {
                header("$name: $value");
            }
        }

        foreach ($this->cookies as $cookie) {
            setcookie($cookie->name, $cookie->value, [
                "expires" => $cookie->expires,
                "path" => $cookie->path,
                "domain" => $cookie->domain,
                "secure" => $cookie->secure,
                "httponly" => $cookie->httponly,
                "samesite" => $cookie->samesite,
            ]);
        }

        echo $this->content;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Sets the HTTP status code for the response.
     *
     * @param int $code The HTTP status code (e.g., 200, 404, 500)
     * @return self Returns the current instance for method chaining
     */
    public function setStatusCode(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Sets a single header for the response.
     *
     * @param string $key The header name (e.g., "Content-Type")
     * @param string $value The header value
     * @return self Returns the current instance for method chaining
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Sets multiple headers for the response.
     *
     * @param array $headers Associative array of header names and values
     * @return self Returns the current instance for method chaining
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Retrieves all headers set for the response.
     *
     * @return array Associative array of headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a specific header exists in the response.
     *
     * @param string $key The header name to check
     * @return bool True if the header exists, false otherwise
     */
    public function hasHeader(string $key): bool
    {
        return array_key_exists($key, $this->headers);
    }

    /**
     * Retrieves the value of a specific header.
     *
     * @param string $key The header name
     * @return string|null The header value, or null if not found
     */
    public function getHeader(string $key): string|null
    {
        if (!$key) {
            return null;
        }

        return $this->headers[$key] ?? null;
    }

    /**
     * Retrieves all cookies set for the response.
     *
     * @return array List of cookie objects
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Retrieves the response content.
     *
     * @return string The response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Retrieves the HTTP status code of the response.
     *
     * @return int The HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Sets the response content.
     *
     * @param string $content The content to set
     * @return void
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Adds a cookie to the response.
     *
     * @param Cookie $cookie The cookie object to add
     * @return self Returns the current instance for method chaining
     */
    public function setCookie(Cookie $cookie): self
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    /**
     * Adds a cookie to the response (alias for setCookie).
     *
     * @param Cookie $cookie The cookie object to add
     * @return self Returns the current instance for method chaining
     */
    public function withCookie(Cookie $cookie): self
    {
        $this->cookies[] = $cookie;
        return $this;
    }
}
