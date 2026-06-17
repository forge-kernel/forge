# Configuration Guide

Complete reference for configuring ForgePackageManager registries.

## Configuration Methods

### Method 1: Config File

Create `config/source_list.php`:

```php
<?php

return [
    'registry' => [
        // Your registry configurations here
    ],
    'cache_ttl' => 3600
];
```

### Method 2: Environment Variables

Set registry configurations via `.env` file and reference them in config:

```php
'personal_token' => env('GITHUB_TOKEN')
```

## Registry Configuration Structure

Each registry entry requires:

- `name` (string) - Unique identifier for the registry
- `type` (string) - Source type: `git`, `sftp`, `ftp`, `http`, `local`, `network`
- `private` (bool) - Public or private registry

### Git Registry

```php
[
    'name' => 'my-git-registry',
    'type' => 'git',
    'url' => 'https://github.com/user/repo',
    'branch' => 'main',
    'private' => false,
    'personal_token' => env('GITHUB_TOKEN')
]
```

### SFTP Registry

```php
[
    'name' => 'my-sftp-registry',
    'type' => 'sftp',
    'host' => 'example.com',
    'port' => 22,
    'username' => env('SFTP_USER'),
    'key_path' => env('SFTP_KEY_PATH'),
    'base_path' => '/modules'
]
```

### FTP Registry

```php
[
    'name' => 'my-ftp-registry',
    'type' => 'ftp',
    'host' => env('FTP_HOST'),
    'port' => 21,
    'username' => env('FTP_USER'),
    'password' => env('FTP_PASS'),
    'base_path' => '/modules',
    'passive' => true,
    'ssl' => false
]
```

### HTTP Registry

```php
[
    'name' => 'my-http-registry',
    'type' => 'http',
    'base_url' => 'https://example.com/modules',
    'username' => env('HTTP_USER'),
    'password' => env('HTTP_PASS')
]
```

### Local Registry

```php
[
    'name' => 'my-local-registry',
    'type' => 'local',
    'path' => '/path/to/modules-registry'
]
```

### Network Registry

```php
[
    'name' => 'my-network-registry',
    'type' => 'network',
    'path' => '\\\\server\\share\\modules'
]
```

## Registry Priority

When multiple registries are configured, the package manager searches them in order. The first registry containing the requested module is used.

## Cache Configuration

Control cache behavior with `cache_ttl` (time-to-live in seconds):

```php
'cache_ttl' => 3600  // Cache for 1 hour
```

## Security Best Practices

1. **Never commit credentials** - Use environment variables
2. **Use tokens with minimal permissions** - Limit scope of access tokens
3. **Validate SSL certificates** - HTTPS sources validate certificates by default
4. **Secure SSH keys** - Set proper permissions (600) on private keys
5. **Path validation** - Local sources validate paths to prevent directory traversal

## Environment Variables

Common environment variables:

- `GITHUB_TOKEN` - GitHub personal access token
- `GITLAB_TOKEN` - GitLab personal access token
- `SFTP_USER` - SFTP username
- `SFTP_KEY_PATH` - Path to SSH private key
- `FTP_HOST` - FTP server hostname
- `FTP_USER` - FTP username
- `FTP_PASS` - FTP password
- `HTTP_USER` - HTTP basic auth username
- `HTTP_PASS` - HTTP basic auth password

