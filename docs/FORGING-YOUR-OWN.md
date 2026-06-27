# FORGING-YOUR-OWN.md

You want to build your own kernel distribution. This doc covers what to fork,
what to change, and how to get your own kernel running.

---

## Overview

Forge is an Application Hosted Kernel with pluggable capabilities. Modular and
transparent by design. Everything lives in public repositories under the
[forge-kernel](https://github.com/forge-kernel) GitHub org.

You can fork any part — just the modules, just the blueprints, or the whole
system.

---

## Repositories to Fork

To fully own and rebrand your stack, fork the following repositories:

### Core Kernel

- [forge-kernel/kernel](https://github.com/forge-kernel/kernel) — the minimal
  kernel core (DI container, module loader, CLI, config manager, bootstrap)

### Registry

- [forge-kernel/kernel-registry](https://github.com/forge-kernel/kernel-registry)
  — maps module names to GitHub URLs (used by the package manager)

### Blueprints

- [forge-kernel/blueprints](https://github.com/forge-kernel/blueprints) —
  pre-assemblies with certain capabilities for building your application

### Capabilities (Modules)

- [forge-kernel/kernel-module-registry](https://github.com/forge-kernel/kernel-module-registry)
  — all optional capability modules (database, ORM, auth, storage, etc.)

### Installer

- [forge-kernel/installer](https://github.com/forge-kernel/installer) — bash
  script for bootstrapping a new project

### (Optional) Docs

- [forge-kernel/forge-kernel.github.io](https://github.com/forge-kernel/forge-kernel.github.io)
  — documentation site
- [forge-kernel/forge-schemas](https://github.com/forge-kernel/forge-schemas)
  — schema definitions for module manifests

### Main Dev Repo

- [forge-kernel/forge](https://github.com/forge-kernel/forge) — the monorepo
  that brings together the kernel, modules, installer, and docs. For
  development only.

---

## What to Change

### 1. Configure Package Manager Sources

The package manager does not enforce anything — you explicitly add your trusted
sources, like Linux package managers.

In your project, configure `config/source_list.php`. The package manager
supports multiple source types:

- **Git** — GitHub, GitLab, Bitbucket, Azure DevOps, self-hosted
- **SFTP** — secure file transfer over SSH
- **FTP/FTPS** — with optional SSL/TLS
- **HTTP/HTTPS** — direct URL downloads with basic auth
- **Local** — local filesystem paths
- **Network** — network-mounted drives and SMB/CIFS shares

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

**Trusted Sources Philosophy**: Like `apt`, `yum`, or `pacman`, the package
manager requires you to explicitly trust sources. When installing modules,
you will be prompted to trust sources. Trusted sources are stored in
`storage/framework/trusted_sources.json`. This gives you full control over
what gets installed.

**Documentation**: For detailed configuration examples for each source type,
see the [ForgePackageManager documentation](../modules/ForgePackageManager/docs/README.md):

- [Configuration Guide](../modules/ForgePackageManager/docs/configuration.md)
- [Git Sources](../modules/ForgePackageManager/docs/git-sources.md)
- [SFTP Sources](../modules/ForgePackageManager/docs/sftp-sources.md)
- [FTP Sources](../modules/ForgePackageManager/docs/ftp-sources.md)
- [HTTP Sources](../modules/ForgePackageManager/docs/http-sources.md)
- [Local Sources](../modules/ForgePackageManager/docs/local-sources.md)

### 2. Installer Script

In your fork of the installer repo, edit `installer.sh`:

```bash
BLUEPRINT_REPO_BASE_URL="https://github.com/your-org/forge-blueprint/archive/refs/heads/main.zip"
```

### 3. install.php Scripts

In both:

- `forge/installer/install.php`
- `forge-blueprint/install.php`

Update the kernel registry URL:

```php
const FRAMEWORK_REPO_URL = 'https://github.com/your-org/framework-registry';
```

### 4. Blueprint Updates

In your `forge-blueprint` fork:

- Update `config/source_list.php` to point to your registries
- Update `.env.example`, `composer.json`, and docs if needed
- Configure your trusted sources as needed

### 5. Optional Capability Module Prefix Rename

If you want, rename all module prefixes from `Forge` to something else:

- Rename namespaces and folder names in each module
- Adjust module manifest files (`forge.json`)
- Update your registry entries

---

## Done

After forking, updating URLs, configuring your trusted sources, and optionally
renaming modules — you have your own kernel stack. Fork it, ship it, evolve it
how you want.

You are not a user. You are a builder.

---

MIT licensed. Take what helps. Ignore what does not.
