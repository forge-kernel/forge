# Forge

**Forge is an Application Hosted Kernel with pluggable capabilities.**

This is the Forge monorepo — the kitchen sink where the kernel, modules,
installer, and documentation are developed. It is not intended for
application assembly. To start a project, use the installer.

A fast, simple, no-magic kernel that puts you in control. That is what Forge
is — a kernel, not a framework.

---

## Philosophy

Forge is not here to be everything for everyone.
It is here to give you a strong, minimal foundation you can own.

You are not a user. You are a builder.

- If you use Forge, it belongs to you now. Your rules. Your way.
- You get a solid base, and updates if you want them.
- If the direction does not fit yours — fork it, and forge your own path.
- Modules and improvements are published as they help build real-world
  applications. Take what helps, ignore what does not.

You do not build Forge applications. You build your application on top of the
Forge kernel. Kernel + capabilities (modules) + your own code = your
application. You assemble, you own, you decide.

This is not a product. This is a toolbox.

---

## What's In This Monorepo

- **Kernel** — the minimal, no-magic core (DI, module loader, CLI, config)
- **Modules** — pluggable capabilities as separate packages
- **Installer** — project creation and setup scripts
- **Configuration** — registry, environment, and service wiring
- **Documentation** — philosophy, guides, forging your own distribution

The CLI is part of the kernel — it is how you interact with it.

---

## Capabilities as Modules

Forge starts minimal, and you add capabilities as modules when you need them.

Need a database? Install a database module. Need an ORM? Install an ORM module.
Authentication, storage, testing — all capabilities, all optional, all
pluggable.

```bash
php forge.php package:install-module --module=forge-auth
php forge.php package:install-module --module=forge-storage
php forge.php package:install-module --module=forge-database-sql
```

Or clone from [the module registry](https://github.com/forge-kernel/modules)
and drop them in `/modules`.

Capability modules are published as they help build real-world projects.
If something is too specific, it will not be published — or a simplified
version will be.

---

## Install

### With the Installer (Recommended)

```bash
bash <(curl -Ls https://raw.githubusercontent.com/forge-kernel/installer/main/installer.sh)
```

### Manually

```bash
php install.php
php forge.php key:generate
php forge.php package:install-project
```

---

## Want to Build Your Own Kernel Distribution?

See [`FORGING-YOUR-OWN.md`](./docs/FORGING-YOUR-OWN.md).

You can rename the CLI, change the bootstrap flow, use your own registry,
build your own capabilities. Everything is yours. This is a kernel, not a
framework. You are the builder.

---

## License

MIT — take it, use it, change it.
