<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__ . '/../');

$envPath = BASE_PATH . '/.env';
$appEnv = 'production';

if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'APP_ENV=') === 0) {
            $appEnv = strtolower(trim(substr($line, 8)));
            break;
        }
    }
}

if (!in_array($appEnv, ['dev', 'local', 'development'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed in this environment']);
    exit;
}

$cacheFile = BASE_PATH . '/storage/framework/cache/tailwind-watch.json';
if (!is_file($cacheFile)) {
    echo json_encode(['mtime' => 0]);
    exit;
}

$data = json_decode(file_get_contents($cacheFile), true);
echo json_encode(['mtime' => $data['mtime'] ?? 0]);
