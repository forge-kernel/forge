# Local Sources

Configure local filesystem and network-mounted path registries.

## Configuration

### Local Filesystem

```php
[
    'name' => 'local-filesystem',
    'type' => 'local',
    'path' => '/path/to/modules-registry'
]
```

### Network Mount (Linux/macOS)

```php
[
    'name' => 'network-mount',
    'type' => 'network',
    'path' => '/mnt/modules'
]
```

### Windows UNC Path

```php
[
    'name' => 'windows-unc',
    'type' => 'network',
    'path' => '\\\\server\\share\\modules'
]
```

### SMB URL

```php
[
    'name' => 'smb-url',
    'type' => 'network',
    'path' => 'smb://server/share/modules'
]
```

## Path Security

Local sources validate paths to prevent directory traversal:

- Removes `../` and `..\\` sequences
- Validates paths are within base directory
- Uses `realpath()` to resolve symlinks

## Local Filesystem

### Absolute Paths

```php
'path' => '/var/www/modules-registry'
```

### Relative Paths

Relative paths are resolved from project root:

```php
'path' => 'storage/modules-registry'
```

## Network Paths

### Mounted Network Drives

Network drives must be mounted before use:

**Linux:**
```bash
sudo mount -t cifs //server/share /mnt/modules -o username=user,password=pass
```

**macOS:**
```bash
mount_smbfs //user@server/share /Volumes/modules
```

**Windows:**
Network drives are typically mapped automatically.

### UNC Paths (Windows)

UNC paths work on Windows when network is accessible:

```php
'path' => '\\\\server\\share\\modules'
```

### SMB URLs

SMB URLs are normalized to file paths:

```php
'path' => 'smb://server/share/modules'
```

## Directory Structure

Your local registry should follow this structure:

```
modules-registry/
├── modules.json
└── modules/
    └── module-name/
        └── version/
            └── module-name-version.zip
```

## Permissions

Ensure PHP process has:

- **Read access** to registry directory
- **Read access** to modules.json
- **Read access** to module zip files

### Linux/macOS

```bash
chmod -R 755 /path/to/modules-registry
chown -R www-data:www-data /path/to/modules-registry
```

### Windows

- Grant read permissions to IIS_IUSRS or PHP process user
- Ensure network share has appropriate permissions

## Use Cases

### Development

Use local source for development modules:

```php
[
    'name' => 'dev-modules',
    'type' => 'local',
    'path' => '/home/developer/modules'
]
```

### Network Share

Share modules across multiple servers:

```php
[
    'name' => 'shared-modules',
    'type' => 'network',
    'path' => '\\\\fileserver\\modules'
]
```

### Backup Registry

Maintain local backup of modules:

```php
[
    'name' => 'backup-registry',
    'type' => 'local',
    'path' => '/backup/modules-registry'
]
```

## Troubleshooting

### Path Not Found

- Verify path exists and is accessible
- Check path spelling and case sensitivity
- Ensure PHP process has read permissions

### Permission Denied

- Check file and directory permissions
- Verify PHP process user has access
- For network paths, verify mount is active

### Network Path Issues

- Ensure network drive is mounted
- Verify network connectivity
- Check SMB/CIFS service is running
- Test path with `ls` or `dir` command

## Security Considerations

1. **Validate paths** - Prevents directory traversal attacks
2. **Limit access** - Use appropriate file permissions
3. **Avoid symlinks** - Realpath resolves symlinks for security
4. **Network security** - Secure network shares appropriately
5. **Path validation** - All paths are sanitized before use

