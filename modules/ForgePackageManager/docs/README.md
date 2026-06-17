# ForgePackageManager v1.0.0

A flexible package manager for Forge Framework that supports multiple source types for module distribution.

## Overview

ForgePackageManager v1.0.0 introduces multi-source support, allowing you to install modules from various sources:

- **Git** - GitHub, GitLab, Bitbucket, Azure DevOps, and self-hosted Git repositories
- **SFTP** - Secure file transfer over SSH
- **FTP/FTPS** - File transfer protocol with optional SSL/TLS
- **HTTP/HTTPS** - Direct URL downloads with optional basic authentication
- **Local** - Local filesystem paths
- **Network** - Network-mounted drives and SMB/CIFS shares

## Quick Start

1. Configure your registries in `config/source_list.php` or via environment variables
2. Install modules using the CLI:

```bash
php forge.php package:install-module --module=module-name
php forge.php package:install-module --module=module-name@1.0.0
```

## Documentation

- [Configuration Guide](configuration.md) - Complete configuration reference
- [Git Sources](git-sources.md) - Setup for Git-based registries
- [SFTP Sources](sftp-sources.md) - SSH/SFTP configuration
- [FTP Sources](ftp-sources.md) - FTP/FTPS setup
- [HTTP Sources](http-sources.md) - HTTP/HTTPS URL configuration
- [Local Sources](local-sources.md) - Local filesystem and network paths

## Examples

See the [examples directory](examples/) for configuration examples for each source type.

## Features

- Multi-source support with unified interface
- Integrity verification using SHA256 hashes
- Caching for improved performance
- Lock file support for reproducible installations
- Manifest validation for security
- No external dependencies (pure PHP)

