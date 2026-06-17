<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

use App\Modules\ForgeRouter\Http\UploadedFile;

final class Request
{
    private array $headers;
    private string $uri;
    private array $attributes = [];
    private ?string $parsedPath = null;
    private ?array $jsonData = null;
    private array $rawCookies = [];
    private array $cookieCache = [];

    /**
     * Constructs a new Request instance.
     *
     * @param array $queryParams The query parameters from the request
     * @param array $postData The POST data from the request
     * @param array $serverParams The $_SERVER superglobal array
     * @param string $requestMethod The HTTP request method (e.g., GET, POST)
     * @param array $cookies The cookies from the request
     * @param string|null $query The query parameter value (optional)
     */
    public function __construct(
        public array $queryParams,
        public array $postData,
        public array $serverParams,
        public string $requestMethod,
        public array $cookies,
        private ?string $query = null,
    ) {
        $this->headers = $this->parseHeadersFromServerParams($serverParams);
        $this->rawCookies = $cookies;
        $this->query = $queryParams["query"] ?? null;
        $rawUri = $serverParams["REQUEST_URI"] ?? "/";
        $parsed = parse_url($rawUri, PHP_URL_PATH);
        $this->parsedPath = ($parsed !== false && $parsed !== null) ? $parsed : "/";
    }

    /**
     * Gets the HTTP request method.
     *
     * @return string The HTTP request method (e.g., GET, POST, PUT, DELETE)
     */
    public function getMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Creates a Request instance from the global server variables.
     *
     * @return self A new Request instance populated from global variables
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $postData = $_POST;
        $cookies = $_COOKIE;

        if ($method === "POST") {
            if (isset($_POST["_method"])) {
                $spoofedMethod = strtoupper($_POST["_method"]);
                if (in_array($spoofedMethod, ["PUT", "PATCH", "DELETE"])) {
                    $method = $spoofedMethod;
                }
            }

            if (
                isset($_SERVER["CONTENT_TYPE"]) &&
                $_SERVER["CONTENT_TYPE"] === "application/json"
            ) {
                $rawBody = file_get_contents("php://input");
                $jsonData = json_decode($rawBody, true);
                if (is_array($jsonData)) {
                    $postData = array_merge($postData, $jsonData);
                }
            }
        }

