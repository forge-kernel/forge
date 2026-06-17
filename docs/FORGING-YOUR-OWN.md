# FORGING-YOUR-OWN.md

So you want to build your own version of Forge?
Awesome — this doc will walk you through exactly what to fork, what to change, and how to get your own kernel up and running.

---

## Overview

Forge is an Application Hosted Kernel with pluggable capabilities. It's modular and transparent by design, and everything lives in public repositories under the [forge-kernel](https://github.com/forge-kernel) GitHub org.

You can fork any part — just the capabilities (modules), just the starter, or the whole system.

If you're going all in, here's what to do.

---

## 🧬 Repositories to Fork

To fully own and rebrand your Forge stack, fork the following repositories:

### Core Kernel

- [forge-kernel/kernel](https://github.com/forge-kernel/kernel) – the minimal core kernel (DI, routing, bootstrapping, view kernel, configuration manager, and core services)

### Registry

- [forge-kernel/kernel-registry](https://github.com/forge-kernel/kernel-registry) – maps module names to GitHub URLs (used by the package manager)

### Starter Templates

- [forge-kernel/starter-templates](https://github.com/forge-kernel/starter-templates) – a ready-to-go project templates with minimal setup

### Capabilities (Modules)

- [forge-kernel/kernel-module-registry](https://github.com/forge-kernel/kernel-module-registry) – all optional, official Forge capability modules live here (database, ORM, auth, storage, etc.)

### Installer

- [forge-kernel/installer](https://github.com/forge-kernel/installer) – bash script for bootstrapping a new project quickly

### (Optional) Docs

- [forge-kernel/forge-kenel.github.io](https://github.com/forge-kernel/forge-kernel.github.io) – the documentation site
- [forge-kernel/forge-schemas](https://github.com/forge-kernel/forge-schemas) – schema definitions for module manifests, etc.

### Main Dev Repo

- [forge-kernel/forge](https://github.com/forge-kernel/forge) – the full repo containing all capability modules, installer, and kernel glued together. This is mostly for local dev.

---

## ⚙️ What to Change

### 1. Configure Package Manager Sources

The package manager doesn't enforce anything — you explicitly add your trusted sources, just like Linux package managers.

In your project, configure `config/source_list.php`. The package manager supports multiple source types:

- **Git** - GitHub, GitLab, Bitbucket, Azure DevOps, and self-hosted Git repositories
- **SFTP** - Secure file transfer over SSH
- **FTP/FTPS** - File transfer protocol with optional SSL/TLS
- **HTTP/HTTPS** - Direct URL downloads with optional basic authentication
- **Local** - Local filesystem paths
- **Network** - Network-mounted drives and SMB/CIFS shares

Example `config/source_list.php`:

```php
<?php

return [
    'registry' => [
        [
            'name' => 'your-org-modules',
            'type' => 'git',
            'url' => 'https://github.com/your-org/modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('GITHUB_TOKEN')
        ],
        [
            'name' => 'internal-sftp',
            'type' => 'sftp',
            'host' => 'modules.internal.com',
            'port' => 22,
            'username' => env('SFTP_USER'),
            'key_path' => env('SFTP_KEY_PATH'),
            'base_path' => '/modules'
        ],
        [
            'name' => 'local-registry',
            'type' => 'local',
            'path' => '/var/modules-registry'
        ],
        // Add more registries as needed
        // The package manager searches them in order
    ],
    'cache_ttl' => 3600
];
```

**Trusted Sources Philosophy**: Like `apt`, `yum`, or `pacman`, Forge's package manager requires you to explicitly trust sources. When installing modules, you'll be prompted to trust sources. Trusted sources are stored in `storage/framework/trusted_sources.json`. This gives you full control over what gets installed in your kernel.

**Documentation**: For detailed configuration examples for each source type, see the [ForgePackageManager documentation](../modules/ForgePackageManager/docs/README.md):

- [Configuration Guide](../modules/ForgePackageManager/docs/configuration.md) - Complete configuration reference
- [Git Sources](../modules/ForgePackageManager/docs/git-sources.md) - Setup for Git-based registries
- [SFTP Sources](../modules/ForgePackageManager/docs/sftp-sources.md) - SSH/SFTP configuration
- [FTP Sources](../modules/ForgePackageManager/docs/ftp-sources.md) - FTP/FTPS setup
- [HTTP Sources](../modules/ForgePackageManager/docs/http-sources.md) - HTTP/HTTPS URL configuration
- [Local Sources](../modules/ForgePackageManager/docs/local-sources.md) - Local filesystem and network paths

### 2. Installer Script

In your fork of the installer repo, edit `installer.sh`:

```bash
STARTER_REPO_BASE_URL="https://github.com/your-org/forge-starter/archive/refs/heads/main.zip"
```

### 3. install.php Scripts

In both:

- `forge/installer/install.php`
- `forge-starter/install.php`

Update the kernel registry URL:

```php
const FRAMEWORK_REPO_URL = 'https://github.com/your-org/framework-registry';
```

### 4. Starter Updates

In your `forge-starter` fork:

- Update `config/source_list.php` to point to your registries
- Update `.env.example`, `composer.json`, and docs if needed
- Configure your trusted sources as needed

### 5. Optional Capability Module Prefix Rename

If you want, you can rename all module prefixes from `Forge` to something else. This takes some work:

- Rename namespaces and folder names in each module
- Adjust module manifest files (`forge.json`)
- Update your registry entries

I might release a script to help with this — or maybe not. Depends on time and demand.

---

## ✅ Done

After forking, updating URLs, configuring your trusted sources, and optionally renaming capability modules — you've got your own kernel stack.

At this point, Forge becomes a base you control fully. Fork it, ship it, and evolve it how you want.

You're not a user. You're a builder.

---

MIT licensed. Take what helps. Ignore what doesn’t.
