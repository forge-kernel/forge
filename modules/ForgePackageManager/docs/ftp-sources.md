# FTP Sources

Configure FTP and FTPS (FTP over SSL/TLS) registries for module distribution.

## Configuration

### Basic FTP

```php
[
    'name' => 'ftp-basic',
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

### FTPS (Explicit TLS)

```php
[
    'name' => 'ftps-explicit',
    'type' => 'ftp',
    'host' => env('FTP_HOST'),
    'port' => 21,
    'username' => env('FTP_USER'),
    'password' => env('FTP_PASS'),
    'base_path' => '/modules',
    'passive' => true,
    'ssl' => true
]
```

### FTPS (Implicit TLS)

For implicit TLS, use port 990:

```php
[
    'name' => 'ftps-implicit',
    'type' => 'ftp',
    'host' => env('FTP_HOST'),
    'port' => 990,
    'username' => env('FTP_USER'),
    'password' => env('FTP_PASS'),
    'base_path' => '/modules',
    'passive' => true,
    'ssl' => true
]
```

## Environment Variables

Add to `.env`:

```
FTP_HOST=ftp.example.com
FTP_USER=username
FTP_PASS=password
FTP_PORT=21
```

## Passive vs Active Mode

### Passive Mode (Recommended)

```php
'passive' => true
```

- Works better with firewalls
- Client initiates data connections
- Default and recommended setting

### Active Mode

```php
'passive' => false
```

- Server initiates data connections
- May require firewall configuration
- Less compatible with NAT networks

## Server Requirements

Your FTP server should have:

1. FTP/FTPS service running
2. Directory structure:
   ```
   /modules/
   ├── modules.json
   └── modules/
       └── module-name/
           └── version/
               └── module-name-version.zip
   ```

## SSL/TLS Configuration

### Explicit TLS (FTPS)

- Uses standard FTP port (21)
- TLS negotiation after connection
- More compatible with firewalls

### Implicit TLS

- Uses dedicated port (990)
- TLS from connection start
- Legacy protocol, less common

## Firewall Considerations

### Passive Mode Ports

Passive mode requires opening a range of ports:

1. Control port: 21 (FTP) or 990 (FTPS)
2. Data ports: Configure range on server (e.g., 50000-51000)
3. Open data port range in firewall

### Active Mode

Requires server to connect back to client:

1. Client opens random port
2. Server connects to client port
3. May require client-side firewall rules

## Troubleshooting

### Connection Timeout

- Verify host and port
- Check firewall rules
- Test with FTP client: `ftp ftp.example.com`

### SSL/TLS Errors

- Verify server supports FTPS
- Check certificate validity
- Try explicit TLS first

### Authentication Failed

- Verify username and password
- Check account permissions
- Ensure account has read access

### Passive Mode Issues

- Verify passive port range is open
- Check server passive mode configuration
- Try active mode if firewall allows

## Security Best Practices

1. **Use FTPS** instead of plain FTP
2. **Use strong passwords** or certificate-based auth
3. **Limit FTP access** to specific IPs
4. **Use passive mode** for better firewall compatibility
5. **Regularly update** FTP server software