        return new self($_GET, $postData, $_SERVER, $method, $cookies);
    }

    /**
     * Checks if the request is secure (using HTTPS).
     *
     * @return bool True if the request is secure, false otherwise
     */
    public function isSecure(): bool
    {
        $https = $this->serverParams["HTTPS"] ?? "";

        if (!empty($https) && strtolower($https) !== "off") {
            return true;
        }
        if ($this->getHeader("x-forwarded-proto") === "https") {
            return true;
        }

        if (($_SERVER["SERVER_PORT"] ?? null) == 443) {
            return true;
        }

        return false;
    }

    public function cookies(): array
    {
        if (!empty($this->cookieCache)) {
            return $this->cookieCache;
        }

        $cookies = [];
        foreach ($this->rawCookies as $name => $value) {
            $cookies[$name] = new Cookie($name, $value);
        }
        $this->cookieCache = $cookies;
        return $cookies;
    }

    public function cookie(string $name): ?Cookie
    {
        if (isset($this->cookieCache[$name])) {
            return $this->cookieCache[$name];
        }

        if (!isset($this->rawCookies[$name])) {
            return null;
        }

        $cookie = new Cookie($name, $this->rawCookies[$name]);
        $this->cookieCache[$name] = $cookie;
        return $cookie;
    }

    public function hasCookie(string $name): bool
    {
        return isset($this->rawCookies[$name]);
    }

    /**
     * Gets the URI path from the request.
     *
     * @return string The path portion of the request URI
     */
    public function getUri(): string
    {
        return $this->parsedPath;
    }

    /**
     * Checks if a request header exists.
     * Header name is case-insensitive.
     *
     * @param string $name The name of the header to check
     * @return bool True if the header exists, false otherwise
     */
    public function hasHeader(string $name): bool
    {
        $normalizedName = strtolower($name);
        return isset($this->headers[$normalizedName]);
    }

    /**
     * Gets the path portion of the request URI, with a leading slash.
     *
     * @return string The path portion of the request URI
     */
    public function getPath(): string
    {
        return "/" . ltrim($this->parsedPath, "/");
    }

    /**
     * Gets the scheme (http or https) of the request (proxy-aware).
     *
     * @return string The scheme of the request (http or https)
     */
    public function getScheme(): string
    {
        $forwardedProto =
            $this->getHeader("X-Forwarded-Proto") ??
            $this->getHeader("X-Real-Scheme");

        if ($forwardedProto) {
            return strtolower($forwardedProto);
        }

        $https = $_SERVER["HTTPS"] ?? "";
        if (!empty($https) && strtolower($https) !== "off") {
            return "https";
        }
        if (($_SERVER["SERVER_PORT"] ?? null) == 443) {
            return "https";
        }
        return "http";
    }

    /**
     * Gets the client IP address.
     *
     * @return string The client IP address, or '0.0.0.0' if not found
     */
    public function getClientIp(): string
    {
        $ip = $this->serverParams["REMOTE_ADDR"] ?? "0.0.0.0";

        if (isset($this->serverParams["HTTP_X_FORWARDED_FOR"])) {
            $forwardedIps = explode(
                ",",
                $this->serverParams["HTTP_X_FORWARDED_FOR"],
            );
            $ip = trim(end($forwardedIps));
        } elseif (isset($this->serverParams["HTTP_X_REAL_IP"])) {
            $ip = $this->serverParams["HTTP_X_REAL_IP"];
        } elseif (isset($this->serverParams["HTTP_CLIENT_IP"])) {
            $ip = $this->serverParams["HTTP_CLIENT_IP"];
        }

        return $this->validateIp($ip) ? $ip : "0.0.0.0";
    }

    /**
     * Validates if a string is a valid IP address.
     *
     * @param string $ip The IP address to validate
     * @return bool True if the IP is valid, false otherwise
     */
    private function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Gets all request headers as an associative array.
     * Header names are normalized to lowercase.
     *
     * @return array<string, string> An associative array of headers with lowercase keys
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets a request header value (case-insensitive).
     *
     * @param string $name The name of the header to retrieve
     * @param string|null $default The default value to return if not found
     * @return string|null The header value or null if not found
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalizedName = strtolower($name);
        return $this->headers[$normalizedName] ?? $default;
    }

    /**
     * Parses headers from the $_SERVER array.
     * Normalizes header names to lowercase and removes 'HTTP_' prefix.
     *
     * @param array $serverParams The $_SERVER array
     * @return array<string, string> An associative array of headers with lowercase keys
     */
    private function parseHeadersFromServerParams(array $serverParams): array
    {
        $headers = [];
        foreach ($serverParams as $key => $value) {
            if (str_starts_with($key, "HTTP_")) {
                $name = strtolower(str_replace("HTTP_", "", $key));
                $name = str_replace("_", "-", $name);
                $headers[$name] = $value;
            } elseif ($key === "CONTENT_TYPE") {
                $headers["content-type"] = $value;
            } elseif ($key === "CONTENT_LENGTH") {
                $headers["content-length"] = $value;
            }
        }
        return $headers;
    }

    /**
     * Gets the full URL of the request (scheme://host/path).
     *
     * @return string The full URL of the request
     */
    public function getUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $uri = $this->getUri();
        return "$scheme://$host$uri";
    }

    /**
     * Gets the host of the request (proxy-aware).
     *
     * @return string The host of the request
     */
    public function getHost(): string
    {
        $forwardedHost =
            $this->getHeader("X-Forwarded-Host") ??
            ($this->getHeader("X-Real-IP") ??
                $this->getHeader("X-Forwarded-Server"));

        if ($forwardedHost) {
            return $forwardedHost;
        }

        return $this->serverParams["HTTP_HOST"] ?? "localhost";
    }

    /**
     * Gets the query parameters from the request.
     *
     * @return array The query parameters as an associative array
     */
    public function getQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Gets all available request data (query parameters and POST data).
     *
     * @return array<string, mixed> An associative array of all request data
     */
    public function all(): array
    {
        return array_merge($this->getQuery(), $this->postData);
    }

    /**
     * Retrieves a value from the request data.
     * Checks POST data first, then query parameters.
     *
     * @param string $key The name of the value to retrieve
     * @param mixed $default The default value to return if not found
     * @return mixed The value of the requested key or $default
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->postData)) {
            return $this->postData[$key];
        }

        if (isset($this->queryParams[$key])) {
            return $this->queryParams[$key];
        }

        return $default;
    }

    /**
     * Gets a specific query parameter from the request.
     *
     * @param string $key The name of the query parameter
     * @param string|null $default The default value to return if the parameter is not present
     * @return string|null The value of the query parameter or the default value
     */
    public function query(string $key, ?string $default = null): ?string
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Checks if a file exists in the uploaded files array.
     *
     * @param string $key The name of the file to check
     * @return bool True if the file exists and is valid, false otherwise
     */
    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]["error"] === UPLOAD_ERR_OK;
    }

    /**
     * Gets an uploaded file by key.
     *
     * @param string $key The name of the uploaded file
     * @return \App\Modules\ForgeRouter\Http\UploadedFile|null The UploadedFile object or null if not found
     */
    public function getFile(string $key): ?UploadedFile
    {
        if ($this->hasFile($key)) {
            return new UploadedFile($_FILES[$key]);
        }
        return null;
    }

    /**
     * Sets an attribute on the request.
     *
     * @param string $key The name of the attribute
     * @param mixed $value The value to assign to the attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Gets an attribute from the request.
     *
     * @param string $key The name of the attribute
     * @param mixed $default The default value to return if the attribute is not found
     * @return mixed The value of the attribute or $default
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Gets JSON data from the request body.
     * Caches the parsed data for efficiency.
     *
     * @param string|null $key The key to retrieve from the JSON data (optional)
     * @param mixed $default The default value to return if the key is not found
     * @return mixed The JSON data or the value at the specified key or $default
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonData === null) {
            $rawBody = file_get_contents("php://input");
            $decoded = json_decode($rawBody, true);
            $this->jsonData = is_array($decoded) ? $decoded : [];
        }

        return $key === null ? $this->jsonData : ($this->jsonData[$key] ?? $default);
    }
}
