# SFTP Sources

Configure SFTP-based registries using SSH2 extension for secure file transfer.

## Prerequisites

Install PHP SSH2 extension:

```bash
# Ubuntu/Debian
sudo apt-get install php-ssh2

# macOS (Homebrew)
brew install php-ssh2

# Or via PECL
pecl install ssh2
```

## Configuration

### Key-Based Authentication

```php
[
    'name' => 'sftp-key',
    'type' => 'sftp',
    'host' => 'example.com',
    'port' => 22,
    'username' => env('SFTP_USER'),
    'key_path' => env('SFTP_KEY_PATH'),
    'key_passphrase' => env('SFTP_KEY_PASSPHRASE'),
    'base_path' => '/modules'
]
```

### Password Authentication

```php
[
    'name' => 'sftp-password',
    'type' => 'sftp',
    'host' => 'example.com',
    'port' => 22,
    'username' => env('SFTP_USER'),
    'password' => env('SFTP_PASS'),
    'base_path' => '/modules'
]
```

## SSH Key Setup

### Generate SSH Key Pair

```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/forge_modules
```

### Copy Public Key to Server

```bash
ssh-copy-id -i ~/.ssh/forge_modules.pub user@example.com
```

### Set Key Permissions

```bash
chmod 600 ~/.ssh/forge_modules
chmod 644 ~/.ssh/forge_modules.pub
```

### Environment Variables

Add to `.env`:

```
SFTP_USER=username
SFTP_KEY_PATH=/path/to/private/key
SFTP_KEY_PASSPHRASE=optional-passphrase
```

## Server Requirements

Your SFTP server should have:

1. SSH/SFTP access enabled
2. Directory structure:
   ```
   /modules/
   ├── modules.json
   └── modules/
       └── module-name/
           └── version/
               └── module-name-version.zip
   ```

## Troubleshooting

### Connection Failed

- Verify SSH2 extension is installed: `php -m | grep ssh2`
- Check host and port are correct
- Verify network connectivity: `ssh user@host -p port`

### Authentication Failed

- Verify key path is absolute and accessible
- Check key permissions (should be 600)
- Ensure public key is in `~/.ssh/authorized_keys` on server
- For password auth, verify credentials

### Permission Denied

- Check user has read access to base_path
- Verify directory structure exists
- Check file permissions on server

## Security Considerations

1. **Use key-based authentication** when possible
2. **Protect private keys** with proper file permissions
3. **Use passphrases** for additional security
4. **Limit SSH access** to specific IPs if possible
5. **Use non-standard ports** to reduce attack surface

