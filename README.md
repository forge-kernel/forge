# Forge

**Forge is an Application Hosted Kernel with pluggable capabilities.**

It's here because I
wanted a fast, simple, no-magic kernel that puts _me_ in control. That's what Forge is — a kernel, not a framework. If that works for you too,
welcome.

---

## Philosophy

Forge is not here to be everything for everyone.  
It’s here to give you a strong, minimal foundation you can **own**.

You’re not a user. You’re a builder.

- If you use Forge, it belongs to you now. Your rules. Your way.
- You get a solid base, and updates if you want them.
- If my direction doesn’t fit yours — fork it, and forge your own path.
- I’ll keep publishing modules and improvements that help me build real-world apps. You’re free to take what helps,
  ignore what doesn’t.

This isn’t a product. This is a toolbox.

---

## What's In The Box

- Simple, fast dependency injection container
- Modular structure (install only what you need)
- Zero dependencies, zero magic
- Built-in router, configuration manager, and core services
- Pluggable capabilities system — capabilities are packed as modules
- Module system with life cycle hooks
- CLI for installing modules, project, scaffold commands etc.

**Capabilities, not built-ins.** Database, ORM, authentication, storage — these aren't built into the kernel. They're capabilities you plug in via modules when you need them. The kernel stays lean. You stay in control.

Everything is structured for clarity. No magic files. No guesswork.

---

## Install

### With the Installer (Recommended)

```bash
bash <(curl -Ls https://raw.githubusercontent.com/forge-kernel/installer/main/installer.sh)
```

### Manually

php install.php
php forge.php key:generate
php forge.php package:install-project

````

---

## Capabilities as Modules

Forge starts minimal (under 400KB), and you add capabilities as modules when you need them.

Need a database? Install a database capability module. Need an ORM? Install an ORM capability module. Authentication, storage, testing — all capabilities, all optional, all pluggable.

```bash
php forge.php package:install-module --module=forge-auth
php forge.php package:install-module --module=forge-storage
php forge.php package:install-module --module=forge-database-sql


Or clone from [github.com/forge-kernel/kernel-module-registry](https://github.com/forge-kernel/kernel-module-registry) and drop them in `/modules`.

I publish capability modules that help me build real-world projects. If something's too specific, I won't. Or I'll release a
simplified version.

---

## Want to Build Your Own Kernel?

Do it. I'll even show you how.
See: [`FORGING-YOUR-OWN.md`](./docs/FORGING-YOUR-OWN.md)

You can rename the CLI, change the bootstrap flow, use your own registry, build your own capabilities — everything is yours now. That's the point. This is a kernel, not a framework. You're the builder.

---

## License

MIT — take it, use it, change it.
Just don’t whine if it’s not what you expected.
Nobody owes you anything. Build your own vision.
````
