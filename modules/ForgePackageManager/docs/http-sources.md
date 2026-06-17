# HTTP Sources

Configure HTTP/HTTPS URL-based registries with optional basic authentication.

## Configuration

### Public HTTP/HTTPS

```php
[
    'name' => 'http-public',
    'type' => 'http',
    'base_url' => 'https://example.com/modules'
]
```

### HTTP with Basic Authentication

```php
[
    'name' => 'http-auth',
    'type' => 'http',
    'base_url' => 'https://example.com/modules',
    'username' => env('HTTP_USER'),
    'password' => env('HTTP_PASS')
]
```

### Custom Timeout

```php
[
    'name' => 'http-timeout',
    'type' => 'http',
    'base_url' => 'https://example.com/modules',
    'timeout' => 60
]
```

## Environment Variables

Add to `.env`:

```
HTTP_USER=username
HTTP_PASS=password
```

## Server Requirements

Your HTTP server should:

1. Serve files over HTTP/HTTPS
2. Have directory structure:
   ```
   /modules/
   ├── modules.json
   └── modules/
       └── module-name/
           └── version/
               └── module-name-version.zip
   ```

## SSL Certificate Validation

HTTPS sources validate SSL certificates by default. For self-signed certificates, ensure:

1. Certificate is properly installed on server
2. Certificate chain is complete
3. Server name matches certificate

## Basic Authentication

Basic HTTP authentication is supported:

1. Username and password are base64 encoded
2. Sent in `Authorization: Basic` header
3. Credentials should be stored in environment variables

### Server-Side Setup (Apache)

```apache
<Directory "/path/to/modules">
    AuthType Basic
    AuthName "Module Registry"
    AuthUserFile /path/to/.htpasswd
    Require valid-user
</Directory>
```

### Server-Side Setup (Nginx)

```nginx
location /modules {
    auth_basic "Module Registry";
    auth_basic_user_file /path/to/.htpasswd;
}
```

## Custom Headers

For advanced authentication, you may need to modify the HttpSource class to add custom headers.

## Redirects

HTTP sources follow redirects automatically (up to 5 redirects).

## Timeout Configuration

Default timeout is 30 seconds. Adjust for slow connections:

```php
'timeout' => 60  // 60 seconds
```

## Use Cases

### CDN Distribution

```php
[
    'name' => 'cdn-modules',
    'type' => 'http',
    'base_url' => 'https://cdn.example.com/modules'
]
```

### Private Server

```php
[
    'name' => 'private-server',
    'type' => 'http',
    'base_url' => 'https://internal.example.com/modules',
    'username' => env('HTTP_USER'),
    'password' => env('HTTP_PASS')
]
```

### Version Control Integration

Some version control systems provide HTTP access to repositories, which can be used as HTTP sources.

## Troubleshooting

### Connection Failed

- Verify URL is accessible
- Check network connectivity
- Verify SSL certificate is valid

### Authentication Failed

- Verify username and password
- Check server authentication configuration
- Ensure credentials are correctly encoded

### Timeout Errors

- Increase timeout value
- Check server response time
- Verify network stability

### SSL Certificate Errors

- Verify certificate is valid
- Check certificate chain
- Ensure server name matches certificate

## Security Considerations

1. **Use HTTPS** for all HTTP sources
2. **Store credentials** in environment variables
3. **Use strong passwords** for basic auth
4. **Validate certificates** (enabled by default)
5. **Limit access** to specific IPs on server side

