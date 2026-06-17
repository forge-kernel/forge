<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

final class CloudflareService
{
    private const API_BASE_URL = "https://api.cloudflare.com/client/v4";

    public function __construct(private readonly string $apiToken) {}

    public function addDnsRecord(
        string $zoneId,
        string $domain,
        string $ipAddress,
        string $type = "A",
    ): bool {
        $existing = $this->findDnsRecord($zoneId, $domain, $type);
        if ($existing !== null) {
            return $this->updateDnsRecord(
                $zoneId,
                $existing["id"],
                $domain,
                $ipAddress,
                $type,
            );
        }

        return $this->createDnsRecord($zoneId, $domain, $ipAddress, $type);
    }

    public function verifyDnsRecord(
        string $zoneId,
        string $domain,
        string $ipAddress,
        string $type = "A",
    ): bool {
        $record = $this->findDnsRecord($zoneId, $domain, $type);
        if ($record === null) {
            return false;
        }

        return $record["content"] === $ipAddress;
    }

    public function deleteDnsRecords(string $domain): bool
    {
        $zoneId = $this->getZoneId($domain);
        if ($zoneId === null) {
            return false;
        }

        $response = $this->makeRequest("GET", "/zones/{$zoneId}/dns_records", [
            "name" => $domain,
        ]);

        if (!isset($response["result"])) {
            return true;
        }

        $success = true;
        foreach ($response["result"] as $record) {
            $deleteResponse = $this->makeRequest(
                "DELETE",
                "/zones/{$zoneId}/dns_records/{$record["id"]}",
            );
            if (!isset($deleteResponse["result"]["id"])) {
                $success = false;
            }
        }

        return $success;
    }

    public function getZoneId(string $domain): ?string
    {
        $rootDomain = $this->extractRootDomain($domain);
        $response = $this->makeRequest("GET", "/zones", [
            "name" => $rootDomain,
        ]);
        if (!isset($response["result"][0]["id"])) {
            return null;
        }

        return $response["result"][0]["id"];
    }

    private function extractRootDomain(string $domain): string
    {
        $parts = explode(".", $domain);
        if (count($parts) >= 2) {
            return $parts[count($parts) - 2] . "." . $parts[count($parts) - 1];
        }
        return $domain;
    }

    private function createDnsRecord(
        string $zoneId,
        string $domain,
        string $ipAddress,
        string $type,
    ): bool {
        $response = $this->makeRequest("POST", "/zones/{$zoneId}/dns_records", [
            "type" => $type,
            "name" => $domain,
            "content" => $ipAddress,
            "ttl" => 3600,
            "proxied" => false,
        ]);

        return isset($response["result"]["id"]);
    }

    private function updateDnsRecord(
        string $zoneId,
        string $recordId,
        string $domain,
        string $ipAddress,
        string $type,
    ): bool {
        $response = $this->makeRequest(
            "PUT",
            "/zones/{$zoneId}/dns_records/{$recordId}",
            [
                "type" => $type,
                "name" => $domain,
                "content" => $ipAddress,
                "ttl" => 3600,
                "proxied" => false,
            ],
        );

        return isset($response["result"]["id"]);
    }

    private function findDnsRecord(
        string $zoneId,
        string $domain,
        string $type,
    ): ?array {
        $response = $this->makeRequest("GET", "/zones/{$zoneId}/dns_records", [
            "type" => $type,
            "name" => $domain,
        ]);

        if (!isset($response["result"][0])) {
            return null;
        }

        return $response["result"][0];
    }

    private function makeRequest(
        string $method,
        string $endpoint,
        array $params = [],
    ): array {
        $url = self::API_BASE_URL . $endpoint;

        if (!empty($params) && $method === "GET") {
            $url .= "?" . http_build_query($params);
        }

        $headers = [
            "Authorization: Bearer " . $this->apiToken,
            "Content-Type: application/json",
        ];

        $options = [
            "http" => [
                "method" => $method,
                "header" => implode("\r\n", $headers),
                "timeout" => 30,
                "ignore_errors" => true,
            ],
        ];

        if (
            !empty($params) &&
            in_array($method, ["POST", "PUT", "PATCH"], true)
        ) {
            $options["http"]["content"] = json_encode($params);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException("Cloudflare API request failed");
        }

        $statusCode = $this->getHttpStatusCode($http_response_header ?? []);
        $decoded = json_decode($response, true);

        if ($statusCode >= 400) {
            $message = $decoded["errors"][0]["message"] ?? "API request failed";
            throw new \RuntimeException(
                "Cloudflare API error ({$statusCode}): {$message}",
            );
        }

        return $decoded ?? [];
    }

    private function getHttpStatusCode(array $headers): int
    {
        if (empty($headers)) {
            return 0;
        }

        preg_match("/HTTP\/\d\.\d\s+(\d+)/", $headers[0], $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}
