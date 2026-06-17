<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class SourceFactory
{
    public static function create(array $config): SourceInterface
    {
        $type = $config['type'] ?? 'git';
        
        $config = self::mergeEnvVars($config, $type);

        return match ($type) {
            'git' => new GitSource($config),
            'sftp' => new SftpSource($config),
            'ftp' => new FtpSource($config),
            'http' => new HttpSource($config),
            'local' => new LocalSource($config),
            'network' => new LocalNetworkSource($config),
            default => new GitSource($config),
        };
    }

    private static function mergeEnvVars(array $config, string $type): array
    {
        if ($type === 'git') {
            if (empty($config['personal_token']) && empty($config['token'])) {
                $config['personal_token'] = env('GITHUB_TOKEN') ?? env('GITLAB_TOKEN') ?? null;
            }
        }

        if ($type === 'sftp') {
            $config['username'] = $config['username'] ?? env('SFTP_USER');
            $config['password'] = $config['password'] ?? env('SFTP_PASS');
            $config['key_path'] = $config['key_path'] ?? env('SFTP_KEY_PATH');
            $config['key_passphrase'] = $config['key_passphrase'] ?? env('SFTP_KEY_PASSPHRASE');
        }

        if ($type === 'ftp') {
            $config['host'] = $config['host'] ?? env('FTP_HOST');
            $config['username'] = $config['username'] ?? env('FTP_USER');
            $config['password'] = $config['password'] ?? env('FTP_PASS');
            $config['port'] = $config['port'] ?? env('FTP_PORT', 21);
        }

        if ($type === 'http') {
            $config['username'] = $config['username'] ?? env('HTTP_USER');
            $config['password'] = $config['password'] ?? env('HTTP_PASS');
        }

        return $config;
    }
}

