# Plan: Forge Documentation Reorganization

> Goal: Stop selling the Kernel as a framework. `forge-documentation/index.html` correctly positions the Kernel as a minimal, dependency-free PHP kernel with pluggable capabilities. The rest of the docs must align with that story: **Kernel + Capabilities + Your Code = Your App. You assemble, you own.**

Source of truth: `kernel/README.md`, `kernel/Core/*`, `docs/FORGING-YOUR-OWN.md`, `capabilities/*/forge.json` + `modules/*/forge.json`, `forge.json`, and live capability source in `modules/` / `capabilities/`. Kernel supports both `modules/` and `capabilities/` directories (`kernel/Core/Structure/forge_structure.php:32-33`) due to slow migration. Docs are currently outdated; every phase requires a pre-flight check against the kernel/capability source before editing HTML.

> **Clarification 2026-08-27 (updated):** Kernel loader treats `modules/` and `capabilities/` identically (semantics + namespace only). `capabilities/` = primitives / reusable building blocks (e.g. `ForgeDatabaseSql`, `ForgeHtmx` - already migrated). `modules/` = app-specific modules (e.g. your `Blog`, `Shop` business features). Both use same `src/*Module.php` convention (`kernel/Core/Structure/forge_structure.php:32-35`) and `Modules\` / `Capability\` namespaces. This is a gradual migration: old primitives still live in `modules/` until moved. **Developer freedom:** You can follow this convention, or put everything in `modules/`, or everything in `capabilities/`, or mixed - the Kernel does not enforce it (`forge_structure.php:32-33` `modules_root => ['modules','capabilities']` scans both in order, no hard rule). Docs must present it as a *recommended convention* (like PSR-4), not a requirement, and explicitly say "use your choice".
> **Clarification 2026-08-27 (structure):** Default folder structure is fully customizable. Kernel defaults live in `kernel/Core/Structure/forge_structure.php:3-58` (app/capabilities/modules roots, namespaces, per-type paths). You don't have to create `forge_structure.php` by hand — use the wizard `php forge.php structure:init` (`kernel/CLI/Commands/StructureInitCommand.php:12`) which offers full / app-only / modules-only / partial / roots-only modes plus merge, and warnings about file moves (`:47-56`). View current config with `php forge.php structure:info` (`StructureInfoCommand.php:14`). The wizard writes the same `forge_structure.php` at project root (`StructureResolver.php:13` `BASE_PATH . '/forge_structure.php'`). Each capability/module can also define its own internal structure via `#[Structure]` attribute (`kernel/Core/Module/Attributes/Structure.php:10`) / `StructureResolver.php:78`. This deserves its own section in `anatomy.html` ("Customizing Folder Structure & Namespaces") with wizard example + warnings; per-capability overrides are then detailed in that capability's own page, not duplicated. See Phase 1.3 update.
> **Clarification 2026-08-27 (discovery & tone):** Service discovery is now **scoped, not global**. Removed the “scan every folder recursively” auto-discovery for performance/caching. Now via `kernel/Core/Bootstrap/ServiceDiscoverSetup.php:42-72` which only scans `injectable` paths from `forge_structure.php:9,39`. Any reference to “any folder + `#[Service]`/`#[Injectable]` is found” must be corrected in `kernel-overview.html`, `core-concepts.html`, `getting-started.html`, and `anatomy.html`. Also, fix tone: `kernel-overview.html` rewrite removed robot file:line citations, file counts, and overflowing card code — keep hand-written feel like `index.html` (short, plain, no marketing pitch). `break-words`/`overflow-hidden` added to cards.

**Status legend:** `PENDING` | `IN PROGRESS` | `DONE` | `BLOCKED`
**File scope:** All changes live in `forge-documentation/` unless noted. New pages are `forge-documentation/<name>.html`.

---

## 0. Principles & Guardrails

- [ ] `DONE` Homepage (`forge-documentation/index.html:143-259`) stays as-is - it's the only page that nails the pitch.
- [ ] `PENDING` Kernel pages must never present Router/View/DB/ORM/Auth as built-ins. Every capability feature gets a `Prerequisite:` callout with install command: `php forge.php package:install-module --module=ForgeX`.
- [ ] `PENDING` Terminology: `Capability` = primitive/reusable building block distributed as ZIP unit (e.g. `ForgeDatabaseSql`, `ForgeHtmx`) - recommended lives in `capabilities/` (`Capability\` namespace), but legacy primitives still in `modules/` during migration. `Module` = app-specific feature module recommended in `modules/` (`Modules\` namespace, e.g. `Blog`). Technically both are modules to the Kernel loader (`kernel/Core/Module/ModuleLoader/Loader.php:74` scans both `modules_root`); difference is semantic/convention only, not enforced - you may use `modules/` only, `capabilities/` only, or mixed (`forge_structure.php:32-33`). Add glossary in `capabilities.html` + `anatomy.html` explicitly: "Recommended convention (not requirement): primitives -> `capabilities/`, app modules -> `modules/`. Choose what fits your project." with migration note (`capabilities/ForgeHtmx` is the reference migrated example).
- [ ] `PENDING` No kernel code changes in this plan. Docs-only.
- [ ] `PENDING` Tone: Human, hand-written feel (like `index.html`). No robot gibberish — no inline `file:line` citations, no file counts (“19 dirs + 3”), no `Verified against ...` headers, no overflowing code in cards. Write as a person explaining to another builder. Keep it short, plain, no marketing pitch.
- [ ] `PENDING` Service discovery is now manual & scoped, not “any folder”, and no attribute needed. Kernel scans only `injectable` paths via `kernel/Core/Bootstrap/ServiceDiscoverSetup.php:42-72` → `forge_structure.php:9,39` `injectable => ['Services','Listeners']` and `['src/Services','src/Listeners','src/Providers']`. `ServiceDiscoverSetup.php:103-106` registers **any** class found there (`!$reflection->isAbstract() && !$reflection->isInterface()` → `$container->register()`), no `#[Injectable]` check. `#[Injectable]` (`kernel/Core/DI/Attributes/Injectable.php:10`) and legacy `#[Service]` are leftovers — optional only if you want custom `id`/`singleton:false` (`Container.php:76-85`), not required for discovery. Verified 2026-08-27: `Service` attribute file does not exist (`grep Service` → false), `RegisterModuleService.php:49` references it as legacy. Docs must say “put file in Services/Listeners/Providers — it’s found, no attribute” and keep caching note (removed global scan).

**Workflow for every sub-phase (mandatory):**

1.  Pre-flight check: `read` the listed kernel/capability source file(s) to confirm current behavior (DI attributes, bootstrap flow, config keys, forge.json manifest).
2.  Edit HTML with evidence-backed content (cite `file:line` in commit message).
3.  Manual verify: `python3 -m http.server` in `forge-documentation/` and click-through nav/sidebar/anchors.
4.  Update this file's status checkbox and add date.

---

## Phase 1 - Kernel Foundation & Navigation (smallest slice, highest leverage)

> Why first: Establishes the correct mental model before we touch Getting Started or Capabilities. Each page is its own sub-phase.

### Phase 1.0 - Pre-flight: Audit current nav & kernel surface

- Status: `DONE` (2026-08-27)
- Pre-flight check:
  - `forge-documentation/index.html:22-80` (nav)
  - `forge-documentation/getting-started.html:32-55` (nav)
  - `forge-documentation/core-concepts.html:32-69` (nav)
  - `forge-documentation/modules.html:1-55` (nav, title mismatch)
  - `kernel/README.md:1-53` (what's in kernel vs not)
  - `kernel/Core/Kernel.php:18-30` (init -> Bootstrap), `kernel/Core/Bootstrap/Bootstrap.php:40-62` (storage dirs, env, session, ContainerAppSetup), `kernel/Core/DI/Container.php:22-54` (singleton, service map, module lazy-load), `kernel/Core/Module/ModuleLoader/Loader.php:32-99` (discoverModuleDirectories)
- Changes:
  - Document exact file rename needed: `modules.html` -> `capabilities.html` (keep `modules.html` as meta-refresh redirect for SEO/bookmarks).
  - Decide nav grouping for Phase 1 (flat nav stays for now, but add `Kernel` dropdown stub).
- Exit criteria: Audit notes committed to this file; redirect strategy agreed.

#### Audit Findings (2026-08-27)

**Nav consistency:**
- `forge-documentation/index.html:52` desktop link text `Capabilities` -> `href="modules.html"` ; `forge-documentation/index.html:118` mobile link text `Modules` -> same href. **Mismatch: mobile says Modules, desktop says Capabilities, both point to `modules.html`.**
- `forge-documentation/getting-started.html:52` & `core-concepts.html:52` & `modules.html:41` all correctly say `Capabilities` for `modules.html` href. So only `index.html` mobile is wrong.
- All 14 `forge-*.html` + all 9 `tutorial-*.html` + `tutorial.html` use `href="modules.html"` for Capabilities (verified `grep -rn 'href="modules.html' forge-documentation/` => 30+ hits). Rename requires bulk replace.
- `forge-documentation/modules.html:7` `<title>Capabilities - Forge Kernel Documentation</title>` matches intent, but file name is `modules.html`. `forge-documentation/api-reference.html:52` etc. consistent.
- Nav items identical across all pages: `Home | Getting Started | Core Concepts | Capabilities(modules.html) | API Reference | Tutorials | GitHub` - flat, no Kernel group.

**Kernel surface (ground truth, docs currently drift):**
- Kernel is `kernel/Core/Kernel.php:18` -> `Bootstrap::getInstance()` only. No Router, View, DB. `kernel/README.md:3-6` explicitly: "It handles bootstrapping, DI, module loading, configuration, CLI infrastructure. Everything else — database, routing, auth, storage, templating — is a capability."
- Kernel components verified: `kernel/Core/DI/Container.php:36-45` (services, instances, parameters), `kernel/Core/Bootstrap/Bootstrap.php:24-33` (STORAGE_DIRS 7 dirs, CacheRebuildTrigger, Environment, Session), `kernel/Core/Module/ModuleLoader/Loader.php:74-99` (scans `modules` AND `capabilities` per `kernel/Core/Structure/forge_structure.php:32` `modules_root => ['modules','capabilities']`), `kernel/Core/Config/`, `kernel/CLI/`, `kernel/Core/Cache/`, `kernel/Core/Session/`, `kernel/Core/Autoloader.php`, `kernel/Core/Support/helpers.php`.
- `kernel/Core/Structure/forge_structure.php:4-58` shows `app_root = app`, `modules_root = ['modules','capabilities']`, `modules_namespace = ['Modules','Capability']` - **Clarification 2026-08-27 (updated):** `capabilities/` = primitives (Forge building blocks like `ForgeDatabaseSql`, `ForgeHtmx`), `modules/` = app-specific modules (Blog, Shop). Same loader, different semantics/namespaces. Migration is gradual: `capabilities/ForgeHtmx` is already migrated as reference; most primitives still live in `modules/` (30 dirs there, 1 in `capabilities/`). **Freedom:** User may follow convention, or keep everything in `modules/`, or everything in `capabilities/`, or mixed - Kernel scans both and does not enforce (`forge_structure.php:32-33`). Docs must present `capabilities/` vs `modules/` as recommended convention, not requirement, with explicit "Your choice" callout.
- Module discovery `Loader.php:91` requires `src/*Module.php` e.g. `ForgeRouterModule.php` -> docs mention `ForgeRouter` correctly but some cards broken. Namespace for new primitives should be `Capability\Name` (`forge_structure.php:33`), app-modules stay `Modules\Name`, but mixed placement is allowed - table must show "Recommended" not "Required".
- `capabilities/` disk: only `capabilities/ForgeHtmx` exists; 29 primitives live in `modules/` (`ForgeRouter`, `ForgeAuth`, `ForgeDatabaseSQL`, `ForgeSqlOrm` - `ForgeView` currently not on disk as separate module, view likely inside Router/App). Loader supports both, so both locations must be documented in `anatomy.html` and `capabilities.html` glossary with choice emphasis.

**Outdated docs evidence:**
- `forge-documentation/core-concepts.html:238-276` Architecture lists 6 built-ins correctly but then sections `Routing System` `core-concepts.html:468` / `Middleware` `core-concepts.html:526` / `View Engine` `core-concepts.html:809` present as Core Concepts without install prerequisite (small tag `via ForgeRouter capability` only). Same duplication in `getting-started.html:635-956` teaches Routing/View/DB as Getting Started steps.
- `forge-documentation/modules.html:466-483` ForgeWire card shows tags `Authentication, Sessions, JWT, Mobile` (copy-paste from ForgeAuth `modules.html:420-426`). Duplicate `ForgeDeployment` card at `modules.html:430` and `modules.html:485` identical.

**Redirect Strategy (agreed):**
- Create `forge-documentation/capabilities.html` as canonical copy of `modules.html` (Phase 2.1). Keep `modules.html` as HTML meta-refresh redirect: `<meta http-equiv="refresh" content="0; url=capabilities.html">` + canonical link + JS fallback, to preserve bookmarks/SEO.
- Do NOT 301 at server yet (static hosting); meta refresh is portably correct.
- Bulk replace `href="modules.html"` -> `href="capabilities.html"` across all docs in Phase 5.1 after canonical exists. Phase 1.1 will add new Kernel links additively, not replace existing nav.
- Phase 1.1 nav: additive only - add `Kernel` group (stub links to `kernel-overview.html` etc.) alongside existing flat nav, keep old items for compat. Mobile mirrors desktop. No deletion until Phase 3.
- Verification: `grep -rn 'modules.html' forge-documentation/` should show only the redirect stub after Phase 5.

### Phase 1.1 - Navigation in `index.html` (and propagate stub)

- Status: `DONE` (2026-08-27)
- Pre-flight check:
  - `kernel/Core/Kernel.php:18-30` (init → Bootstrap, verified `kernel/README.md:14-26` surface)
  - `kernel/Core/Structure/forge_structure.php:32-33` (both roots `modules`/`capabilities` + namespaces `Modules`/`Capability` - migration semantics + user choice noted in stubs)
- Changes:
  - `forge-documentation/index.html:32-67` → `forge-documentation/index.html:32-118` - Added nav group `Kernel` dropdown (desktop hover + click toggle `index.html:340-356`) with 4 links: `kernel-overview.html` (Overview — What's in / NOT), `anatomy.html`, `lifecycle.html`, `forging-your-own.html`. Kept existing `Getting Started | Core Concepts | Capabilities(modules.html) | API Reference | Tutorials` for compat. Mobile menu `index.html:135-145` now correctly says `Capabilities` (fixed `Modules` bug) and shows nested Kernel section `index.html:136-141`.
  - Created stubs: `kernel-overview.html`, `anatomy.html`, `lifecycle.html`, `forging-your-own.html` with `IN PROGRESS` yellow banner citing verified kernel source lines and "Your choice" + customizable structure notes.
  - No deletions — additive only.
- Exit criteria: `index.html` renders, nav click targets exist (even as 404 stubs with TODO banner), mobile toggle works.
- **Verification (2026-08-27):** `grep -n 'Kernel' forge-documentation/index.html` shows dropdown; `ls -lh kernel-overview.html anatomy.html lifecycle.html forging-your-own.html` 4 files; `python3 -m http.server 8001 --directory forge-documentation` → `curl -w "%{http_code}"` 200 for all 5 pages (`index.html`, 4 stubs); `grep -rn 'href="modules.html' wc -l` still 75 (expected until Phase 5).

### Phase 1.2 - `kernel-overview.html` (What's in the kernel / what's NOT)

- Status: `DONE` (2026-08-27)
- Pre-flight check (MUST read before writing):
  - `kernel/README.md:1-53` (entire file) ✓
  - `kernel/Core/Contracts/` (`DatabaseConnectionInterface.php:11`, `ViewInterface.php`, `Cache/`, `EventDispatcherInterface.php`) ✓
  - `kernel/Core/DI/Container.php:22` + `Attributes/Injectable.php:10` + `RegisterModuleService.php:49` (Injectable/Service alias) + `ServiceDiscoverSetup.php:20` ✓
  - `kernel/Core/Config/`, `kernel/Core/Module/Loader.php:74`, `kernel/CLI/`, `kernel/Core/Cache/`, `kernel/Core/Session/`, `kernel/Core/Support/helpers.php:13,28,55` (env, cache, config, e, data_get), `kernel/Core/Autoloader.php:44-123` (PSR-4 + class_file_map), `kernel/Core/Structure/forge_structure.php:3-58`, `StructureResolver.php:11` ✓
  - `forge-documentation/core-concepts.html:238-277` (existing Architecture section to reuse but correct) ✓
- Changes:
  - Replaced stub `forge-documentation/kernel-overview.html:1` (8.3K) with full page (verified sidebar `Kernel Overview` with 7 anchors: `what-is-kernel`, `whats-in`, `whats-not`, `contracts`, `philosophy`, `capabilities-vs-modules`, `custom-structure`).
   - Sections: `What is the Kernel` (Kernel → Bootstrap, human tone, no file:line), `What's In the Kernel` (12 cards now human-written, no overflowing `kernel/Core/...:line` code; `break-words`/`overflow-hidden` fixed), `What's NOT` (yellow callout + grid linking ForgeRouter/ForgeView/ForgeHtmx etc., prerequisite `package:install-module`), `Contracts vs Implementations` (DatabaseConnectionInterface snippet without line numbers, plain before/after boxes), `Philosophy` (plain, builder tone like index.html), `Capabilities vs Modules` table + "Your choice" callout, `Customizing Folder Structure` pointer to `anatomy.html#custom-structure`.
   - Nav same as `index.html` (Kernel dropdown `kernel-menu-wrap` + mobile, hover+click JS `index.html:340-358`).
- Exit criteria: Page renders, no robot file:line citations or file counts visible, no marketing pitch, no card overflow, human hand-written feel like `index.html`.
- **Verification (2026-08-27):** `grep -c "Verified against|Kernel file count"` 0 (gibberish removed), `grep -c "break-words"` 18 (overflow fix), `python3 -m http.server 8003` → 200 `kernel-overview.html`; 7 sidebar anchors; links to `anatomy.html` resolve.
- **Fix 2026-08-27 (tone + discovery):** Rewrote `kernel-overview.html` to remove inline citations, `Kernel file count: 19 dirs...` line, file:line citations in cards; simplified TL;DR; changed cards to `overflow-hidden` + `break-words`. Also corrected discovery: removed false “Mark with `#[Injectable]` anywhere” and later “Mark with `#[Injectable]` in injectable folders” — **double-checked 2026-08-27**: `ServiceDiscoverSetup.php:103-106` registers any class in `injectable` paths without attribute check; `#[Injectable]`/`#[Service]` are leftovers, optional only for custom id/singleton (`Container.php:76`, `Injectable.php:10`). `Service` attribute file does not exist. Docs now say “put file in Services/Listeners/Providers — no attribute needed”.

### Phase 1.3 - `anatomy.html` (Project structure: `forge.json`, `forge.php`, `config/`, `modules/` vs `capabilities/`, `storage/`)

- Status: `DONE` (2026-08-27)
- Pre-flight check (MUST read before writing):
  - `forge.json` (manifest) ✓
  - `forge.php`, `install.php` (entry points) ✓
  - `config/` dir listing (`forge_router.php`, `middleware.php`, `registry.php` — NOT `source_list.php` as originally speculated) ✓
  - `kernel/Core/Structure/forge_structure.php:3-58` (both roots + migration semantics) + `StructureResolver.php:11-33` (customizable structure) + `Structure.php:10` (`#[Structure]` attribute) ✓
  - `kernel/Core/Bootstrap/Bootstrap.php:24-33` (STORAGE_DIRS / BASE_PATH) ✓
  - `kernel/CLI/Commands/StructureInitCommand.php` + `StructureInfoCommand.php` (wizard + info CLI) ✓
  - `public/index.php`, root `index.php`, `.env`/`env-example`, `forge-lock.json`, `kernel/Core/Config/Config.php` + `helpers.php:63` (config() by filename) ✓
- Changes:
  - Replaced stub with full `forge-documentation/anatomy.html` (sidebar `Anatomy & Structure` with 8 anchors: `blueprint-vs-installed`, `root-files`, `config`, `app-vs-modules`, `storage`, `public`, `custom-structure`, `forge-lock`).
  - Sections: `Blueprint vs Installed App`, `Root Files` (6 cards: forge.json / forge.php / install.php / .env / root index.php 403-guard / forge_structure.php), `config/` (filename→array maps to `config()` helper), `app/ vs modules/ vs capabilities/` (namespace table `App\` `Modules\` `Capability\` + "Your choice — not enforced" callout + `src/*Module.php` entry-file example), `storage/` (Bootstrap STORAGE_DIRS incl. `trusted_sources.json`), `public/` (web root + `public/index.php` → `Kernel::init()`; root `index.php` is 403 guard), `Customizing Folder Structure & Namespaces` (wizard `structure:init` 5 modes + overwrite/merge + `structure:info` + hand-written `forge_structure.php` example + warnings callout + `#[Structure]` sovereignty + per-capability link), `forge.lock.json` (pin/integrity record, commit it).
  - **Correction applied:** used `#[Structure]` attribute (not `#[Module]`) per 2026-08-27 re-verification; `config/` reality is `forge_router.php`/`middleware.php`/`registry.php` (corrected the plan's earlier `source_list.php` assumption).
  - Tone: human hand-written like `index.html`; no inline `file:line` citations, no file counts, no marketing, `break-words`/`overflow-hidden` on cards.
- Exit criteria: Structure matches real `forge.json` + `install.php` constants and `forge_structure.php:32-33`, not stale docs; choice + customizability are explicit with working `forge_structure.php` example (from `StructureInitCommand.php` merge behavior, array shape matches `StructureResolver`).
- **Verification (2026-08-27):** `python3 -m http.server 8005` → 200 for `anatomy.html` and `kernel-overview.html`; 8 sidebar anchors all present (`grep -o 'id=...'`); `kernel-overview.html` link `anatomy.html#custom-structure` resolves to matching `<section id="custom-structure">`; `Config.php` + `helpers.php:63` confirm `config('registry')` → `config/registry.php` claim; both pages served. Next: Phase 1.4 `lifecycle.html`.

### Phase 1.4 - `lifecycle.html` (Kernel bootstrap for web & CLI)

- Status: `DONE` (2026-08-27)
- Pre-flight check (MUST read before writing):
  - `kernel/Core/Bootstrap/Bootstrap.php` (init: storage dirs, cache trigger, env, timezone, session, `ContainerAppSetup::initOnce`) ✓
  - `kernel/Core/Kernel.php:18-30` (`init()` → load `.env` → `Bootstrap::getInstance()`; also `.env` request guard at top of file) ✓
  - `kernel/Core/Bootstrap/ContainerAppSetup.php:43-106` (web container: core services → includes → early hooks → EARLY_BOOT → `ModuleSetup::loadModules()` → error handler → `ServiceDiscoverSetup` → APP_BOOTED → `finishBootstrap`) ✓
  - `kernel/Core/Bootstrap/ContainerCLISetup.php` + `AppCommandSetup.php` + `kernel/CLI/Application.php` (CLI path: modules + preload → services → command discovery → `Application::run`) ✓
  - `kernel/Core/Module/ModuleLoader/Loader.php` (`discoverModuleDirectories` glob `src/*Module.php:91`, `#[Module]` order/type/core:127-167, disabled `DISABLED_MODULES:443-447`, ModuleCache + compiled_hooks) ✓
  - `kernel/Core/Module/LifecycleHookName.php` (7 hooks: EARLY_BOOT, BEFORE_MODULE_LOAD, AFTER_BOOT, AFTER_MODULE_LOAD, AFTER_MODULE_REGISTER, AFTER_CONFIG_LOADED, APP_BOOTED) ✓
  - `kernel/Core/Bootstrap/ServiceDiscoverSetup.php` (scoped `injectable` paths via StructureResolver, no attribute, also registers `#[LifecycleHook]` on discovered classes) ✓
  - `kernel/Core/Autoloader.php:379` (`class_file_map.php` — corrected the stub's wrong `class-map.php`) ✓
  - `modules/ForgeRouter/src/*Module.php` (`#[LifecycleHook(APP_BOOTED)] boot():169` → build Request → RouterSetup → `Kernel->handler()` → `response->send()`) — the web request terminates here in the capability ✓
  - `public/index.php` (maintenance check + `Kernel::init()`), `forge.php` (CLI) ✓
- Changes:
  - Replaced stub with full `forge-documentation/lifecycle.html` (sidebar `Lifecycle & Bootstrap`, 8 anchors: `two-entry-points`, `boot-sequence`, `web-flow`, `cli-flow`, `module-loading`, `service-discovery`, `config-loading`, `lifecycle-hooks`).
  - Sections: `Two Entry Points, One Bootstrap` (web `public/index.php` vs CLI `forge.php`, both converge on container — Kernel doesn't know HTTP), `The Shared Boot Sequence` (storage dirs → cache trigger → env → timezone → session defaults → container setup), `Web Request Flow` (ordered bash flow + note that ForgeRouter answers via `#[LifecycleHook(APP_BOOTED)]`, request terminates in capability), `CLI Flow` (ordered flow + core vs `module:`/`dev:` commands + `FORGE_DEVELOPER_MODE` gate + interactive startup), `How Modules Load` (glob `src/*Module.php` entry file, `#[Module]` order/type/core, disabled modules, caching, register methods), `Service Discovery` (scoped `injectable` paths, no attribute, `#[Injectable]` optional leftover, hooks on discovered classes, `class_file_map.php`), `Config Loading` (`config/*.php` keyed by filename → `config()` helper, `#[ConfigDefaults]` merge, `.env` for secrets), `Lifecycle Hooks` (table of all 7 hook names + when they run).
  - **Correction applied:** removed stub's stale `#[Discoverable]`/`#[Service]`/`class-map.php` claims; discovery is scoped `injectable` (no attribute), autoloader cache is `storage/framework/cache/class_file_map.php`.
  - **Command registration / prefixing (2026-08-27, re-verified + user-clarified):** Prefixes are purely for CLI organization, not an indicator of "core" status. Module commands get a `modules:` prefix via the `RegistersCommands` trait (`RegistersCommands.php:25`) — e.g. `modules:forge-router:init`, `modules:saas:plan:list` (the earlier `module:` in docs and my notes was a typo). App commands you list in `forge_structure.php` → `app.commands` are registered with an empty prefix (`LoadsCommands.php:34`), so they have no prefix (`app:` is only the separate `registerAppCommandClass()` API). `#[CoreCommand]` on a command class skips prefixing entirely (`Application.php:131-135`) so a command keeps a short name like `db:migrate` — a naming decision, not a claim of kernel membership. `dev:` commands only register when `FORGE_DEVELOPER_MODE=true` (`Application.php:109-111,144-150`); mostly kernel/module dev tools (registry, blueprint, kernel/module list). Real core command names: `structure:init`, `structure:info`, `storage:link`, `storage:unlink`, `asset:link`, `asset:unlink`, `generate:module`, `generate:command`, `generate:event`, `generate:migration`, `generate:seeder`, `generate:entity`, `generate:test`, `key:generate`, `cache:flush`, `cache:warm`, `cache:rebuild`, `stats`.
  - **Refined further (2026-08-27, kernel-only scope):** removed web-flow/router HTTP material entirely — it's not Kernel responsibility and ForgeRouter documents its own lifecycle. Page is now strictly the Kernel bootstrap: `Kernel::init()` → boot sequence → CLI flow → modules → services → config → hooks. No `public/index.php`/request/response/router content remains; a note points HTTP-related lifecycle to the capability's own page. Sections: `#kernel-init`, `#boot-sequence`, `#cli-flow`, `#module-loading`, `#service-discovery`, `#config-loading`, `#lifecycle-hooks`.
  - Tone: human hand-written, no inline `file:line` citations, no file counts, no marketing, `break-words`/`overflow-hidden` on cards; Router/View/DB framed as capabilities, never kernel built-ins.
- Exit criteria: ordered flow cites actual bootstrap files/class names (all verified above) and capability framing is correct.
- **Verification (2026-08-27):** `python3 -m http.server 8007` → 200 for `lifecycle.html`, `anatomy.html`, `kernel-overview.html`; 8 sidebar anchors present; stale `class-map.php`/`#[Discoverable]`/`#[Service]` all absent (`grep -F` = none); web/CLI flow steps match `ContainerAppSetup.php` + `ContainerCLISetup.php` + `ForgeRouterModule.php` source. Next: Phase 1.5 `forging-your-own.html`.

### Phase 1.5 - `forging-your-own.html` (Own Your Stack)

- Status: `DONE` (2026-08-27)
- Pre-flight check (verified):
  - `docs/FORGING-YOUR-OWN.md:1-181` (entire file) ✓
  - `install.php` root exists with `const FRAMEWORK_REPO_URL` ✓
  - `config/source_list.php` — confirmed NOT a core kernel config; it's the **ForgePackageManager capability's** config, auto-created at first run pre-filled with the official registry (`ForgePackageManagerModule.php:63-81`), read via `config('source_list...')`. Framed as such in docs.
  - `storage/framework/trusted_sources.json` exists (apt/yum/pacman trusted-sources analogy) ✓
  - Registry shape: `config/registry.php` maps modules/blueprint to forge-kernel URLs ✓
- Changes:
  - `forge-documentation/forging-your-own.html` fully ported from markdown with human "builder" tone; no `file:line` citations, no file counts.
  - Sections (9 anchors): `#what-is-forge`, `#repositories` (kernel/kernel-registry/blueprints/kernel-module-registry/installer/forge + optional docs/forge-schemas), `#package-sources` (git/sftp/ftp/http/local/network; `source_list.php` framed as package-manager capability config, auto-created), `#trusted-sources` (apt/yum/pacman, `trusted_sources.json`), `#installer` (`BLUEPRINT_REPO_BASE_URL`), `#install-php` (`FRAMEWORK_REPO_URL`), `#blueprint-updates`, `#prefix-rename`, `#done`.
  - Kept "You're not a user. You're a builder." tone; "MIT licensed. Take what helps." footer note.
- Exit criteria met: URLs/env keys (`GITHUB_TOKEN` via `env('GITHUB_TOKEN')` matching module default) match source. Note: `FORGE_DEVELOPER_MODE` registry commands omitted from this page — they're package-manager/dev-tool domain, not the forking/rebranding story; correctly scoped out per kernel/capability-focus guardrail.

**Phase 1 Exit Gate:**
- All 4 new pages render with shared nav/footer, links validated via `grep -r href forge-documentation/*.html`, no kernel feature misrepresented as built-in.

---

## Phase 2 - Capabilities Re-Promotion

> Why second: Once Kernel story is clean, we fix the biggest framework smell: Capabilities are second-class.

### Phase 2.0 - Pre-flight: Capabilities audit

- Status: `DONE` (2026-08-27)
- Pre-flight check (all verified from source):
  - Disk truth inventoried from all 35 `*Module.php` entry files (1 in `capabilities/`, 34 in `modules/`) with `#[Module(...)]` metadata (name/version/type/order/core/tags). `capabilities/` currently contains only `ForgeHtmx` (migrated primitive); all others still live in `modules/` (loader treats both roots identically per `forge_structure.php` `modules_root`/`modules_namespace`).
  - Docs compared: `forge-documentation/modules.html` cards (lines 402-731) + the 14 `forge-*.html` pages on disk.
- **AUDIT RESULT — real capabilities on disk (35):**
  - `capabilities/` (migrated): ForgeHtmx (tool).
  - `modules/` (not yet migrated): AppAuth, ForgeAdminConsole, ForgeAppAuth, ForgeAuth, ForgeBilling, ForgeComponents, ForgeDatabaseSQL(core), ForgeDebugBar, ForgeDeployment, ForgeErrorHandler(core), ForgeEvents, ForgeHub, ForgeLanding, ForgeLanguage, ForgeLogger, ForgeMarkDown, ForgeMultiTenant, ForgeNotification, ForgePackageManager, ForgeRouter(core), ForgeSaas, ForgeSockets, ForgeSprinkle, ForgeSqlOrm(core), ForgeStaticGen, ForgeStaticHtml, ForgeStorage, ForgeTailwind, ForgeTemplates, ForgeTesting, ForgeUi, ForgeView(core), ForgeWelcome, ForgeWire.
- **Capability classifications (user-confirmed 2026-08-27):**
  - **Not ready / skip for now:** `ForgeStaticGen`, `ForgeStaticHtml` (both `isCli: true`, type `html`). Don't feature or promote; leave out of the catalog for now.
  - **Primitives / true capabilities:** `ForgeAuth` = the real auth capability you use behind the scenes (auth/authorization building blocks your app depends on). Not a turnkey app itself.
  - **Business implementations (built ON the capability):** `ForgeAppAuth` (distributable auth business implementation with login/register/forgot/reset atop ForgeAuth), `AppAuth` (application auth business implementation). These are the concrete business-layer implementations, not the underlying capability. Frame as such: ForgeAuth = the engine, ForgeAppAuth/AppAuth = real implementations using it.
  - **UI component library examples:** `ForgeUi` + `ForgeComponents` are examples of how you'd build a UI component library using the modular approach — instructive, not core.
  - **New / recently added:** `ForgeSockets` (WebSocket primitives), `ForgeSprinkle` (tiny HTML attribute enhancements). Treat as new primes; verify currency.
- **Findings / flags (for Phase 2.1):**
  1. **ForgeRouter & ForgeView are missing from the catalog:** both are core capabilities on disk (`type: core`) with NO card in `modules.html` and NO `forge-router.html`/`forge-view.html` page. `core-concepts.html:254-256,470-484,733-734` references them but with STALE namespaces (`App\Modules\ForgeRouter\...` — real is `Modules\ForgeRouter\...`). → Phase 2.2 must create these pages.
  2. **ForgeNexus is a phantom — do NOT add:** carded in `modules.html:591-593` but NO such module exists on disk, and it is intentionally not part of the stack. Remove the card and do not reintroduce it in any Phase 2.1/2.2 catalog work.
  3. **ForgeUI name mismatch:** on disk `ForgeUi` (dir `modules/ForgeUi/`, class `ForgeUIModule`); docs card says `ForgeUI` and links to `forge-ui.html` which does NOT exist (broken link — the earlier `MISSING: forge-ui.html`). → normalize to `ForgeUi`, decide page. (Also note ForgeUi + ForgeComponents are UI-lib *examples* per above, so frame accordingly.)
  4. **Carded but NO page (broken/pointing nowhere):** ForgeHub, ForgeLogger, ForgeMarkDown, ForgeNotification, ForgeStaticGen*, ForgeStaticHtml*, ForgeStorage, ForgeUi. (* = skip, not ready.)
  5. **On disk but uncarded:** ForgeRouter, ForgeView, ForgeComponents, ForgeBilling, ForgeSaas, ForgeSockets(new), ForgeSprinkle(new), ForgeTemplates, ForgeWelcome, ForgeAdminConsole, ForgeAppAuth(example), AppAuth(example), ForgeLanding.
  6. **Duplicate cards:** ForgeDeployment (modules.html:430-502), ForgeUI (modules.html:449-508) — remove one each.
  7. Other on-disk modules with NO docs presence at all: AppAuth(example), ForgeComponents(ui-example), ForgeBilling, ForgeSaas, ForgeSockets(new), ForgeSprinkle(new), ForgeTemplates, ForgeWelcome, ForgeAdminConsole, ForgeAppAuth(example), ForgeLanding, ForgeRouter, ForgeView.
- Note: `kernel-module-registry/modules.json` not present as a separate repo dir in this checkout (registry accessed via `config/registry.php` pointing at `forge-kernel/kernel-module-registry` GitHub URL), so no manifest cross-check possible locally this pass; relied on disk entry files.
- Changes: This is the audit; the actual catalog rebuild (rename `modules.html` → `capabilities.html`, dedupe, taxonomy grouping, glossary) is Phase 2.1. All findings above feed Phase 2.1/2.2.

### Phase 2.1 - `capabilities.html` (rename + clean catalog)

- Status: `DONE`
- Pre-flight check: Same as 2.0 + `kernel/Core/Module/ModuleLoader.php` (how `type`/`order` works)
- Changes:
  - Copy `modules.html` -> `capabilities.html`; keep `modules.html` as ` <meta http-equiv="refresh" content="0; url=capabilities.html">`.
  - Fix duplicates: deduplicate `ForgeDeployment` (`modules.html:430-502`), deduplicate `ForgeUI`, fix `ForgeWire` tags (`modules.html:477-482` currently shows Auth tags), ensure each card links to real `forge-*.html` (or stub).
  - Taxonomy grouping: `Foundation (ForgeRouter, ForgeView, ForgeDatabaseSQL, ForgeSqlOrm)` | `Application (ForgeAuth, ForgeStorage, ForgeEvents, ForgeLanguage, ForgeMultiTenant)` | `Frontend (ForgeWire, ForgeHtmx, ForgeTailwind)` | `DevOps/Tooling (ForgePackageManager, ForgeDeployment, ForgeTesting, ForgeDebugbar, ForgeErrorHandler)`.
  - Add glossary callout: **Capability vs Module - Semantics, Namespace & Choice** - capability = primitive/building block recommended in `capabilities/` (`Capability\` namespace, e.g. `ForgeHtmx`) vs module = app feature recommended in `modules/` (`Modules\` namespace). Note gradual migration: primitives still in `modules/` until moved, `capabilities/ForgeHtmx` is the reference; loader treats both roots identically (`forge_structure.php:32-33`). Add explicit "Not enforced - your conventions or mixed layout is fine" (per 2026-08-27 clarification).
  - Sidebar `modules.html:141-189` -> update to `capabilities.html`, fix anchors.
- Exit criteria: Catalog matches disk truth; no duplicate cards; all 14 `forge-*.html` links resolve.

### Phase 2.2 - Rewrite Catalog Capability Pages (app-assembly framing)

- Status: `IN PROGRESS`
- Scope (user directive 2026-08-27): Every capability currently in the `capabilities.html` catalog gets its own dedicated page rewritten to the correct framing — **you are building your app**, the capability is a component you selected (or wrote) for that app; it is NOT taught as a framework feature. Run through each catalog page, one sub-phase per page.
- Common framing for every page (apply to all sub-phases):
  - Open with "who this is for / when to reach for it" and an install prerequisite (`php forge.php package:install-module --module=ForgeX`).
  - Frame the capability as a component of *your* app, not a step in a framework's implied stack. The app (your business modules / `App\`) is the product; the capability is the material.
  - Cite capability source, not kernel. No `file:line` in rendered page, no file counts, no "Verified against" headers. `break-words`/`overflow-hidden` on cards.
  - New/missing pages (ForgeRouter, ForgeView) are created within this wave using the `forge-database-sql.html` shell as template, and Router/View content is moved out of `core-concepts.html` / `getting-started.html`.
- Sub-phases (add/rename here, catalog order = Foundation → Application → Frontend → Tooling → Examples):
  - 2.2.0 `forge-database-sql.html` (catalog's first capability) — DONE
  - 2.2.1 `forge-sql-orm.html` — DONE
  - 2.2.2 `forge-error-handler.html` — DONE
  - 2.2.3 `forge-auth.html` (Application; ForgeAuth = primitives) — DONE
  - 2.2.4 `forge-events.html` (Application; events/queue) — DONE
  - 2.2.5 `forge-language.html` (Application; localization) — DONE
  - 2.2.6 `forge-multi-tenant.html` (Application) — DONE
  - 2.2.7 `forge-wire.html` (Frontend; reactive controller-rendering protocol, NOT Livewire-like) — DONE
  - 2.2.8 `forge-htmx.html` (Frontend; lives in `capabilities/ForgeHtmx`) — DONE
  - 2.2.9 `forge-tailwind.html` (Frontend) — DONE
  - 2.2.10 `forge-testing.html` (Tooling; `test` command, attribute-driven runner) — DONE
  - 2.2.11 `forge-debugbar.html` (Tooling; debug bar, tracked from source) — DONE
  - 2.2.12 `forge-deployment.html` (Tooling; automated provisioning + incremental deploy) — DONE
  - 2.2.13 `forge-storage.html` (Application; file storage, new page created) — DONE
  - 2.2.14 `forge-notification.html` (Application; multi-channel notifications, new page created) — DONE
  - 2.2.15 `forge-logger.html` (Tooling; structured logging, new page created) — DONE
  - 2.2.16 `forge-hub.html` (Tooling; administration hub, new page created) — DONE
  - 2.2.17 `forge-templates.html` (Application; message/notification template composer, new page created) — DONE
  - 2.2.18 `forge-saas.html` (Tooling; SaaS plans/subscriptions/feature gating built on ForgeMultiTenant, new page created) — DONE
  - 2.2.19 `forge-billing.html` (Application; billing portal with plans/invoices/payment providers, new page created) — DONE
  - 2.2.20 `forge-sprinkle.html` (Frontend; tiny progressive-enhancement JS lib added to page, new page created) — DONE
  - 2.2.21 `forge-sockets.html` (Frontend/networking; RFC 6455 WebSocket primitives + socket:serve worker, new page created) — DONE
  - 2.2.22 `forge-markdown.html` (Tooling; lightweight markdown-to-HTML processor, new page created) — DONE
  - 2.2.23 `forge-admin-console.html` (Tooling; protected admin console completing the auth implementation, new page created) — DONE
  - 2.2.24 `forge-view.html` (Foundation; the view engine: plain PHP views, layered/parent layouts, layout props/sections/slots, reusable components; StructureResolver-driven paths) — DONE
  - 2.2.25 `forge-router.html` (Foundation; HTTP routing + middleware, requests/responses, sessions/CSRF, security/hardening, hooks, commands; the last core Foundation capability on disk without a page) — DONE
  - 2.2.26 Catalog restructure: split capabilities into a rich marketplace/directory `catalog.html` (new) + a concepts-only `capabilities.html` (what/why/how + capability vs module reference) — DONE
  - 2.2.27+ continue per catalog entries (remaining "Not listed" ForgeStatic callout + any unlinked Application/Tooling entries)
- Exit criteria: Every catalog capability links to a real, correctly-framed dedicated page; no capability concept taught as framework machinery; stale namespaces fixed.

### Phase 2.2.0 - `forge-database-sql.html` rewrite (first capability page)

- Status: `DONE`
- Pre-flight check:
  - Capability source: `modules/ForgeDatabaseSQL/` (or `capabilities/`) — connection classes, driver support, config, `#[Module]` metadata (`type: core`).
  - Current page: `forge-documentation/forge-database-sql.html` (existing, needs rewrite to app-assembly framing).
  - `forge-documentation/forge-sql-orm.html` for cross-reference (ORM builds on this).
- Changes:
  - Rewrite to app-assembly framing: SQL support as a component of your app, not a framework layer. Install prerequisite, drivers (SQLite/MySQL/PostgreSQL), connection config (`.env` keys `CACHE_DRIVER` etc. as available), how your app queries, migration to the real capability source.
- Exit criteria: Page reads as "a component your app uses," not framework machinery; all facts verified against `ForgeDatabaseSQL` source; HTTP 200 + anchors resolve.

**Phase 2.2.0 Verification (2026-08-27):**
- Rewrote `forge-database-sql.html` 2151 lines → 642 lines with app-assembly framing: intro = "a component that gives *your app* SQL database support"; sections Overview, When to Reach for It, Installation, Configuration, Using the Connection, Migrations, Seeders, Database Sessions; App-vs-module scoping called out. `db:` commands frame alongside `--type=app|module|all` (your app / a module), never as framework machinery.
- Facts verified from source `modules/ForgeDatabaseSQL/`:
  - Env keys: `DB_DRIVER/DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS/SQLITE_PATH/SQLITE_DB` (`DB/DatabaseSetup.php:48-55`), `SESSION_DRIVER=database` (`ForgeDatabaseSQLModule.php:79`).
  - Commands all `#[CoreCommand]` (no prefix): `db:migrate`, `db:migrate:rollback`, `db:seed`, `db:seed:preview`, `db:seed:rollback` (`Commands/*.php`). `db:migrate --type` validates `app|module|all` (NO `kernel` scope — old page's `--type=kernel` removed). Rollback flags `--steps/--batch/--type/--group`.
  - Real namespace `Modules\ForgeDatabaseSQL\...` (old page used stale `App\Modules\ForgeDatabaseSQL\...` — all fixed). Base `Migration` (`DB/Migrations/Migration.php`), `Seeder`+`#[AutoRollback]` (`DB/Seeders/Shaker.php` area), attrs `#[Table]/#[Column]/#[GroupMigration]`, enum `ColumnType`.
  - Module metadata `type: core, order: 0`, provides `DatabaseConnectionInterface`; MySQL/PostgreSQL/SQLite via `DB/Drivers/PdoDatabaseDriver.php`; migrations folder paths resolved via `StructureResolver` (so folder examples left generic).
- `python3 -m http.server 8017` → 200; all 8 body anchors (`#overview #when-to-use #installation #configuration #using-the-connection #migrations #seeders #database-sessions`) resolve to defined sections; no unescaped `<?php`; no `App\Modules`; related links (`forge-sql-orm.html`, `capabilities.html`) resolve.

### Phase 2.2.1 - `forge-sql-orm.html` rewrite

- Status: `DONE`

**Phase 2.2.1 Verification (2026-08-27):**
- Rewrote `forge-sql-orm.html` 1540 lines → ~590 lines with app-assembly framing: intro = "a component that gives *your app* an object-style layer over your SQL database"; sections Overview, When to Reach for It, Installation, Models, Queries, Saving & Deleting, Casts & Relationships, Soft Deletes, Repositories & Caching, Pagination. ORM explicitly framed as optional (skip it if you prefer raw SQL / the ForgeDatabaseSQL connection).
- Facts verified from source `modules/ForgeSqlOrm/`:
  - Module metadata `type: core, order: 1`, requires `DatabaseConnectionInterface` (ForgeDatabaseSQL), provides `QueryBuilderInterface` (`ForgeSqlOrmModule.php:22-25`).
  - Base `Modules\ForgeSqlOrm\ORM\Model`: attrs `#[Table]/#[Column]/#[Hidden]` (`Column` fields `primary/cast/hidden`, `Table` field `name`); API `query/orderBy/latest/oldest/paginate/save/delete/toArray/table/fromRow/softDeleteColumn/setRelation`; UUID auto-gen for string PK, auto `created_at`/`updated_at`, `formatForDatabase` UTC.
  - `ModelQuery` methods verified: `get/first/id/where/whereIn/whereNotIn/whereNull/whereNotNull/whereRaw/with/orderBy/latest/oldest/limit/offset/count/insert/insertMany/update/softDelete/forceDelete/withTrashed/onlyTrashed/paginate`.
  - Cast enum: INT/FLOAT/BOOL/STRING/JSON/DATE/DATETIME/TIMESTAMP/ENUM. Soft deletes via `Traits/SoftDeletes` (adds `#[Column(cast: Cast::DATETIME)] ?DateTimeImmutable $deleted_at`); trait used as column marker.
  - Repository interface: create/update/delete/find/findBy/findAll/query. `QueryCache` singleton in container (default TTL 3600s, get/set/forget/invalidate). Pagination helpers `pagination()/paginate()/pagination_info()` from `Support/helpers.php` (`PaginationHelper::render/info`).
- `python3 -m http.server 8018` → 200; all 10 body anchors resolve; no literal `<?php`/`<?=` (fixed-string grep = 0); all same-origin `.html` links resolve.

### Phase 2.2.2 - `forge-error-handler.html` rewrite

- Status: `DONE`

**Phase 2.2.2 Verification (2026-08-27):**
- Rewrote `forge-error-handler.html` 1038 lines → 535 lines with app-assembly framing: intro = "a component that handles errors in *your app*"; sections Overview, When to Reach for It, Installation, How It Works, Debug vs Production, API Requests, Error Logging, Writing Your Own. Framed as a component plugged into the router's error contract (not kernel machinery), with a "Writing Your Own" section showing an app can implement the same interface.
- Facts verified from source `modules/ForgeErrorHandler/`:
  - Module metadata `core: true, type: core, order: 2`; binds `ErrorHandlerInterface` → `ForgeErrorHandlerService` (`ForgeErrorHandlerModule.php:33-36`).
  - Contract is `Modules\ForgeRouter\Contracts\ErrorHandlerInterface` with `handle(Throwable $e, Request $request): Response` — so it plugs into the web layer, not the bare Kernel.
  - `ForgeErrorHandlerService`: registers `set_error_handler`/`set_exception_handler`/`register_shutdown_function`; errors→`ErrorException`; shutdown catches fatals (E_ERROR/E_PARSE/E_CORE_ERROR/E_COMPILE_ERROR); CLI routes to `CliErrorHandler` + exit 1.
  - Debug from `Environment::isDebugEnabled()` (`APP_DEBUG`); debug→rich page w/ code snippets + filtered trace + request/session data, or pretty JSON for API; production→friendly page (`ErrorPageRenderer::render(500)`) or minimal 500 JSON. API detection: Accept/Content-Type `application/json` or URI `/api` prefix.
  - Logging: PSR-3 logger if it has `error`/`debug`, else `storage/logs/errors.log`; context includes fingerprint, request_id, exception, message, code, file, line, trace, memory, duration_ms, source (ip/method/uri or cli argv), sapi, user_agent, masked session/get/post; rate-limited per fingerprint 300s. Masks keys `password/token/secret/authorization/cookie`.
  - Removed broken `forge-router.html` Related link (page not yet created — future sub-phase); swapped to `capabilities.html` + `core-concepts.html`.
- `python3 -m http.server 8019` → 200; all 8 body anchors resolve; no literal `<?php`/`<?=`; all same-origin `.html` links resolve.

### Phase 2.2.3 - `forge-auth.html` rewrite

- Status: `DONE`

**Phase 2.2.3 Verification (2026-08-27):**
- Rewrote `forge-auth.html` 1469 lines → 656 lines with app-assembly framing centered on the (2026-08-27) classification: **ForgeAuth = auth primitives/engine your app builds on, NOT a turnkey login**. Sections: Overview, "Primitives, Not a Login", Installation, The User Provider, The Auth API, The Current User, Roles & Permissions, Middleware & Guards, Configuration, Beyond ForgeAuth. Explicitly separates the engine from the business-layer impls (ForgeAppAuth/AppAuth) referenced by name, no links (no pages).
- Facts verified from source `modules/ForgeAuth/`:
  - Module `type: auth, order: 99`, provides `ForgeAuthInterface` → `ForgeAuthService`; requires `forge-database-sql` + `forge-sql-orm`; PostInstall/PostUninstall run `db:migrate --type=module --module=ForgeAuth` (`ForgeAuthModule.php`).
  - `ForgeAuthInterface` = `register(array): bool`, `login(array): AuthUserInterface`, `logout(): void`. `AuthUserInterface` = `getId/getIdentifier/getEmail`. `UserContextInterface` = `current/isAuthenticated/setCurrentUser`. `UserProviderInterface` = `findById/findByIdentifier/findByEmail/verifyCredentials/createUser/paginate` — the app supplies user impl.
  - Roles/permissions: `Role` enum (ADMIN/USER), `Permission` enum (USER_WRITE/USER_READ/USER_DELETE/LOGS_READ/HUB_PERMISSIONS...), `RoleService` (createRole/deleteRole/addPermissionToRole/getUserRoles...), `PermissionService`, helpers `can/canAny/canAll/cannot/hasRole/hasRoleEnum/isOwner/getAllUserPermissions`; `HasRoles`/`HasCurrentUser` traits, `RequiresPermission` attr.
  - Middlewares: `PermissionMiddleware`, `RoleMiddleware`, `ApiJwtMiddleware`, `ApiKeyMiddleware`; `JwtService` (encode/decode), `ApiKeyService`, `TokenManagerService`.
  - Config env keys: `FORGE_JWT_ENABLED/SECRET/TTL/REFRESH_TTL`, `FORGE_PASSWORD_COST/MAX_LOGIN_ATTEMPTS/LOCKOUT_TIME/MIN_PASSWORD_LENGTH/MAX_PASSWORD_LENGTH`, `FORGE_AFTER_LOGIN_REDIRECT/FORGE_AFTER_LOGOUT_REDIRECT`.
  - Commands registered via `RegistersCommands` get the literal prefix `modules:` (no module-name segment auto-added). ForgeAuth commands are `command: "auth:..."`, so the real invocations are `modules:auth:role:create`, `modules:auth:role:add-permission`, `modules:auth:user:add`, etc. (NOT `modules:forge-auth:...`).
- `python3 -m http.server 8020` → 200; all 10 body anchors resolve (sidebar hrefs match exactly); no literal `<?php`/`<?=`; all same-origin `.html` links resolve.
- Pre-flight check:
  - Capability source: `modules/ForgeAuth/` — auth primitives/engine (confirmation, password verification, sessions, guards), per user classification (2026-08-27), NOT a turnkey login. `ForgeAppAuth` + `AppAuth` are the business-layer implementations built ON it.
  - Current page: `forge-documentation/forge-auth.html`.
- Changes:
  - Rewrite to app-assembly framing: ForgeAuth = the underlying auth primitives your app (or a business module) builds on — not a login page out of the box. Distinguish primitives vs the `ForgeAppAuth`/`AppAuth` business implementations (link/refer, don't conflate).
- Exit criteria: Page reads as "auth engine/primitives your app uses"; facts verified against `ForgeAuth` source; HTTP 200 + anchors resolve.

### Phase 2.2.4 - `forge-events.html` rewrite

- Status: `DONE`

**Phase 2.2.4 Verification (2026-08-27):**
- Rewrote `forge-events.html` 1112 lines → 606 lines with app-assembly framing (a communication component *your app* uses to decouple + defer work; not framework machinery). Sections: Overview, "When to Reach for It", Installation, Defining an Event, Listeners, Dispatching, Queued Work, Queue Drivers, Running the Worker, Configuration.
- Facts verified from source `modules/ForgeEvents/`:
  - Module `name: ForgeEvents, version: 1.4.17, type: 'communication', order: 99`; `#[Requires(module: 'forge-database-sql')]` (`ForgeEventsModule.php`).
  - Binds `EventDispatcher` as `Forge\Core\Contracts\EventDispatcherInterface`; auto-scans app + module `events/`/`listeners/` dirs for `#[EventListener]` methods (`register()` + `scanDirectory()`).
  - Attributes: `#[Event(queue, maxRetries, retryDelay, delay('10m'/'30s'/'2h'), priority)]`, `#[EventListener(EventClass)]`, `#[Queue(name, maxRetries, retryDelay, priority)]`. `QueuePriority` enum HIGH=3/NORMAL=2/LOW=1. Example `CacheRefreshEvent` uses `#[Event(queue: 'cache_refresh', maxRetries: 3, delay: '0s', priority: LOW)]`.
  - Dispatcher API: `dispatch(object)`, `dispatchDelayed(object, int ms)`, `addListener`, `currentJobId`, `writeJobMetadata`, `failJob`, `release`, `getNextJobDelay`.
  - `QueueInterface`: `push/pop/count/clear/release/getNextJobDelay`; drivers `DatabaseQueue`/`FileQueue`/`InMemoryQueue`.
  - Config env: `QUEUE_DRIVER` (default `database`), `QUEUE_LIST` (default `['default']`) → `forge_events.queue_driver`/`queue_list` config.
  - Worker command: `#[Cli(command: 'queue:work')]` with `--workers` and `--queues` args (overrides QUEUE_LIST); registered via `RegistersCommands` → real invocation `modules:queue:work`. QueueWorkerService manages/restarts workers + tails output.
  - **Cron-like recurring jobs (2026-08-27, user-directed deep check):** ForgeEvents has no cron parser; "cron-like" events are **self-rescheduling events**. A listener does its work then re-dispatches the same event via `dispatchDelayed($event, $delayMilliseconds)`, creating a repeating cadence. Confirmed in `EventDispatcher.php:88-105` docblock ("Used by self-rescheduling jobs (e.g. the maintenance/cron runner)"). Supporting primitives: `currentJobId()`, `writeJobMetadata()`, `failJob()`, `release()`, `getNextJobDelay()`. Scheduled (future `process_at`) jobs show status `scheduled` in `QueueHubService::getJobStatus()`. No scheduler/cron in kernel `kernel/Core` (grep clean). Added a "Recurring & Scheduled Jobs" section + example to `forge-events.html` (new `recurring-jobs` anchor). Re-verified: 200, all 11 anchors resolve, no literals.
- **Command-prefix correction (2026-08-27):** re-verified `RegistersCommands` passes literal `'modules:'` prefix (`kernel/Core/Module/Traits/RegistersCommands.php:25` + `kernel/CLI/Application.php:118-135`); no module-name segment auto-added. ForgeAuth commands are `command: "auth:..."` → actual `modules:auth:...` — fixed `forge-auth.html` (was wrong `modules:forge-auth:...`) and corrected the 2.2.3 note.
- `python3 -m http.server 8030` → 200; all 10 body anchors resolve; no literal `<?php`/`<?=`; same-origin `.html` links resolve.

### Phase 2.2.5 - `forge-language.html` rewrite

- Status: `DONE`

**Phase 2.2.5 Verification (2026-08-27):**
- Rewrote `forge-language.html` 222 lines → 537 lines with app-assembly framing (a localization component *your app* uses to translate copy + let visitors switch language, not framework machinery). Sections: Overview, "When to Reach for It", Installation, Translation Files, Choosing the Language, Helpers, Configuration, Language Switcher. Fixed stale namespace (was `App\Modules\ForgeLanguage\...` → real `Modules\ForgeLanguage\...`) and added `language_switcher_url()` helper and language-resolution order.
- Facts verified from source `modules/ForgeLanguage/`:
  - Module `name: ForgeLanguage, version: 0.2.10, order: 40`; `#[Requires(module: 'forge-router')]` (hooks `RouterHookName::BEFORE_REQUEST`); `#[ConfigDefaults]` gives `forge_language.languages` (with `label` + `flag` keys) + `default`. No env keys — configuration is via the `forge_language` config array (`ForgeLanguageModule.php`, `LanguageService.php`).
  - `LanguageService` API: `current()`, `set($lang)` (throws `InvalidArgumentException` if unsupported, stores in session), `available()`, `language($code)`, `isSupported($lang)`, `term($key,$fallback,$args,$language)` (nested dot-notation + `ModuleResourceResolver` module-key + `/` file-path support, callable terms, fallback).
  - Resolution order (`resolveLanguage`): `?lang=` query → session → cookie → browser `Accept-Language` → default.
  - Term files: app `languages/{lang}.php` + module `languages/{lang}.php`, returning arrays (`loadLanguage`/`resolveLanguagePath`); app loaded first, module overrides.
  - Helpers (`Support/helpers.php`): `languageTerm()`, `current_language()`, `available_languages()`, `language_switcher_url($code)`.
  - UI: `language-switcher` view component + `LanguageSwitcherDefinition` (showFlags/showLabels/showCodes/showCurrent/wrapperClass/itemClass/activeClass).
- `python3 -m http.server 8050` → 200; all 8 body anchors resolve; no literal `<?php`/`<?=`; all same-origin `.html` links resolve (No `forge-router.html` link — page not created).

### Phase 2.2.6 - `forge-multi-tenant.html` rewrite

- Status: `DONE`

**Phase 2.2.6 Verification (2026-08-27):**
- Rewrote `forge-multi-tenant.html` 1200 lines → 617 lines with app-assembly framing (a multi-tenancy component *your app* uses to serve many customers from one codebase; not framework machinery). Sections: Overview, "When to Reach for It", Installation, Strategies, How a Tenant Resolves, Working With Tenants, Scoping Data, Central vs. Tenant Routes, CLI Commands, Configuration.
- Facts verified from source `modules/ForgeMultiTenant/`:
  - Module `name: ForgeMultiTenant, version: 0.4.9, type: 'multi-tenant', order: 2`; requires `forge-database-sql` + `forge-router` (`ForgeMultiTenantModule.php`).
  - Registers middleware (via `ForgeRouterModule::registerMiddleware`): `TenantMiddleware` (web, prio 1), `ScopeMiddleware` (web, 2), `TenantAwareRateLimitMiddleware` (global, 1, extends RateLimitMiddleware), `TenantAwareCircuitBreakerMiddleware` (global, 2), `TenantAwareApiKeyMiddleware` (api, 100).
  - `Strategy` enum: `column`/`view`/`database`. `Tenant` DTO: `id` (CHAR 36), `domain`, `subdomain`, `strategy`, `dbName`, `connection`. `tenants` table indexed on domain + subdomain.
  - `TenantManager`: `resolveByDomain($host)`, `current()`, `tenantId()`, `all()`, `find($id)`, `clearCache()`. `CentralDomain` handles central domain + `isLocal()` dev hosts. Unknown tenant → `unknown_tenant_page`/`unknown_tenant_view`.
  - Helpers (`Support/helpers.php`): `tenant()`, `get_tenant_id()`, `requireTenant()` (throws `TenantNotFoundException`), `tenant_url($scheme)`.
  - Scoping: `TenantScopedTrait` (`newQuery()` → `TenantQueryRewriter::scope()`), `TenantScoped` marker attr, `TenantSchema::addTenantColumn()` adds `tenant_id CHAR(36)`. `ScopeMiddleware` reads `TenantScope` attr (`CENTRAL='central'`/`TENANT='tenant'`); `RouteScopeFilter`.
  - Connection switching: `TenantConnectionFactory::forTenant()` + `TenantMiddleware` swaps `DatabaseConnectionInterface`/`PDO`/`QueryBuilderInterface` + `TenantSessionProxy` + `TenantCacheProxy`.
  - Commands: `tenant:list`, `tenant:migrate` (`--tenant` default 'all', `--preview`), `tenant:seed` (same). Registered via `RegistersCommands` → real CLI `modules:tenant:...`. PostInstall: `db:migrate`+`db:seed` (module) then `tenant:migrate`+`tenant:seed`.
  - Config env keys (`forge_multi_tenant.*`): `FORGE_MULTI_TENANT_CENTRAL_DOMAIN` (default `forge.localhost`), `FORGE_MULTI_TENANT_UNKNOWN_PAGE`, `FORGE_MULTI_TENANT_UNKNOWN_VIEW`.
- `python3 -m http.server 8060` → 200; all 10 body anchors resolve; no literal `<?php`/`<?=`; same-origin `.html` links resolve (incl. forge-database-sql + forge-sql-orm prerequisites; NO forge-router.html link — page not created).

### Phase 2.2.7 - `forge-wire.html` rewrite

- Status: `DONE`

**Phase 2.2.7 Verification (2026-08-27):**
- Rewrote `forge-wire.html` 2339 lines → 733 lines with app-assembly framing. Sections: Overview, "How the Protocol Works", Installation, The Reactive Controller, State/Computed/Actions, Validation, Islands in the View, Responding, Polling, Security, Configuration, CLI Commands.
- **CRITICAL reframe (user-directed):** ForgeWire is NOT like Livewire (except the shared "-wire" name ending). The old page wrongly described it as "Lightweight Livewire-like reactive components" / "Livewire-like reactivity". Verified against source: `#[Module(description: 'A reactive controller rendering protocol for PHP', type: 'reactive', order: 99)]` — it is a **reactive controller-rendering protocol**, server-driven, no browser-mounted component lifecycles, no whole-page swaps. Removed ALL "Livewire" mentions from the page (grep = 0). The one "component framework" phrase is a deliberate clarification that it is NOT one.
- Facts verified from source `modules/ForgeWire/`: requires `forge-router`; registers `ForgeWireMiddleware` (web, prio 100); binds via traits.
  - Attributes: `#[Reactive]` (TARGET_CLASS), `#[State]` (TARGET_PROPERTY, optional `shared`), `#[Computed]` (TARGET_METHOD), `#[Action]` (TARGET_METHOD, optional `submit`), `#[Validate(rules, messages)]` (TARGET_PROPERTY, array or pipe-string rules).
  - Traits: `ReactiveEndpointHelper` (composes `WithWireResponse`) → `redirect($url,$delay)`, `flash($type,$msg)`, `dispatch($event,$data)`, `isWireRequest()`, `isReactive()`, `cacheComputed()`; `WireHelper` = component registry/cleanup helper.
  - Protocol: wire request = POST `/__wire` with `X-ForgeWire` header; state hydrated from session via `Hydrator` (shared state via `SharedStateManager`); only `#[Action]` methods callable (`ActionDispatcher`); checksum-signed state (`Security/Checksum`) + CSRF; response verbs from `ForgeWireResponse` (redirect/flash/events, targeted HTML). `WireKernel::process()`.
  - Islands: `fw_id()` helper marks reactive boundaries (required for reactivity); multiple islands per page. Client directives (`UI/assets/js/forgewire.js`): `fw:id`, `fw:controller`, `fw:action`, `fw:click`, `fw:keydown`, `fw:submit`, `fw:model`, `fw:param`, `fw:depends`, `fw:event`, `fw:poll`, `fw:loading`, `fw:validation`, `fw:checksum`. Polling via `fw:poll.Ns` + IntersectionObserver (pauses off-screen). Optimistic updates via `ForgeWire.optimistic()`. Client security: event-name regex, CSS-selector escaping, same-origin redirect restriction, CSRF.
  - Commands: `forgewire:cleanup`, `forgewire:minify` → CLI `modules:forgewire:cleanup` / `modules:forgewire:minify`.
  - Config env: `FORGE_WIRE_USE_MINIFIED` (default true), `FORGEWIRE_COMPONENT_TTL_SECONDS` (default 1800). Module config `forge_wire.use_minified/stale_threshold=200/component_ttl_seconds`. PostInstall `asset:link --type=module --module=forge-wire` for JS asset.
  - Trait names corrected in examples: `Modules\ForgeWire\Traits\ReactiveEndpointHelper` (old page used `EndpointHelper`).
- `python3 -m http.server 8070` → 200; all 12 body anchors resolve; no literal `<?php`/`<?=` (8 intentional `&lt;?` HTML-escaped in code samples); same-origin `.html` links resolve (NO forge-router.html link).
- **Shared state between islands (2026-08-27, user-directed deep dive + addition):** Added a "Shared State Between Islands" section (new `shared-state` anchor; page now 812 lines, 13 anchors). Key verified fact: because the controller is the single source of truth, islands don't need events to share state — they're views of the same server-side state, and the server keeps them in sync. Mechanics (from `modules/ForgeWire/src/`):
  - `#[State(shared: true)]` marks a property stored once at controller level; any island reads it.
  - After each action the kernel dehydrates controller state to session (`WireKernel.php`), detects shared-state changes (`SharedStateManager::getSharedStateChanges`), finds affected islands (`findAffectedComponents` intersects each component's `uses` i.e. its declared `fw:depends` names against the changed shared keys — `SharedStateManager.php:101-106`), and re-renders them server-side (`renderAffectedComponent`) returning them in the `updates` array.
  - `fw:depends` directive: comma-separated list of shared-state names an island reads; client sends them as the component's `uses` (`DependencyTracker::parseAndStoreUses` / JS `collectDepends`, `forgewire.js:183-207`). Split on commas, allowed on island root or descendants.
  - Verified action-arg syntax is `fw:param-<key>` (hyphen, `forgewire.js:172-180`) — corrected from an initial `fw:param.region` typo in the draft.
  - Re-verified: HTTP 200, all 13 anchors resolve, 0 Livewire mentions, no literals.

### Phase 2.2.8 - `forge-htmx.html` rewrite

- Status: `DONE`

**Phase 2.2.8 Verification (2026-08-27):**
- Rewrote `forge-htmx.html` 968 lines → 550 lines with app-assembly framing (a tool *your app* uses to add htmx to its server-rendered pages; not framework machinery). Sections: Overview, "Integration, Not a Rewrite", Installation, htmx in Your Views, Response Helpers, Rendering Partials, CSRF & Assets.
- **KEY finding:** ForgeHtmx does NOT live in `modules/` — it lives in **`capabilities/ForgeHtmx`** (namespace `Capability\ForgeHtmx`), the reference layout for reusable primitives (catalog line 120 confirms). Source path verified: `capabilities/ForgeHtmx/src/`.
- Facts verified from source:
  - Module `name: ForgeHtmx, version: 1.0.4, type: 'tool', order: 80`; `#[Requires(module: 'forge-router')]` + `#[Requires(module: 'forge-view')]`; PostInstall/PostUninstall `asset:link/unlink --type=module --module=forge-htmx` (`ForgeHtmxModule.php`).
  - Registers `ForgeHtmxMiddleware` (web, prio 2); AFTER_REQUEST hook injects `<script src="/assets/modules/forge-htmx/js/htmx.min.js?v=1" defer>` into `</head>`.
  - Middleware behavior (`ForgeHtmxMiddleware.php` + tests): detects `HX-Request` header (htmx partials) — passes through unchanged; otherwise injects CSRF-config script binding `X-CSRF-TOKEN` header on every htmx request (`htmx:configRequest`). Skips JSON/non-HTML/no-DOCTYPE responses; places script before `</head>`.
  - `HtmxResponseHelper` trait (for controllers): `htmxFragment($html,$status)` (HX body), `htmxRedirect($url)` (HX-Redirect), `htmxRefresh()` (HX-Refresh), `htmxTrigger` / `htmxTriggerAfterSwap` / `htmxTriggerAfterSettle` (HX-Trigger* events, with array/string/detail forms), `htmxLocation($url,$context)` (HX-Location), `htmxPushUrl` (HX-Push-Url), `htmxReplaceUrl` (HX-Replace-Url), `htmxRetarget` (HX-Retarget), `htmxReswap` (HX-Reswap), `htmxStopPolling` (HTTP 286).
  - `HtmxViewHelper` trait: `htmxView($view,$data,$partial)` — on `HX-Request` suppresses layout (`View::suppressLayout(true)`) and returns the partial view only; otherwise full view with layout.
  - **Framing guardrail:** ForgeHtmx does NOT reimplement htmx — the `hx-*` attribute language + DOM swaps are standard htmx client (`htmx.min.js` loaded verbatim); ForgeHtmx only injects the client, keeps CSRF working, detects `HX-Request`, and offers server-side response helpers. Foreign trait `Modules\ForgeView\View`.
- Cross-link added to `forge-wire.html` (Related) — both are Frontend reactive approaches.
- `python3 -m http.server 8090` → 200; all 7 body anchors resolve; no literal `<?php`/`<?=` (code samples HTML-escaped `&lt;?`); same-origin `.html` links resolve (incl. forge-wire.html; NO forge-router.html/forge-view.html links — pages not created).

### Phase 2.2.9 - `forge-tailwind.html` rewrite

- Status: `DONE`

**Phase 2.2.9 Verification (2026-08-27):**
- Rewrote `forge-tailwind.html` 973 lines → 469 lines with app-assembly framing (a build-time tool *your app* uses to compile its Tailwind CSS). Sections: Overview, Installation, Building, Watching & Hot Reload, Binary Management, Paths & Platform Support.
- **KEY finding:** ForgeTailwind is a **CLI-only capability** — `isCli: true`, `type: 'tailwind'`, `order: 99`, `version: 0.2.7` (`ForgeTailwindModule.php`). Registers NO request middleware/static file/runtime services; framing is a build pipeline, not runtime.
- Facts verified from source `modules/ForgeTailwind/`:
  - Module wires `IncludesFiles` + `RegistersCommands`; includes `src/Support/helpers.php`; exposes exactly two commands (`BuildTailwindCommand`, `WatchTailwindCommand`).
  - **CLI command names (declared `tailwind:build` / `tailwind:watch`):** → `modules:tailwind:build` / `modules:tailwind:watch` (literal `modules:` prefix rule confirmed — page uses these).
  - `tailwind:build [--input] [--output]`: default in `app/UI/assets/css/tailwind.css` → default out `public/assets/css/app.css`, runs `-i IN -o OUT --minify`. First run auto-downloads binary to `storage/bin/tailwindcss` (hardcoded `macos-arm64` URL), `mkdir storage/bin`, chmod +x, move temp→bin.
  - `tailwind:watch [--input] [--output] [--platform]`: same + `--watch`; platform map `macos-arm64|x64`, `windows-x64` (.exe), `linux-arm64|x64` + `-musl` variants (7 total); default `macos-arm64`; Windows binary is `tailwindcss.exe`.
  - **Helper `forgetailwind()`** (`Support/helpers.php`): returns `<script defer src="/assets/modules/forge-tailwind/js/forge-tailwind-hmr.js">` ONLY when `APP_HMR` env truthy AND env NOT `production`/`staging` AND host starts `localhost`/`127.0.0.1`; otherwise returns `""`. HMR runtime asset exists at `src/UI/assets/js/forge-tailwind-hmr.js`.
  - App-side (not capability API, not doc): `tailwind.config.js`, `app/UI/assets/css/tailwind.css`, `public/tailwind-watch.php`.
  - Prerequisites: none at runtime (clientless build tool). Binary from official Tailwind GitHub releases `.../latest/download/tailwindcss-<platform>`.
- Cross-link added to `forge-htmx.html` (front-end tooling sibling). Dropped old page's stale "helper-functions"/"comparison"/"architecture" drift; now purely the two commands + helper + binary mgmt + paths/platform.
- `python3 -m http.server 8091` → 200; all 6 body anchors resolve; no literal `<?php`/`<?=`; same-origin `.html` links resolve; commands grep as `modules:tailwind:build`/`watch`.

### Phase 2.2.10 - `forge-testing.html` rewrite

- Status: `DONE`

**Phase 2.2.10 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeTesting/` — module `name: ForgeTesting, version: 0.4.9, type: 'testing', order: 9999, isCli: true, #[Compat framework: '>=0.1.20']`; registers exactly 1 command (`TestCommand`).
- **Command is `test`** (`#[CoreCommand]` → NO `modules:` prefix). Options: `--type=app|kernel|module` (default `app`), `--module=MODULE` (default `all`), `--group=GROUP`.
- Attributes (all `Modules\ForgeTesting\Attributes\*`): `Test(?description)`, `BeforeEach`, `AfterEach`, `DataProvider(methodName)`, `Depends(testMethod)`, `Group(name)`, `Skip(reason)`, `Incomplete(reason)`. `Test`, `Skip` target class+method; `Group` class+method; `BeforeEach/AfterEach/DataProvider/Depends/Incomplete/Test` target method. Only `#[Test]` methods run.
- `TestCase` composes `Assertions` + `DatabaseTesting` + `PerformanceTesting` + `CacheTesting` (**HttpTesting composed separately/opted-in**); `setup()`/`tearDown()` carry `#[BeforeEach]`/`#[AfterEach]`.
- `Assertions`: assertEquals/NotEquals, assertSame/NotSame, assertTrue/False, assertNull/NotNull, assertEmpty/NotEmpty, assertCount, assertInstanceOf/NotInstanceOf, assertArrayHasKey/NotHasKey, assertGreaterThan/LessThan/OrEqual, assertStringContainsString/NotContainsString, assertMatchesRegularExpression/DoesNotMatch, assertJsonStringEqualsJsonString, assertContains/NotContains, assertHttpStatus, assertFileExists/DoesNotExist, fail, shouldFail.
- `DatabaseTesting`: `refreshDatabase()`, `seed($seederClass)`, `assertDatabaseHas($table, $criteria)`, `assertDatabaseMissing`, `assertDatabaseCount`.
- `PerformanceTesting`: `assertMaxExecutionTime($maxSeconds, callable)`, `benchmark(callable, iterations=1000)` → {avg,min,max,total}. Plus `MetricsProvider` trait (`recordMetrics`, `startProfile/endProfile`, `profile(fn)` → {result, wall_ms, cpu_ms, cpu_pct, memory_mb}) surfaced as a metric table; `CacheTesting` (`flushCache`, `clearLogs`). `HttpTesting` trait exists (`get/post/patch`, `withCsrf`, `csrfHeaders`) but not auto-composed — requires router capability.
- Test discovery: files ending `Test.php`; `app/tests/` (app), `kernel/tests/` (kernel), `{root}/{Module}/src/tests/` (module). Cache `storage/framework/cache/test_cache.php` (TTL 3600). Exit code 1 on failure. Report: passed/failed/skipped/incomplete, slowest 5, benchmarks, metrics.

**Phase 2.2.10 Verification (2026-08-27):**
- Rewrote `forge-testing.html` 2264 lines → 714 lines with app-assembly framing (the runner *your app* uses to verify its own code — no separate test framework). Sections: Overview, Installation, Running Tests, Writing Tests, Attributes, Assertions, Database Testing, Performance & Cache, HTTP Testing, Test Reports. `test` command with `--type`/`--module`/`--group`; escaped code samples (no literal `<?php`).
- Related box links `capabilities.html` + `forge-package-manager.html` (install via PackageManager); does not link `forge-router.html`/`forge-view.html` (not yet created).
- `python3 -m http.server 8765` → 200 (`forge-testing.html`, `forge-package-manager.html`, `capabilities.html`); all 10 body anchors (overview, installation, running, writing-tests, attributes, assertions, database, performance, http, results) resolve; grep `<?php`/`<?=` = 0.

### Phase 2.2.11 - `forge-debugbar.html` rewrite

- Status: `DONE`

**Phase 2.2.11 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeDebugBar/` — module `name: ForgeDebugBar, version: 1.3.21, type: 'generic', order: 3, author: Forge Team, license: MIT`. `#[Requires(forge-router, forge-view)]`, `#[HubItem('Debug Bar', '/hub/debugbar')]`, `#[Compatibility(framework '>=4.15.11', php '>=8.3')]`. **No forge.json manifest** — metadata is inline via `#[Module]` (`DebugBarModule.php`).
- `#[ConfigDefaults(forge_debug_bar.enabled=true, metrics=true)]`, overridden by env `FORGE_DEBUG_BAR_ENABLED` / `FORGE_DEBUG_BAR_METRICS`. Registers middleware `DebugBarExceptionMiddleware` on 'web' group priority 2 (merges/persists exceptions across requests via session `_debugbar_exceptions`). `#[PostInstall: asset:link --type=module --module=forge-debug-bar]` / `PostUninstall asset:unlink`.
- **Enable gate:** bar shows only when `forge_debug_bar.enabled && APP_DEBUG` (both). Injection skips non-HTML Content-Type, bodies without `</body>`, and JSON-fragment responses (`str_starts_with '{"html":'`). Injects CSS link + `DebugBar::render()` + JS before `</body>`.
- **DebugBar API** (`implements Modules\ForgeRouter\Contracts\DebugBarInterface`): singleton `getInstance()`, `addCollector(name, callable)`, `registerTab(name,label,component,collector?,options)`, `getTabs()`, `getData()`, `render()`, `injectDebugBarIfEnabled(response, container)`, `shouldEnableDebugBar(container)`, `reset()`.
- **Core collectors (always):** `memory` (MemoryCollector → current/used/peak MB + % of memory_limit or 'Unlimited'), `time` (TimeCollector → request ms), `messages` (MessageCollector). **Metrics-only:** `request` (RequestCollector: url/method/ip/headers/query/body/cookies/files), `session` (SessionCollector: session_id + formatted data + count), `route` (RouteCollector: uri/method/handler/middleware or 'No current route matched').
- **Cross-module collectors (metrics-only, from ForgeRouter, only if present in container):** `timeline` (TimelineCollector), `views` (ViewCollector), `exceptions` (ExceptionCollector), `Database` (DatabaseCollector; tab data_key 'Database').
- **Tabs:** `resources` (always; memory). Metrics-on adds: `overview`(request), `console`(messages), `errors`(exceptions), `database`(Database), `router`(route), `templates`(views), `state`(session), `timeline`. Bar header metrics: request time (ms), current memory MB, PHP version (`data.php_version`).
- **Helpers** (`Support/helpers.php`): `debug_log($message, $label='info')` → MessageCollector (message/label/time/relative_time), `formatBytes($bytes)` → readable B/KB/MB/GB.
- **Hub:** `DebugBarHubService` (`#[Injectable]`) `storeLatestData()` → `$_SESSION['forge_debugbar_latest_data']`, `getLatestData()`, `formatDataForDisplay()` (overview/queries/timeline/messages/exceptions/views/route/session/request).
- **Note:** catalog link already `forge-debugbar.html` (matches filename, no `forge-debug-bar.html`). Related cross-links `forge-error-handler.html` (debug page) + `forge-package-manager.html`; does not link router/view pages (not created).

**Phase 2.2.11 Verification (2026-08-27):**
- Rewrote `forge-debugbar.html` 1924 lines → 588 lines with app-assembly framing (a development-time toolbar *your app* shows, never shipped; gated on `APP_DEBUG`). Sections: Overview, Installation, When the Bar Shows, The Bar & Tabs, Collectors, Custom Messages, Configuration, Safe in Production. Removed stale framework-integration drift and unverifiable `module:install` commands → correct `package:install-module --module=forge-debug-bar`.
- No literal `<?php`/`<?=` (grep tool = 0; shell `grep -c` miscount was a zsh quoting artifact on `\?`). All 8 body anchors (overview, installation, when-it-shows, the-bar, collectors, custom-messages, configuration, safe-in-production) resolve.
- `python3 -m http.server 8766` → 200 (`forge-debugbar.html`, `forge-error-handler.html`, `forge-package-manager.html`, `capabilities.html`).

### Phase 2.2.12 - `forge-deployment.html` rewrite

- Status: `DONE`

**Phase 2.2.12 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeDeployment/` — module `name: ForgeDeployment, version: 2.5.9, type: 'deployment', order: 99, author: Forge Team, license: MIT`. **No forge.json** — metadata inline via `#[Module]` (`ForgeDeploymentModule.php`). `#[Compatibility(framework '>=4.15.11', php '>=8.3')]`, `#[Repository(git)]`, `#[ConfigDefaults(forge_deployment.digitalocean.api_token, cloudflare.api_token)]` from env `FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN` / `FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN`. `RegistersCommands` only (CLI; no web middleware).
- **Command prefix verified:** all 15 commands use `#[Cli]` (NONE are `#[CoreCommand]`) → literal `modules:` prefix prepended (`kernel/Core/Module/Traits/RegistersCommands.php:25` → `kernel/CLI/Application.php:131-135` `!str_starts_with($commandName, $prefix)`). So `modules:forge-deployment:init|deploy|create-server|delete-server|provision|deploy-app|deploy-env|update|rollback|resume|status|reset|setup-ssl|configure-dns|fix-permissions`.
- Config file: `forge-deployment.php` (alt `deployment.php`) at project root, returns array `php_executable|server|provision|deployment` (`DeploymentConfigReader`). server: name/region/size/image/ssh_key_path; provision: php_version/database_type/database_version/database_name/database_user/database_password; deployment: domain/ssl_email/commands/post_deployment_commands/env_vars. Post-deploy commands run as `{phpN.N} forge.php {cmd}` (rewrites `php forge.php` → `php8.4 forge.php`).
- Deploy flow (`ForgeDeploymentService::deployFull`): SSH connect → system provision (swap/firewall/updates/kernel) → PHP install → database install + create db/user → Nginx install → upload project to `/var/www/{domain}` → Nginx site config. `DeploymentService::deploy`: mkdir remote dir → zip (respects `.forgeignore`) → upload zip to `/tmp` → unzip → set permissions (www-data, dirs 755, files 644, storage 775) → run commands. `configureEnvironment`: merge local `.env`/`env-example` + config env_vars + db config → upload remote `.env`; `key:generate` if no local `.env`.
- State: `DeploymentStateService` → `.forge-deployment-state.json` (server_ip/server_id/ssh_key_path/domain/completed_steps/current_step/last_updated/config/last_deployed_commit). Steps: server_created, ssh_connected, system_provisioned, php_installed, database_installed, nginx_installed, project_uploaded, site_configured. `validate()` = fsockopen :22.
- Incremental: `GitDiffService` (git repo/current commit/changed files between commits/uncommitted) + `IncrementalUploadService` (uploads only changed files). `update --force-full` = full, `--working-tree` = diff uncommitted, `--skip-commands`. `rollback` → previous commit files. `resume` → from last checkpoint. `.forgeignore` (gitignore-style: `!` negation, `/*`/trailing `/` dir, `*`/`?` wildcards, `#` comments).
- Providers: `ProviderInterface` (createServer/waitForServer/getServerStatus/listRegions/listSizes/listImages/deleteServer); `DigitalOceanProvider` (droplets + SSH keys). SSL: `LetsEncryptService::setupSsl(domain,email)` + Nginx update. DNS: `CloudflareService` (addDnsRecord/verifyDnsRecord/deleteDnsRecords/getZoneId). SshKeyManager (locate/read/validate ~/.ssh/id_rsa.pub). ProjectZipService (createZip/cleanup).
- Hub: `DeploymentHubService` logs under `storage/framework/deployments/{id}.log`; masks `SENSITIVE_KEYS` (api_token/password/secret/key/token/ssh_key_path/private_key/passphrase). Catalog link already `forge-deployment.html`. `tutorial-production-deployment.html` exists — cross-linked.

**Phase 2.2.12 Verification (2026-08-27):**
- Rewrote `forge-deployment.html` 627 lines → 655 lines with app-assembly framing (tooling *your app* runs to ship itself; CLI commands + config file + provider creds, no framework layer). Sections: Overview, Installation, Configuration, Commands, The Deploy Flow, Incremental Updates, Environment & Secrets, Cloud Providers & SSL. Removed old framework-architecture framing (mermaid "Deployment Controller" diagram, "framework integration" text bus); all commands use verified `modules:forge-deployment:*` names.
- Config example uses HTML-escaped `&lt;`? — no: code samples are plain PHP arrays WITHOUT `<?php` open tag; grep tool `<?php`/`<?=` = 0. All 8 body anchors (overview, installation, configuration, commands, the-deploy-flow, incremental-updates, environment, providers) resolve.
- `python3 -m http.server 8767` → 200 (`forge-deployment.html`, `forge-package-manager.html`, `tutorial-production-deployment.html`, `capabilities.html`).

### Phase 2.2.13 - `forge-storage.html` (new page, Application primitive)

- Status: `DONE`

**Phase 2.2.13 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeStorage/` — module `name: ForgeStorage, version: 1.3.8, type: 'storage', author: Forge Team, license: MIT`. **No forge.json** — metadata inline via `#[Module]` (`ForgeStorageModule.php`). `#[Compatibility(framework '>=0.1.2', php '>=8.3')]`, `#[Requires(forge-router)]`, `#[PostInstall: db:migrate --type=module --module=forge-storage]` / `PostUninstall db:migrate:rollback`. Registers `StorageDriverInterface` → `ProviderResolver::resolve()` as a singleton; includes `Support/helpers.php`.
- **Config defaults** (`forge_storage`): provider (local), root_path (storage/files), public_path (public/storage), drivers.s3{key,secret,region us-east-1,bucket,endpoint}, signed_url{default_expiration 3600, max_expiration 86400}, hash_filenames true.
- **Env keys** (register): `STORAGE_PROVIDER`/`FORGE_STORAGE_PROVIDER`, `FILE_STORAGE_PATH`/`FORGE_STORAGE_ROOT_PATH`, `FORGE_STORAGE_PUBLIC_PATH`, `FORGE_STORAGE_AWS_ACCESS_KEY_ID`, `FORGE_STORAGE_AWS_SECRET_ACCESS_KEY`, `FORGE_STORAGE_AWS_DEFAULT_REGION`(us-east-1), `FORGE_STORAGE_AWS_BUCKET`, `FORGE_STORAGE_AWS_ENDPOINT`, `FORGE_STORAGE_SIGNED_URL_DEFAULT_EXPIRATION`(3600), `FORGE_STORAGE_SIGNED_URL_MAX_EXPIRATION`(86400), `FORGE_STORAGE_HASH_FILENAMES`(true). Global limits via config `forge_storage.max_size` (default 10485760 = 10MB), `forge_storage.allowed_types` (default `*`); per-location rules via `forge_storage.locations.{name}.{allowed_types,max_size}`.
- **StorageDriverInterface:** put/get/delete/exists/getUrl/signedUrl/getMetadata(size,mime_type,etag,last_modified)/copy/list(prefix,maxKeys=1000).
- **LocalDriver:** root from config root_path → `BASE_PATH`; `getUrl()` → `/storage/{path}`; `signedUrl(path,expires)` → `/storage/signed/{path}?expires={expires}&signature={token}`, token = `hash_hmac('sha256', "{path}|{expires}", APP_KEY)`. **S3Driver** needs `aws/aws-sdk-php` (getUrl/signedUrl throw if absent). ProviderResolver map local→LocalDriver, s3→S3Driver, caches instances.
- **UploadService** (`#[Injectable]`): `upload(UploadedFile|array, ?location)` → single `UploadResult` or array. Validates → filename = `UUID::generate()` if hash_filenames else sanitized original, + extension; stores at `uploads/{location}/{filename}` (or `uploads/{filename}`); returns UploadResult(path,url,size,mimeType,originalName).
- **FileValidator:** max_size (default 10MB) + allowed_types (default `*`; per-location override), throws RuntimeExceptions. **UploadSignature:** HMAC-SHA256 over canonical JSON {location,allowed_types,max_size,sid}, stored in session `forge_storage:upload:{signature}`; `verify()` for tamper check.
- **upload_input()** helper (`Support/helpers.php`): renders `<input type="file" name=... data-upload-endpoint="/__upload" data-signature=... [accept][multiple][extra attrs]>` + hidden `signature` + `csrf_input()`. Options: accept, multiple, csrf, arbitrary attrs.
- **Http/File** (`#[Routable]`, `#[UseMiddleware('web')]`, `#[Endpoint(path:'/__upload', method:'POST')]`): verifies signature (post `signature` or query), getFiles (`file` single or `files[]` multiple), returns JSON 201 single object or `{files:[...]}`; 403 tamper / 400 no-file / validation errors.
- Note: LocalDriver reads config `forge_storage.providers.local.*` (plural) but module sets `forge_storage.root_path`/`public_path` — internal inconsistency; page documents the module's intended keys (root_path/public_path/locations), not the driver's fallback read path.

**Phase 2.2.13 Verification (2026-08-27):**
- Created NEW `forge-storage.html` (578 lines; no prior page existed) with app-assembly framing (file-storage capability *your app* uses to store/serve uploads). Sections: Overview, Installation, Configuration, Drivers, Uploading Files, Validation, Serving & Signed URLs, From Your Code. Added to catalog (`capabilities.html` Application list linked `forge-storage.html`).
- No literal `<?php`/`<?=` (grep tool = 0; `&lt;?=` used in `upload_input()` sample). All 8 body anchors (overview, installation, configuration, drivers, uploading, validation, serving, from-your-code) resolve.
- `python3 -m http.server 8768` → 200 (`forge-storage.html`, `forge-database-sql.html`, `forge-htmx.html`, `forge-package-manager.html`, `capabilities.html`).

### Phase 2.2.14 - `forge-notification.html` (new page, Application primitive)

- Status: `DONE`

**Phase 2.2.14 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeNotification/` — module `name: ForgeNotification, version: 1.0.1, type: 'communication', order: 99` (`ForgeNotificationModule.php`). **No forge.json** — metadata inline via `#[Module]`. `#[Compatibility(framework '>=0.1.0', php '>=8.3')]`, `#[Requires(module: 'forge-events')]`, `#[Repository(git kernel-module-registry)]`. Binds `NotificationInterface` → `ForgeNotificationService` singleton; includes `Support/helpers.php`.
- **Config defaults** (`forge_notification`): `default_channel` (email), `queue{enabled true, queue_name notifications, priority normal, max_retries 3, delay 0s}`, `channels.{email,sms,push}` each with `default_provider` + a `providers` block.
- **Env keys** (register): `NOTIFICATION_DEFAULT_CHANNEL`(email), `NOTIFICATION_QUEUE_ENABLED`(true)/`NOTIFICATION_QUEUE_NAME`(notifications)/`NOTIFICATION_QUEUE_PRIORITY`(normal)/`NOTIFICATION_QUEUE_MAX_RETRIES`(3)/`NOTIFICATION_QUEUE_DELAY`(0s); `NOTIFICATION_EMAIL_PROVIDER`(smtp) + `SMTP_HOST`(localhost)/`SMTP_PORT`(1025)/`SMTP_USERNAME`/`SMTP_PASSWORD`/`SMTP_ENCRYPTION`(none)/`SMTP_FROM_ADDRESS`(noreply@localhost)/`SMTP_FROM_NAME`(Forge), `SENDGRID_*` (`SENDGRID_API_KEY/FROM_ADDRESS/FROM_NAME`), `MAILGUN_*` (`MAILGUN_DOMAIN/API_KEY/FROM_ADDRESS/FROM_NAME`); `NOTIFICATION_SMS_PROVIDER`(twilio) + `TWILIO_ACCOUNT_SID/AUTH_TOKEN/FROM`, `VONAGE_API_KEY/API_SECRET/FROM`; `NOTIFICATION_PUSH_PROVIDER`(firebase) + `FIREBASE_SERVER_KEY/PROJECT_ID`, `ONESIGNAL_APP_ID/REST_API_KEY`.
- **NotificationInterface** (`kernel/Core/Contracts/NotificationInterface.php`): `email(): object`, `sms(): object`, `push(): object` — service implements it → resolves to fluent channel builders.
- **ForgeNotificationService** (`#[Provides(interface: NotificationInterface::class, version: '0.2.0')]`): `email()/sms()/push()` builders; `send(NotificationChannel, EmailPayload|SmsPayload|PushPayload)` dispatches by enum; `queueEmail/queueSms/queuePush(Dto, ?provider)` → dispatch events via EventDispatcher (async).
- **Channels** (all implement `ChannelInterface` with `send(?Dto, ?provider): bool`, `via(provider)`, `getName()`, `getDefaultProvider()`):
  - EmailChannel fluent: `to` (string|array), `from`, `subject`, `body`, `html`, `text`, `attachments`, `cc`, `bcc`, `replyTo`, `via`, `send()`, `queue()`. `send()` builds `EmailNotificationDto` from the fluent props; requires `to`.
  - SmsChannel fluent: `to`, `from`, `message`, `via`, `send()`, `queue()`; requires `to` + `message`.
  - PushChannel fluent: `to`, `title`, `body`, `data`, `badge`, `sound`, `icon`, `image`, `clickAction`, `via`, `send()`, `queue()`; requires `to` + `title` + `body`.
- **DTOs** extend `NotificationDto` (to, from, subject, body, metadata): EmailNotificationDto (+html/text/attachments/cc/bcc/replyTo; auto-promotes body→text when html set but no text), SmsNotificationDto (+message), PushNotificationDto (+title, data, badge, sound, icon, image, clickAction).
- **Payloads** (readonly, `via` = provider override): EmailPayload(to, subject, html, text, from, cc, bcc, replyTo, attachments, via), SmsPayload(to, message, from, via), PushPayload(to, title, body, data, via).
- **Helper:** global `sendNotification(NotificationChannel, EmailPayload|SmsPayload|PushPayload)` + `SendsNotifications` trait — both resolve the service and `send()`, catching failures into the error handler (`collect_exception`).
- **Providers** via `ProviderResolver` map: email{smtp, sendgrid, mailgun}, sms{twilio, vonage}, push{firebase, onesignal}. **BUT only two provider classes ship:** `SmtpProvider` (default email; native SMTP with EHLO/STARTTLS/AUTH LOGIN+PLAIN, multi-part + plain/html bodies; validate requires valid from_address) and `TwilioProvider` (default SMS; Twilio REST Messages.json, curl, per-recipient; validate requires account_sid+auth_token+from). sendgrid/mailgun/vonage/firebase/onesignal are declared in the config map but have NO class files — page notes only SMTP + Twilio work out of the box.
- **Queued flow:** `queue()`/`queueEmail()` build an `Email/Sms/PushNotificationEvent` (annotated `#[Event(queue: 'notifications', maxRetries: 3, delay: '0s', priority: NORMAL)]`) dispatched on the event system; `NotificationListener` (`#[EventListener]` per event) resolves the channel and `send()`s. Async depends on forge-events.
- Constraint noted: only SMTP/Twilio ship classes, so push providers cannot actually deliver yet — page frames push channel API + config accurately without overclaiming delivery.

**Phase 2.2.14 Verification (2026-08-27):**
- Created NEW `forge-notification.html` (680 lines; no prior page existed) with app-assembly framing (communication capability *your app* uses to reach people via email/SMS/push through swappable providers). Sections: Overview, Installation, Configuration, Channels, Sending Notifications, Payloads, Queued Notifications, From Your Code. Added catalog link (Application list).
- No literal `<?php`/`<?=` (grep tool = 0; `&lt;` used throughout code samples). All 8 body anchors (overview, installation, configuration, channels, sending, payloads, queued, from-your-code) resolve.
- `python3 -m http.server 8771` → 200 (`forge-notification.html`, `capabilities.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `api-reference.html`, `tutorial.html`, `forge-events.html`, `forge-database-sql.html`, `forge-package-manager.html`).

### Phase 2.2.15 - `forge-logger.html` (new page, Tooling primitive)

- Status: `DONE`

**Phase 2.2.15 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeLogger/` — module `name: ForgeLogger, version: 0.5.9, type: 'logging', order: 90` (`ForgeLoggerModule.php`). **No forge.json** — metadata inline via `#[Module]`. `#[Compatibility(framework '>=0.1.0', php '>=8.3')]`, `#[Repository(git kernel-module-registry)]`, `#[Structure(...)]`. **No Requires.** Binds core `LoggerInterface` → `ForgeLoggerService` (non-singleton bind). Commands via `RegistersCommands` trait → `#[CoreCommand]` (no `modules:` prefix).
- **Config defaults** (`forge_logger`): driver (file), path (`/storage/logs/forge.log` → `{BASE}/storage/logs/forge.log`), min_level (DEBUG), max_file_size (0).
- **Env keys** (register): `LOG_DRIVER`/`FORGE_LOGGER_DRIVER`(file), `LOG_PATH`/`FORGE_LOGGER_PATH`(storage/logs/forge.log), `FORGE_LOGGER_MIN_LEVEL`(DEBUG), `FORGE_LOGGER_MAX_FILE_SIZE`(0).
- **Core `LoggerInterface`** (`kernel/Core/Contracts/LoggerInterface.php`): `log(string $message, string $level='INFO', array $context=[])`, `debug/info/warning/error/critical($message, $context)`, `exception(\Throwable, $context)`.
- **ForgeLoggerService** (`#[Provides(interface: LoggerInterface::class, version: '0.2.0')]`) implements `ForgeLoggerInterface extends LoggerInterface` (+ `registerDriver(string, LogDriverInterface)`). Constructor reads config; `initService()` pre-registers `file`/`syslog`/`null` drivers. `shouldLog()` drops records below min_level (LogLevel priority DEBUG 0 < INFO 1 < WARNING 2 < ERROR 3 < CRITICAL 4). `formatMessage()` → `[Y-m-d H:i:s] [LEVEL] message` + JSON context (JSON_UNESCAPED_SLASHES); newlines in message flattened to spaces (log-injection guard). `exception()` adds `context['exception'] = {class,file,line,trace}` and logs at ERROR.
- **LogLevel** enum (`Contracts/LogLevel.php`): DEBUG/INFO/WARNING/ERROR/CRITICAL + `priority()` + `fromString()` (unknown → DEBUG). **LogDriverInterface**: single `write(string $message): void`.
- **FileDriver**: appends with `FILE_APPEND`, `mkdir` dir if missing; rotation when `max_file_size > 0` && filesize ≥ max → rename current to `{path}.1` (delete existing `.1` first). **SysLogDriver**: `syslog(LOG_INFO, $message)`. **NullDriver**: no-op (fallback if configured driver not found).
- **ForgeLoggerCommand** (`#[CoreCommand]`, command `log:clear`): clears `forge_logger.path` + `.1`..`.9`; success/`No log files found`/error messages.
- **No `logger()` global helper** (resolved via container `LoggerInterface`). Tests (`src/tests/ForgeLoggerTest.php`, `#[Group("logging")]`) confirm level filtering, format, exception context, newline sanitization, file write + rotation.

**Phase 2.2.15 Verification (2026-08-27):**
- Created NEW `forge-logger.html` (601 lines; no prior page existed) with app-assembly framing (logging capability *your app* uses to write structured records through swappable drivers). Sections: Overview, Installation, Configuration, Writing Logs, Log Levels, Drivers, The CLI Command, From Your Code. Added catalog link (Tooling & Management list).
- No literal `<?php`/`<?=` (grep tool = 0; `&lt;`-escaped code samples). All 8 body anchors (overview, installation, configuration, writing-logs, levels, drivers, command, from-your-code) resolve.
- `python3 -m http.server 8772` → 200 (`forge-logger.html`, `capabilities.html`, `forge-error-handler.html`, `forge-debugbar.html`, `forge-package-manager.html`).

### Phase 2.2.16 - `forge-hub.html` (new page, Tooling primitive)

- Status: `DONE`

**Phase 2.2.16 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeHub/` — module `name: ForgeHub, version: 2.5.14, type: 'generic', order: 6` (`ForgeHubModule.php`). Metadata inline via `#[Module]`. `#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]`, `#[Repository(git kernel-module-registry)]`. **No `#[Requires]`** — its real deps are runtime (each section's controller resolves other modules' services). Binds `ObservabilityServiceInterface` → `ObservabilityService`; refreshes `HubItemRegistry` when present.
- **Web console at `/hub`**: Dashboard, CLI Commands, Logs, Modules, Cache, Queues, Queue Workers, Cron Jobs, Monitoring, Observability, Deployment (ForgeHub-owned), + Debug Bar (contributed by ForgeDebugBar at `/hub/debugbar`), + Profile/Settings/Stats.
- **Access/permissions:** most sections gated by `web`+`auth`+`role`+`hub-permissions` middleware, require `ADMIN` role + `HUB_PERMISSIONS` permission per endpoint; `HubPermissionMiddleware` enforces per-endpoint `required_permissions` attr — unauthenticated → redirect to login, missing permission → redirect away, permission service absent → block (redirect to `/hub`).
- **`#[HubItem]` extension:** a module declares (label, route, icon, order, permissions) via `#[HubItem]`; `HubItemRegistry` scans module classes (`config/hub_items.php` mtime-cached map) and assembles nav, sorted by order, into `Platform` (forge-hub owned) + `Settings` (contributed) groupings.
- **Section-by-section** (verified via explore agent over controllers + services):
  - **Dashboard** (`/hub`, DashboardController): PHP/kernel versions, module count, hub-item count, log-file count, cache stats (CacheService), + queue totals (ForgeEvents QueueHubService, card omitted if absent). System-info panel (server time, timezone, memory limit, max_execution_time).
  - **CLI Commands** (`/hub/commands`, CommandController + CommandService/CommandCacheService): browse commands grouped by category, declare args, run via `proc_open` streaming with interactive-prompt detection (`/commands/send-input`, `/commands/status`), refresh; filesystem-caches commands/args/php-executable under `storage/framework/cache` (forgehub_commands.php, forgehub_command_args.php, php_executable.php); **blocks disallowed commands** (`dev:`, `maintenance:down/up`, `structure:`, `asset:unlink`, `serve`, `down`, `up`). Session keeps last 50 commands.
  - **Logs** (`/hub/logs`, Http\LogEndpoint + LogService/LogEntry): reads `storage/logs` files (<10MB each), parses 3 formats (ErrorHandler with request id/fingerprint/exception/trace; standard `[date] [LEVEL] msg {ctx}`; simple), filters by search/date/level/module/fingerprint, module extracted from path; **no ForgeLogger/ForgeDebugBar dependency**.
  - **Modules** (`/hub/modules`, ModuleController): read-only catalog of installed registry — name/version/desc/author/license/type/tags/path + each module's hub items. No ForgePackageManager dependency.
  - **Cache** (`/hub/cache`, CacheController + CacheService/EnhancedCacheService): driver/key stats, per-tag file detail, tags (commands, controllers, services, attributes, routes, modules, autoloader, compiled_hooks, views, config, reflections, database, templates, sessions, static_files); `clear` (CacheManager::clear), `clear-expired` (default 24h), `clear-tag`; ops logged to `storage/logs/cache_operations.log`. Depends on core CacheManager.
  - **Queues** (`/hub/queues`, QueueController, `#[Reactive]` ForgeWire component): loads jobs+stats from ForgeEvents QueueHubService; sortable/filterable/paginated table with selection; actions retryJob/deleteJob/triggerJob/viewJob, bulkRetry/bulkDelete, closeJobModal. **Requires ForgeEvents.**
  - **Queue Workers** (`/hub/queue-workers`, QueueWorkerController + ForgeEvents QueueWorkerService): list/create/update/delete, start/stop, view (`{id}/output`, last 200 lines) + clear output. Workers persisted by ForgeEvents (`storage/framework/queue-workers.json`), outputs in `storage/framework/queue-worker-outputs`. **Requires ForgeEvents.**
  - **Cron Jobs** (`/hub/cron-jobs`, CronJobController + CronJobService): create/update/delete/run jobs (forge or script, simple/advanced cron schedule, validates expressions, forbids down/serve/up/dev:/structure:), view/clear output (`{id}/output`, default 200 lines), manual run (300s timeout), list commands. Persists `storage/framework/cron-jobs.json`, outputs in `storage/framework/cron-outputs/`, installs enabled jobs into system crontab under `# ForgeHub Cron Jobs` (batched 30s). **Standalone** (core-only). Gate includes role+ADMIN.
  - **Monitoring** (`/hub/monitoring`, MonitoringController + MonitoringService): CPU loadavg (1/5/15), PHP memory + OS memory (Linux /proc/meminfo, macOS vm_stat, Windows wmic), disk (root + storage), PHP version/SAPI, OS, time, uptime, process count; `/monitoring/refresh` JSON. **Standalone.**
  - **Observability** (`/hub/observability/*`, ObservabilityController + ObservabilityService/Interface): dashboard 24h (avg duration, requests, errors, slow queries, sampled traces, unique paths, total queries), `traces` paginated/filterable (path/status/min_duration), `traces/{id}` span detail, `slow-queries` aggregated by SQL signature (min 100ms, last 7 days), `api/stats` JSON. Driven by core `ObservabilityManager` (active only when enabled) + `SamplingProcessor::shouldSample` (always if strategy `always`; else sample if errors/slow/duration ≥ slow_threshold_ms; else random @ base_rate). Data → `observability_traces` table (`DatabaseStorage`, needs DB). **Config/env:** `forge_observability.enabled` from `APP_METRICS_ENABLED`/`FORGE_OBSERVABILITY_ENABLED` (bool, default false); sampling.strategy (adaptive)/base_rate(0.1)/slow_threshold_ms(200)/slow_query_ms(100); storage.retention_days(7). `purgeOldTraces($days=7)` (not wired to a route).
  - **Deployment** (`/hub/deployment`, Http\Deployment + ForgeDeployment DeploymentHubService/DeploymentExecutionService/DeploymentConfigReader): status/config/logs, run deploy/deploy-app/update/rollback/deploy-env/delete-server, save config, secrets (DigitalOcean/Cloudflare, masked in UI, `FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN`/`FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN`), php binaries + executable preference. **Requires ForgeDeployment.**
  - **Debug Bar** (`/hub/debugbar`, Http\Debugbar + ForgeDebugBar DebugBarHubService): latest captured debug data (PHP version, exec time, memory, request/debug panels). **Contributed by ForgeDebugBar.**
  - **Profile** (`/hub/profile`, ProfileController + AppAuth UserContext/Profile): view/update user identifiers + profile (creates if missing). **Needs AppAuth.**
  - **Settings** (`/hub/settings`, SettingsController): change password (verify current, confirm new ≥6 chars, bcrypt). **Needs AppAuth.**
  - **Stats** (`/hub/stats`, StatsController): renders `Forge\Core\Debug\Metrics::getLive()` (metric key, duration sec, memory).
- If a backing capability is absent, that section's controller can't be resolved → section unavailable (no error).

**Phase 2.2.16 Verification (2026-08-27):**
- Created NEW `forge-hub.html` (703 lines; no prior page existed) with app-assembly framing (administration console *your app* puts on top of its own capabilities, protected behind its auth/permissions). Sections: Overview, Installation, Access & Permissions, Dashboard, CLI Commands, Logs/Modules/Cache, Queues & Workers, Cron & Monitoring, Observability, Deployment, Account & Settings, From Your Code. Added catalog link (Tooling & Management list).
- No literal `<?php`/`<?=` (grep tool = 0). All 12 body anchors (overview, installation, access, dashboard, commands, logs-modules-cache, queues, scheduling-monitoring, observability, deployment, account, from-your-code) resolve.
- `python3 -m http.server 8773` → 200 (`forge-hub.html`, `capabilities.html`, `forge-auth.html`, `forge-package-manager.html`, `forge-events.html`, `forge-deployment.html`).

### Phase 2.2.17 - `forge-templates.html` (new page, Application primitive)

- Status: `DONE`

**Phase 2.2.17 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeTemplates/` — module `name: ForgeTemplates, version: 0.1.2, type: 'html', order: 3` (`ForgeTemplatesModule.php`). Metadata inline via `#[Module]`. `#[Compatibility(framework '>=0.1.0', php '>=8.3')]`, `#[Repository(git kernel-module-registry)]`, `#[Structure(['injectable' => ['src/Injectable']]]`. **No Requires.** Includes `Support/helpers.php`. TemplateManager is `#[Injectable]`.
- **TemplateManager** (`src/Injectable/TemplateManager.php`): `useTemplate(string $template, array|object $data = []): string`. Builds `TemplateFinder` with app template root = `{BASE}/getAppPath('templates')` = `app/Common/Templates`.
- **TemplateFinder** (`src/TemplateFinder.php`):
  - `find($template)`: if name contains `:` → `[module, relative]`; if `ModuleHelper::isModuleDisabled($module)` → RuntimeException "Template not found ({module} disabled)"; resolves via `getModulePath($module, 'templates')` = `src/Common/Templates/{relative}.php` under each modules_root. Else bare name → `{app}/Common/Templates/{template}.php` (throws if not a file). Path cached in static `$pathCache`.
  - `findLayout($layout)`: `{app}/Common/Templates/layouts/{layout}.php` (throws if not a file).
- **Render flow (`useTemplate`)**: render the template file → if it called `$this->layout('name')` (sets `extractedLayout`), find that layout and re-render with data `['content' => renderedBody, 'slots' => extractedSlots]` (slots currently always empty in this version — child templates read, they don't populate; documented as named insert points without overclaiming). `$this->slot($name, $default)` returns `extractedSlots[$name] ?? $default`.
- **`renderFile`**: data as object → `get_object_vars` → `extract(EXTR_SKIP)` + `$props` object in scope; `ob_start()` + `include $file`; render errors caught, wrapped in RuntimeException with buffered content, routed via `collect_exception()` then rethrown.
- **API surface:** global `useTemplate($template, $data)` helper (`Support/helpers.php`, resolves TemplateManager from Container); `TemplateHelper` trait (`src/Traits/TemplateHelper.php`) exposing protected `useTemplate()`; and injectable `TemplateManager` directly.
- Structure/config confirmed: `kernel/Core/Structure/forge_structure.php` → app `templates` => `Common/Templates`, module `templates` => `src/Common/Templates`.
- Note: no `forge-view.html`/`forge-markdown.html` pages exist — Related box intentionally links only existing pages (forge-notification, forge-database-sql, forge-package-manager, capabilities) to pass same-origin 200 checks.

**Phase 2.2.17 Verification (2026-08-27):**
- Created NEW `forge-templates.html` (548 lines; no prior page existed) with app-assembly framing (composition capability *your app* uses to render notification/email message bodies from plain PHP template files, with layouts). Sections: Overview, Installation, Template Files, Rendering a Template, Passing Data, Layouts & Namespaces, With Notifications, From Your Code. Added catalog link (Application list).
- No literal `<?php`/`<?=` (grep tool = 0; template examples use `&lt;?php`/`&lt;?=`/`?&gt;`). All 8 body anchors (overview, installation, template-files, rendering, data, layouts, notifications, from-your-code) resolve.
- `python3 -m http.server 8774` → 200 (`forge-templates.html`, `capabilities.html`, `forge-notification.html`, `forge-database-sql.html`, `forge-package-manager.html`).

### Phase 2.2.18 - `forge-saas.html` (new page, Tooling/SaaS primitive)

- Status: `DONE`

**Phase 2.2.18 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeSaas/` — module `name: ForgeSaas, version: 0.1.10, description: 'SaaS plans, subscriptions, and feature gating for Forge Kernel', order: 4`, `#[Requires(forge-router)]`, `#[Requires(forge-database-sql)]`, tags include 'saas','billing','plans','feature-flags','multi-tenant'.
- **ForgeMultiTenant integration is structural, not incidental:** `SubscriptionManagerInterface` and `SubscriptionManager` reference `Modules\ForgeMultiTenant\DTO\Tenant` directly (`forTenant(Tenant)`, `assignPlanToTenant`). Flow: ForgeMultiTenant `TenantMiddleware` (web priority 1) resolves the tenant by host and `setAttribute('tenant', Tenant)` (swaps app DB/session/cache to the tenant's conn). ForgeSaas `SaasMiddleware` (web priority 5) reads that attribute → `SubscriptionManager::forTenant($tenant)`. `FeatureGateMiddleware` (web priority 6) inspects route handlers for gate attributes → 403.
- **Plans/subscriptions live in the CENTRAL DB**, read via `CentralQueryBuilderInterface` (bound in ForgeDatabaseSQL `DatabaseSetup` to a QueryBuilder over the app's default connection). Even though the two migrations are `#[GroupMigration('tenant')]`, the manager reads `saas_plans` / `tenant_subscriptions` from the central store, keyed by `tenant_id`.
- **SubscriptionManager API:** `forTenant(Tenant)`, `hasFeature(feature)`, `withinLimit(resource,count)`, `limitFor(resource)`, `onPlan(slug)`, `isActive()` (active|trial), `currentPlan()`, `currentSubscription()`, `getAllPlans()`, `createPlan(name,slug,features,limits)`, `deletePlan(id)` (refused if it has subscriptions), `disablePlan(id)`, `assignPlanToTenant(tenantId,planId,status=ACTIVE)`.
- **DTOs:** `SaasPlan` {id,name,slug,features[],limits[],isActive; hasFeature(), limitFor() with default PHP_INT_MAX, -1 = unlimited}; `SaasSubscription` {id,tenantId,plan,status,trialEndsAt,currentPeriodEndsAt}. `SubscriptionStatus` enum: active, trial, past_due, canceled.
- **Gate attributes (class or method; method wins for same attribute):** `#[RequiresFeature(feature)]`, `#[RequiresPlan(planSlug)]`, `#[WithinLimit(resource, table)]` (counts rows in the tenant-scoped table via current QueryBuilderInterface → checks plan limit). If handler is not inside a resolved tenant, gates pass through. `FeatureGateMiddleware` returns 403 with a clear message.
- **Seeded default plans** (`SaasDefaultPlansSeeder`): Free (no features; max_users=3, max_storage_gb=1), Pro (advanced_reports,api_access,custom_domain; 25/20), Enterprise (+white_label,priority_support,sso; –1/–1 unlimited).
- **Commands (RegistersCommands, `modules:` prefix, no module-name segment):** `modules:saas:plan:create|delete|disable|list`, `modules:saas:tenant:assign`. All use Wizard (interactive) + OutputHelper. Command examples confirm: `--tenant=upper --plan=plan-pro`.
- **Global helpers** (`Support/helpers.php`): `saas_manager()`, `tenant_can(feature)`, `tenant_limit(resource)`, `tenant_within_limit(resource,count)`, `tenant_on_plan(slug)`, `tenant_subscription_active()`, `tenant_plan()`.
- **Payment is out of scope** — component models plan/subscription/enforcement, not payment itself.
- Note: no `forge-billing.html`/`forge-router.html` pages exist — Related box links only existing pages (forge-multi-tenant, forge-database-sql, capabilities).

**Phase 2.2.18 Verification (2026-08-27):**
- Created NEW `forge-saas.html` (721 lines; no prior page existed) with app-assembly framing (commercial layer *your app* adds on top of multi-tenancy). Led the page with a dedicated **"Multi-Tenant vs. SaaS"** section capturing the user's distinction: ForgeMultiTenant alone = isolation only (no plans/paywall — every tenant equal); add ForgeSaas = plans/subscriptions/feature gating. Also a yellow callout reinforcing that limiting a feature does not alone make it a SaaS. Sections: Overview, Multi-Tenant vs. SaaS, Installation, Plans, Subscriptions, Gating Features & Limits, From Your Code, CLI Commands, Configuration. Added catalog link (Tooling & Management list, replacing the plain-text ForgeSaas entry).
- No literal `<?php`/`<?=` (grep tool = 0; code blocks use `use ...;` style without open tags, matching the sibling ForgeMultiTenant page). All 9 sidebar anchors resolve; no missing section ids.
- `python3 -m http.server 8775` → 200 (`forge-saas.html`, `capabilities.html`, `api-reference.html`, `core-concepts.html`, `forge-database-sql.html`, `forge-multi-tenant.html`, `getting-started.html`, `index.html`, `tutorial.html`).

### Phase 2.2.19 - `forge-billing.html` (new page, Application/portal primitive)

- Status: `DONE`

**Phase 2.2.19 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeBilling/` (3071 lines) — module `name: ForgeBilling, version: 0.2.11, description: 'Billing portal with plans, invoices, and payment provider support', order: 5`, `#[Requires(forge-database-sql, forge-sql-orm, forge-router, forge-view, forge-components)]`, `#[Compatibility(framework '>=4.15.10', php '>=8.3')]`. Uses an explore agent for full fact-gathering.
- **Billable entity via `BillableResolver::resolve()`:** priority = global `tenant()` (ForgeMultiTenant → Tenant id), else global `getCurrentUser()` (AppAuth → user id), else null. So it bills a tenant in a multi-tenant app OR an individual user in a plain app — not strictly SaaS. `BillingMiddleware` (web priority 7) preloads the subscription only when the request `tenant` attribute is a ForgeMultiTenant `Tenant`; if none, it passes through untouched (portal stays browsable empty).
- **Central data:** ALL six tables are `#[GroupMigration('central')]` — `billing_plans`, `billing_subscriptions` (has `tenant_id`), `invoices`, `invoice_items`, `payment_methods`, `transactions`. Every service is constructed with `CentralQueryBuilderInterface` (central DB); `tenant_id` is a loose string reference (no FK). Recurring-billing rows are central, keyed by tenant/user id.
- **Services (all singletons):** `BillingPlanService` (all/getById/getBySlug/create/disable/delete; central), `BillingSubscriptionService` (forTenant/current/isActive [active|trial]/onTrial/assign/cancel; central; caches subscription per instance), `InvoiceService` (getForTenant/getById/latestForTenant/create/markAsPaid/getItems; central), `PaymentMethodService` (getForTenant/create/delete), `PaymentService` (charge/refund; central), `BillingPortalService` (overview() composing sub/invoice/plans), `PaymentProviderRegistry` (register/get [throws if missing]/all), `ManualPaymentProvider` (built-in; charge always succeeds, transaction id `manual_<hex>`; name `manual`). `BillingServiceInterface` declared but no concrete implementer (closest is BillingPortalService).
- **DTOs (all final readonly):** `BillingPlan` {id,name,slug,amount,currency,interval,features[],isActive,createdAt}, `BillingSubscription` {id,tenantId,plan,status,trialEndsAt,currentPeriodEndsAt,cancelledAt}, `ChargeRequest`, `ChargeResult` {success,transactionId,amountCharged,currency,providerResponse,errorMessage}, `Invoice` {number,status,items,paidAt,dueDate,...}, `InvoiceItem`, `PaymentMethod`, `RefundResult`.
- **Enums:** `InvoiceStatus` (pending/paid/overdue/canceled/refunded), `PaymentMethodType` (card/paypal/manual/other), `PlanInterval` (monthly/yearly/weekly/one_time — defined but unused; intervals are raw strings), `SubscriptionStatus` (active/trial/past_due/canceled/expired).
- **Events:** `GenerateInvoiceEvent` `#[Event(queue:'billing', maxRetries:3)]` {tenantId,subscriptionId,planId,planAmount,planCurrency,planInterval}. Listener: resolves plan + tenant subscription (must match ids) → creates invoice with single line item "{plan} - {interval}" → rolls `billing_subscriptions.current_period_ends_at` forward (monthly=+1mo, yearly=+1yr, weekly=+1wk, day=+1d, one_time=+100y, default=+1mo).
- **Controllers (all `#[Routable(prefix:'/billing')]` + `#[UseMiddleware('web')]`, NO auth/role/SaaS-gate attributes):** `BillingDashboardController` GET `/billing`; `PlanController` GET `/billing/plans` + POST `/billing/plans/{id}/subscribe` (requires plan active + a saved payment method, else redirect); `InvoiceController` GET `/billing/invoices` + `/billing/invoices/{id}`; `PaymentMethodController` GET `/billing/payment-methods` + POST `/billing/payment-methods` + POST `/billing/payment-methods/{id}/delete`; `SubscriptionController` GET `/billing/subscription` + POST `/billing/subscription/cancel`. Portal views under `src/UI/views/pages/billing/`, layouts `root/billing`, components billing-nav/plan-card/invoice-*/subscription-status/payment-method-card, asset `billing.css`.
- **Commands (RegistersCommands, `modules:` prefix):** `modules:billing:plan:create [--name --slug --amount --currency=USD --interval=monthly --features]`, `modules:billing:plan:disable --id`, `modules:billing:plan:list`, `modules:billing:tenant:assign --tenant --plan`, `modules:billing:generate-invoices [--dry-run] [--process]` (selects active subs with current_period_ends_at null/<= now; dispatches GenerateInvoiceEvent; --process actually processes queue 'billing').
- **Helpers (`Support/helpers.php`):** `billing_subscription(): ?BillingSubscription`, `billing_is_active(): bool`, `billing_on_trial(): bool`.
- **Known quirks (documented honestly, not as bugs):** refund provider lookup uses the stored transaction id string as a registry key (no `provider_name` column) — page phrases refunds at the contract level only; `PaymentMethodType` lacks `bank_transfer` (page avoids it); subscription cached per request (page notes resolved-once consistency).
- **Payment is NOT the manual provider's job necessarily** — `PaymentProviderInterface` {charge/refund/tokenize/name} is the extension point; `manual` ships for out-of-box flow; real processing needs a registered provider.

**Phase 2.2.19 Verification (2026-08-27):**
- Created NEW `forge-billing.html` (742 lines; no prior page existed) with app-assembly framing (portal *your app* adds — plans, subscriptions, invoices, payment methods, transactions + ready UI under `/billing`; bills a tenant or an individual user). Sections: Overview, Who You Bill, Installation, Plans, Subscriptions, Invoices, Payments & Providers, The Billing Portal, From Your Code, CLI Commands, Billing vs. SaaS (distinguishing ForgeBilling money/entitlement-records from ForgeSaas feature-gating). Added catalog link (Application list).
- No literal `<?php`/`<?=` and no `&gt;` entities (grep tool = 0; code blocks use `use ...;` style without open tags, consistent with siblings). All 11 sidebar anchors resolve; no missing section ids.
- `python3 -m http.server 8776` → 200 (`forge-billing.html`, `capabilities.html`, `forge-saas.html`, `forge-multi-tenant.html`, `forge-database-sql.html`, `api-reference.html`, `core-concepts.html`, `getting-started.html`, `index.html`, `tutorial.html`).

### Phase 2.2.20 - `forge-sprinkle.html` (new page, Frontend/progressive enhancement)

- Status: `DONE`

**Phase 2.2.20 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeSprinkle/` — module `name: ForgeSprinkle, version: 0.1.0, type: 'tool', order: 90`, `#[Requires(forge-router, forge-view)]`, `#[Compatibility(framework '>=6.0.23', php '>=8.3')]`, tags `['enhance', 'sprinkle', 'ux', 'progressive-enhancement']`. PostInstall: `asset:link --type=module --module=forge-sprinkle`; PostUninstall: `asset:unlink`.
- **Injection:** uses `InjectsAssets` trait + `RouterHookAttribute(RouterHookName::AFTER_REQUEST)` to inject `<link rel=stylesheet .../sprinkle.css>` before `</head>` and a **deferred** `<script .../sprinkle.js>` before `</body>` on HTML responses (skips JSON/plain-text; only when `<!DOCTYPE` present; asserts no duplicate injection). Verified by `ForgeSprinkleModuleTest` (injects into HTML responses, skips JSON).
- **Core library** `src/UI/assets/js/sprinkle.js` (2467 lines) + `src/UI/assets/css/sprinkle.css`, minified ~47KB combined. **Manifesto (matches the app-assembly framing):** "high power behaviors for HTML", "Zero JavaScript boilerplate. Zero dependencies. No build step. Upgrade native form controls and layout structures with single attributes", "**not** a UI component library / not a replacement for HTML / not a polyfill", "Every directive works gracefully, degrading to native elements if JavaScript is disabled." Relies on modern browser APIs: `MutationObserver`, `IntersectionObserver`, native `<dialog>`, CSS `@starting-style`.
- **Architecture:** global `window.ForgeSprinkle.register(selector, handler)` registers a directive; a `MutationObserver` re-runs handlers on dynamically added elements; `attrIndex` builds an index off the selector's attribute pattern. Register before `DOMContentLoaded`.
- **JS directives (1-42 range, gaps at 33/39):** `[theme-toggle]`, `[shell]`/`[sidebar]`/`[content]`/`[shell-static]`(CSS), `ul[nav]` (+`a[active]`→`aria-current="page"`, `nav-group`, `[nav-sep]`), `details[accordion]` (+`[open-group]`/`[close-group]`), `dialog[modal]`/`[drawer]` + `command-for`/`command` (`show-modal`/`close`) + polyfill, `[close-outside]`, `details[dropdown]` (keyboard nav), `[tooltip]`, `[copy]` (`copy="#selector"`), `img[zoomable]`, `[count-up]`, `[sticky]`, `fieldset[card]`, `button[loading]`, `textarea[autosize]`, `[character-count]`, `[enter-submit]`, `[clearable]`/`[leading]`/`[suffix]` (SVG icons via `/assets/svg/{name}.svg`, override `<meta name="sprinkle-svg-path">`; `suffix="close|clear"` clears; password `suffix` eye toggle → `{name}-off.svg`; `clearable` ignored when `suffix` present), `select[combo-box]` (searchable/multiple/category/data-avatar, WAI-ARIA combobox, stays a real `<select>`), `input[otp]`, `input[mask]`, `input[prefix]`, `input[switch]` (real checkbox, `role="switch"`), `label[drop-zone]` (drag-and-drop file input + previews), `[error-message]` (+`error-message-<state>` per-state messages, `aria-invalid`/`aria-describedby`), `input[allowed-domains]`, `input[no-past]`/`[no-future]`/`[disable-days]`, `[business-hours]`, `input[date-input]`/`[date-range]`+`data-range-type`, `form[enhance]` (opt-in fetch/FormData posting + inline validation + busy buttons; **degrades to normal native submit if JS fails**), `[auto-select]`, `[truncate="N"]`, `[auto-width]` (CSS), `img[avatar]`/`ul[breadcrumb]` (CSS-only), `form[confirm-leave]`.
- **App-level demo:** `app/UI/views/pages/sprinkle/` + `app/Http/Sprinkle.php` using `[theme-toggle]`, `[shell]`, `[sidebar="left"]`, `[content]`, `data-sprinkle-theme="dark"`.
- Catalog line 127 (Frontend list): ForgeSprinkle entry existed as plain text — now linked to the new page.

**Phase 2.2.20 Verification (2026-08-27):**
- Created NEW `forge-sprinkle.html` (769 lines; no prior page existed) with app-assembly framing led by the user's explicit **progressive-enhancement** direction — the module's own manifesto ("not a UI component library / not a replacement for HTML / not a polyfill") is front and center. Sections: Overview, **Not a Framework**, **Progressive Enhancement**, Installation, Form Fields, Validation, Dialogs & Menus, Layout & Nav, UX Polish, Extending, Configuration. Related box links ONLY existing pages: forge-wire.html, forge-htmx.html, forge-tailwind.html, capabilities.html (no forge-router/view/components links). Added catalog link (`capabilities.html` line 127 Frontend list).
- No literal `<?php`/`<?=` (grep tool = 0) and no PHP method arrows (`-&gt;` count is 16 = all `--&gt;` closing HTML comments in `<pre><code>` samples, correctly entity-escaped). All 11 sidebar anchors resolve to section ids. The 77 `&gt;` are HTML tag delimiters in entity-escaped `<code>`/`<pre>` samples (correct — page code blocks are HTML/JS, not PHP).
- `python3 -m http.server 8777` → 200 (`forge-sprinkle.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-wire.html`, `forge-htmx.html`, `forge-tailwind.html`).

### Phase 2.2.21 - `forge-sockets.html` (new page, Frontend/networking)

- Status: `DONE`

**Phase 2.2.21 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeSockets/` — module `name: ForgeSockets, version: 0.1.0, type: 'networking'`, tags `['websocket', 'socket', 'realtime', 'event-loop', 'rfc6455']`. Docblock: "production-grade WebSocket primitives ... built on PHP's built-in networking (no external dependencies). Implements RFC 6455 (opening handshake, framing, control frames, close handshake) on top of a non-blocking stream_select event loop". Uses `RegistersCommands` → command is **`modules:socket:serve`** (NOT CoreCommand — literal `'modules:'` prefix, same as billing/saas).
- **Files:** `ForgeSocketsModule.php`, `Commands/SocketServeCommand.php`, `Contracts/{AuthenticatorInterface,ConnectionInterface,MessageHandlerInterface,ServerInterface,TickableHandler}.php`, `Handlers/EchoHandler.php`, `Server/{Connection,ConnectionRegistry,ConnectionState,EventLoop,Handshake,HandshakeResult,WebSocketServer}.php`, `Server/Frames/{CloseCode,Frame,FrameCodec,FrameParser,Opcode,ProtocolException}.php`, `tests/{FrameCodecTest,HandshakeTest,WebSocketServerTest}.php`.
- **Config defaults:** `host` 127.0.0.1, `port` 8282, `max_payload` 65536, `heartbeat_seconds` 30, `workers` 1, `handler` EchoHandler::class, `authenticator` null. Env overrides (from `setupConfigDefaults` in Module, NOT ConfigDefaults@env): `SOCKET_HOST`, `SOCKET_PORT`, `SOCKET_MAX_PAYLOAD`, `SOCKET_HEARTBEAT`, `SOCKET_WORKERS`. Note workers count is config-only; no multi-process fork in module (single process).
- **SocketServeCommand** (`#[Cli(command: 'socket:serve')]`): flags `--host`, `--port`, `--handler` (must be MessageHandlerInterface), `--authenticator` (must be AuthenticatorInterface). Resolves from config or flags; pcntl_async_signals + SIGINT/SIGTERM → graceful shutdown. Builds `WebSocketServer(host,port,handler,authenticator,maxPayload,heartbeatSeconds,tickInterval)` — **note tick_interval read from config key `forge_sockets.tick_interval` default 0.05** (not in ConfigDefaults list; set separately).
- **WebSocketServer**: `bind()` (idempotent, `stream_socket_server` tcp://host:port, non-blocking, throws RuntimeException on bind fail), `boundAddress()` (e.g. "127.0.0.1:8282"), `run(?callable $shouldStop)`. Accepts via `stream_socket_accept`, Connection per peer, EventLoop with `addRead`/`addWrite`, self-rescheduling heartbeat timer (ping idle>interval, abort idle>2x), optional handler tick timer (only if handler is TickableHandler; cadence = tickInterval config, default 1.0 in ctor but 0.05 passed by command). Teardown funnels through `onClosed` → registry.remove + loop.remove + fclose + handler->onClose (onError on throw). shutdown(): gracefulClose (1001 'server shutting down') + flush + abort non-closed + close listener.
- **EventLoop**: stream_select-based non-blocking loop; read interest steady; write interest toggled via `setWriteInterest` (backpressure); timers with nearest-deadline select timeout (never blocks past next timer → heartbeat/ticks on schedule). `addTimer`/`cancelTimer`/`stop`/`run`.
- **Connection**: handshake state machine (HANDSHAKING→OPEN→CLOSING→CLOSED), reads via persist parser, control frames answered here (PING→pong, PONG noop, CLOSE→echo close), data frames → handler->onMessage (onError + fail 1011 on handler throw). sendText/sendBinary (queued FrameCodec::encode), close(code=1000,reason) → CLOSING + enqueue close, ping(), gracefulClose() (1001). Bounded send queue (MAX_SEND_BUFFER 262144 → fail 1008 'send buffer exceeded'). HANDSHAKE_LIMIT 16384 ('request too large' → 1009). markAborted() → 1006 ABNORMAL. user via authenticator.
- **Handshake** (RFC 6455 §4): parses GET line + headers; requires version 13, Upgrade:websocket + Connection contains upgrade, Sec-WebSocket-Key regex `[A-Za-z0-9+/=]{22,24}`; produces 101 + Sec-WebSocket-Accept = base64(sha1(key.GUID,true)), GUID `258EAFA5-E914-47DA-95CA-C5AB0DC85B11`. Preserves path/query/headers for authenticator. Rejected → 400. Authenticator null → 403 + close 1008 POLICY_VIOLATION.
- **FrameParser** (RFC 6455 §5): incremental buffered; multiple frames/packet; split frames; fragmentation reassembly (up to maxFragments 16); control frames returned immediately even mid-fragment; requires masked client frames (ELSE 1002 PROTOCOL_ERROR); RSV bits (1002); reserved opcode (1002); max payload (1009 MESSAGE_TOO_BIG); text frames validated UTF-8 (1007 INVALID_FRAME_PAYLOAD); control frames must be fin + <=125 (1002).
- **FrameCodec**: encode (fin bit + opcode; lengths <126 / 126+16bit / 127+64bit; optional mask random_bytes(4)); unmask (§5.3 XOR).
- **Opcode enum**: CONTINUATION 0x0, TEXT 0x1, BINARY 0x2, CLOSE 0x8, PING 0x9, PONG 0xA; isControl() = value>=0x8.
- **CloseCode enum**: NORMAL 1000, GOING_AWAY 1001, PROTOCOL_ERROR 1002, UNSUPPORTED_DATA 1003, ABNORMAL 1006 (never on wire), INVALID_FRAME_PAYLOAD 1007, POLICY_VIOLATION 1008, MESSAGE_TOO_BIG 1009, MANDATORY_EXTENSION 1010, INTERNAL_ERROR 1011.
- **Contracts:** MessageHandlerInterface (onOpen after auth, onMessage(conn,opcode,payload) ONLY complete data frames — control auto-handled, onClose(code,reason), onError); AuthenticatorInterface authenticate(path,headers):?string (null → reject 1008; cookie header available for SessionDriver); ConnectionInterface id/peer/path/user(?string)/sendText/sendBinary/close(code=1000,reason)/isOpen/isClosing; TickableHandler onTick(float now) optional periodic tick; ServerInterface run(?callable).
- **EchoHandler (default)**: onOpen sends `{"event":"open","id":N}`, echoes every TEXT back.
- **Tests** (`#[Group('sockets')]`): WebSocketServerTest = full loopback lifecycle (pcntl_fork real server on ephemeral port → handshake 101 + accept key, welcome frame, echo, ping→pong w/ same payload, oversized → close 1009). HandshakeTest = validates 101 + RFC example accept key `s3pPLMBiTxaQ9kYGzzhZRbK+xOo=`, incomplete → null, missing upgrade/wrong version/bad key → rejected, path/query/headers exposed. FrameCodecTest = round-trip, length boundaries (0/125/126/65535/65536), multiple frames/packet, split across feeds, fragmentation reassembly, control interleaved mid-fragment, unmasked → 1002, oversized → 1009, invalid UTF-8 → 1007, reserved opcode → 1002, RSV bits → 1002, large control → 1002, close frame codec 1001.
- Catalog line 128 (Frontend list): ForgeSockets entry existed as plain text — now linked to the new page.

**Phase 2.2.21 Verification (2026-08-27):**
- Created NEW `forge-sockets.html` (864 lines; no prior page existed) with app-assembly framing led by "**Primitives, Not a Platform**" — the transport is code *your app* plugs a MessageHandlerInterface into (rooms/games/channels are domain logic, never shipped). Sections: Overview, Primitives Not a Platform, Installation, Running the Worker, Your Message Handler, The Connection, Game Clocks & Ticks, Authenticating, The Protocol, Configuration (config/env table). Relates to sibling realtime/frontend pages only (existing): forge-wire.html, forge-htmx.html, forge-sprinkle.html, capabilities.html. Added catalog link (`capabilities.html` line 128 Frontend list).
- No literal `<?php`/`<?=` and no `&gt;` entities (grep tool = 0; PHP code samples use `use ...;` + literal `->`, matching siblings). All 10 sidebar anchors resolve to section ids.
- `python3 -m http.server 8777` → 200 (`forge-sockets.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-wire.html`, `forge-htmx.html`, `forge-sprinkle.html`).

### Phase 2.2.22 - `forge-markdown.html` (new page, Tooling/markdown processor)

- Status: `DONE`

**Phase 2.2.22 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeMarkDown/` — module `name: ForgeMarkDown, version: 0.1.4, type: 'html'`, tags `['html','static','site','generator','markdown','processor','markdown-processor']`, `#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]`, `#[Provides(interface: ForgeMarkDownInterface::class, version: '0.1.4')]`. `register()` binds `ForgeMarkDownInterface::class` → `ForgeMarkDown::class`. NO commands, NO config, NO Requires attribute — pure container binding.
- **Files (3):** `ForgeMarkDownModule.php`, `ForgeMarkDown.php` (184 lines), `Contracts/ForgeMarkDownInterface.php`. No tests shipped.
- **`ForgeMarkDownInterface`:** `parse(string $markdown): string` + `parseFile(string $path): array`.
- **Pipeline in `parse()`:** parseFrontMatter (strips leading `---\n...\n---` YAML block via regex) → parseBlockElements (code blocks first, then headings/hrs/quotes/lists/tables via `preg_replace_callback_array`) → parseInlineElements → `trim()`.
- **Inline elements:** `**b**`/`__b__` → `<strong>`, `*e*`/`_e_` → `<em>`, `~~s~~` → `<del>`, `![alt](src)` → `<img src alt>` (src+alt htmlspecialchars), `[t](url)` → `<a href>` (href escaped; **link text NOT escaped**), `` `code` `` → `<code>` (escaped).
- **Block elements:** `#`..`######` headings; `---`/`***`/`___` (3+) → `<hr>`; `> q` (SINGLE-line) → `<blockquote>`; `-`/`*`/`+` lists → `<ul><li>` (ALWAYS `<ul>`, no ordered list); GitHub-style pipe table (header + separator `[-:]+` + rows) → `<table><thead><tbody>`.
- **Code blocks:** fenced ` ```lang ` → `<pre><code class="language-X">`; 4-space/tab-indented → `<pre><code>`; contents htmlspecialchars. Both processed FIRST (before block/inline) so code isn't mangled by later passes.
- **`parseFile(string $path): array`:** file_get_contents → extractFrontMatter (regex-yanked `---` block, `yaml_parse` via the `yaml` extension, try/catch → `['error' => 'Invalid YAML syntax']` on parse failure) → returns `['content' => parse(html), 'front_matter' => array]`. NOTE: `parse()` strips front matter WITHOUT yaml-parsing; only `parseFile()` yields the metadata array (needs `yaml` ext for the array).
- Catalog line 141 (Tooling & Management list): ForgeMarkDown entry existed as plain text — now linked to the new page.

**Phase 2.2.22 Verification (2026-08-27):**
- Created NEW `forge-markdown.html` (706 lines; no prior page existed) with app-assembly framing — a single-purpose converter your app resolves via the container interface and calls `parse()`/`parseFile()`. Honest-scope section "**What It Isn't**" documents the deliberate subset: NOT full CommonMark/GFM, single-line blockquotes, always-`<ul>` lists, raw table cells. Sections: Overview, How It Works, Inline Syntax, Block Syntax, Code Blocks, Front Matter, From Your Code, What It Isn't, Installation. Related box links ONLY existing pages: forge-templates.html, forge-notification.html, forge-hub.html, capabilities.html. Added catalog link (`capabilities.html` line 141 Tooling list).
- No literal `<?php`/`<?=` (grep tool = 0) and no PHP method arrows `-&gt;` (code samples use literal `->`, e.g. `$this->markdown->parse(...)`); `&lt;`/`&gt;` entities (21/24) are only intentionally-displayed literal HTML tags (`<strong>`, `<h1>`, `<pre>`, etc.) in tables/prose + 2 `=&gt;` fat-arrow in the return-comment — all inside `<code>`/`<pre>`, correct. All 9 sidebar anchors resolve to section ids.
- `python3 -m http.server 8777` → 200 (`forge-markdown.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-templates.html`, `forge-notification.html`, `forge-hub.html`).

### Phase 2.2.23 - `forge-admin-console.html` (new page, Tooling/protected admin UI)

- Status: `DONE`

**Phase 2.2.23 Pre-flight verification (2026-08-27):**
- Source: `modules/ForgeAdminConsole/` — module `name: ForgeAdminConsole, version: 0.1.4, type: 'generic'`, tags `['ui','admin','console']`, order 55, `#[Requires(module: forge-router, forge-view, forge-components, forge-auth)]`, `#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]`, `#[PostInstall(asset:link, --type=module, --module=forge-admin-console)]` / `#[PostUninstall(asset:unlink)]`. `#[ConfigDefaults(forge_admin_console.brand = 'Admin', items_per_page = 10)]`. NO commands, NO container binds of its own.
- **The user's explicit framing (captured on page):** ForgeAdminConsole combined with ForgeAppAuth are used with ForgeAuth to COMPLETE the auth implementation — providing the UI + business functionality for an easy secure admin console + complete local auth flow. Layer model: (1) ForgeAuth = engine/primitives (UserProvider/UserContext contracts, roles/permissions, JWT/API keys, middlewares — deals in contracts, decides nothing about user storage); (2) ForgeAppAuth = business layer that PROVIDES the concrete `UserProviderInterface`→UserRepository + `UserContextInterface`→UserContext AND the real `/auth` routes (register, login, logout, forgot-password, reset-password with forms/validation/sessions); (3) ForgeAdminConsole = protected UI window (/admin pages) consuming both.
- **Controllers (all `#[UseMiddleware(['web','auth'])]` + `#[Layout("ForgeComponents:wrappers/admin-default")]`):**
  - `Dashboard` `#[Routable]` → GET `/admin`: builds stats cards (Total Users '—', Active Sessions, Modules, Kernel v{Version::version()}), activities feed (welcome + "User {identifier} logged in"), quickActions (View Users,/admin/users; Account Settings,/admin/account; Edit Profile,/admin/profile). Reads `UserContextInterface::current()` (getIdentifier/getEmail).
  - `Account` `#[Routable(prefix:'/admin')]` → GET/POST `/admin/account`: edit account (email InputDefinition), saveAccount sanitizes `$request->postData` via SecurityHelper, Flash success/error, Redirect::to('/admin/account').
  - `Profile` (namespace `Modules\ForgeAdminConsole\Controllers`) `#[Routable]` → GET/POST `/admin/profile`: edit profile (identifier Textarea + bio), saves via sanitize + Flash + redirect.
  - `Users` `#[Routable]` → GET `/admin/users` (list via UserProvider->getUsersTableData) + GET `/admin/users/{id}` (viewUser via UserProvider->getUserDetails; missing → Flash 'User not found.' + Redirect /admin/users).
- **Common/UserProvider** `#[Injectable]`: wraps injected `ForgeAuth\UserProviderInterface`; `getUsersTableData(page,perPage)` → `$userProvider->paginate()` → rows [id,identifier,email] (errors silently swallowed via try/catch); `getUserDetails(id)` → `$userProvider->findById()` → array or null.
- **UI views (5, all under `pages/admin/`):** dashboard.php, account.php, profile.php, users/list.php, users/user-detail.php. Each composes layoutProps (SidebarDefinition 4 nav items Dashboard/Account/Profile/Users with IconDefinition + is_link_active active state; UserDropdownDefinition name/email + DropdownItemDefinition Profile/Account/divider/Logout→`/auth/logout` method POST) + layoutSections (breadcrumbs ForgeComponents:admin/breadcrumbs; head_end CSS link `/assets/modules/forge-admin-console/css/admin-console.css`). Reuse ForgeComponents admin components (alert, input, textarea, button, admin/stats, admin/table, admin/data-card, admin/activity-list, admin/quick-actions). admin-console.css styles fc-admin-* classes.
- **ForgeAuth contracts used:** `UserContextInterface` {current():?AuthUserInterface, isAuthenticated():bool, setCurrentUser():void}; `UserProviderInterface` {findById, findByIdentifier, findByEmail, verifyCredentials, createUser, paginate(page,perPage,options): Paginator}; `ForgeAppAuthModule` provides those Interface→UserRepository/UserContext binds. `auth` middleware = `ForgeAppAuth\AuthMiddleware` (checks UserContext->current(); else setIntendedUrl(REQUEST_URI) + Redirect::to('/auth/login', 401)).
- **ForgeAppAuth local flow routes** (`#[Routable(prefix:'/auth')]` + `#[UseMiddleware('web')]`): GET/POST `/auth/login`, `/auth/register`, `/auth/forgot-password`, `/auth/reset-password`, POST `/auth/logout`. ForgeAppAuth v0.1.6 requires forge-router>=1.0.10, forge-view>=0.1.2, forge-auth>=2.0.5, forge-database-sql>=0.9.12, forge-sql-orm>=0.6.5.
- Catalog line 140 (Tooling & Management list): ForgeAdminConsole entry existed as plain text — now linked to the new page.

**Phase 2.2.23 Verification (2026-08-27):**
- Created NEW `forge-admin-console.html` (763 lines; no prior page existed) with app-assembly framing led by "**The Auth Puzzle**" — the three-layer ForgeAuth (engine) + ForgeAppAuth (local auth flow) + ForgeAdminConsole (protected UI) model that completes the auth implementation. Sections: Overview, The Auth Puzzle, Installation, The Console (route table), Protected Routes (auth middleware + /auth/login redirect + logout POST), User Management (UserProvider wrapping UserProviderInterface), The Layout & UI (admin-default layout + component definitions), Extending, Configuration (brand/items_per_page table). Related box links ONLY existing pages: forge-auth.html, forge-hub.html, forge-notification.html, capabilities.html (forge-app-auth.html does NOT exist — referenced in prose only, not linked; forge-components.html also not linked). Added catalog link (`capabilities.html` line 140 Tooling list).
- No literal `<?php`/`<?=` and NO `&gt;`/`=&gt;`/`-&gt;` entities at all (grep tool + bash both 0; all PHP code uses literal `->`, including nullsafe `?->`) — cleanest page yet. All 9 sidebar anchors resolve to section ids. Verified no duplicate prism CSS link (removed a duplicate head link before verification).
- `python3 -m http.server 8777` → 200 (`forge-admin-console.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-auth.html`, `forge-hub.html`, `forge-notification.html`).

### Phase 2.2.24 - `forge-view.html` (new page, Foundation/view engine)

- Status: `DONE`

**Phase 2.2.24 Pre-flight verification (2026-08-27) — user requested a deep analysis + double-check of StructureResolver paths:**
- Source: `modules/ForgeView/` — module `name: ForgeView, version: 0.1.11, order: 4, type: 'core'`, tags `['view-engine','view']`, `#[Provides(interface: ViewInterface::class, version: '0.1.11')]`. `register()` binds `ViewInterface` → `View` (singleton) and resets `ViewState` on `ResetManager::onBefore`. NO commands, NO config, NO Requires attribute. Includes `Support/helpers.php`.
- **Files (8):** `ForgeViewModule.php`, `View.php` (382), `ViewFinder.php` (242), `ViewState.php` (108), `Traits/ViewHelper.php` (59), `Helpers/Html.php` (56), `Helpers/ModuleResources.php` (13), `Support/helpers.php` (141), + 2 tests (ViewTest, ViewStateTest).
- **`ViewInterface` contract (kernel core):** `render()`, static `layout()/slot()/startSection()/endSection()/section()`, `viewComponent()`, `renderComponentView()`.
- **Render pipeline (`View::render`):** `compileView()` (finder→findView, prefixed `pages/` via trait, collect_view_data) → `executeFile()` (extract vars EXTR_SKIP, include in ob) → capture layout vars ($layoutSlots/$layoutSections/$layoutProps/$parentLayout) → resolve layout (explicit arg → route's `layout` from `#[Layout]` → `View::layout()` state) → `renderLayoutChain()`. `suppressLayout(true)` bypasses. State reset in `finally`.
- **Layout chain + "parent view":** a layout file sets `$parentLayout = '...'`; engine recursively wraps child→parent via `renderLayoutChain()`, merging slots/sections/props at each level (child overrides). Circular layout detection: visited path re-visit → `RuntimeException("Circular layout reference detected: ...")`. Real examples in ForgeComponents: `admin-default.php`/`admin-slim.php`/`public.php`/`auth-*.php` set `$parentLayout = 'ForgeComponents:root'`, and `root.php` emits DOCTYPE + reads `$layoutProps['title']`/`['bodyClass']` + `$layoutSections['head_end']`/`['body_end']`.
- **Layout selection routes:** (1) `#[Layout('Name')]` attribute on controller method OR class — ForgeRouter reads it (class then method, method wins) at `Router.php:219-285`, stores `layout` on the route; `ViewHelper::view()` reads `$route["layout"]`. (2) deprecated static `View::layout()/startSection()/endSection()/section()/suppressLayout()`. (3) `Module:layout_name` module syntax.
- **`$layoutProps`**: arbitrary data (title, bodyClass, sidebar=SidebarDefinition object, footer) merged up into the chain. **`$layoutSections`**: named HTML fragments injected at anchors (head_end, body_end, breadcrumbs). **`$layoutSlots` + `slot($name,$default)`**: named content slots (callable or string), default fallback.
- **Component system:** `component($name,$props,$slots)` helper → `ModuleResourceResolver::parse()` (`Module:name`) → `View::viewComponent()` → fresh `View` w/ module context → `processSlots()` (nested component slots declared as `['name','props','slots']` resolved lazily into closures that render nested components) → `renderComponentView()` (state slots saved/restored so siblings don't leak). Components under app `components/` or module `src/UI/views/components/` (+ `src/UI/components/` fallback).
- **`ViewHelper` trait (`view()`):** prepends `pages/`, reads route's `layout` (via `Router::getCurrentRoute()['layout']`), auto-detects module from controller namespace (`detectModule()`, first namespace part == modules namespace → `$namespaceParts[1]`), returns `Response`.
- **STRUCTURE-RESOLVER-DRIVEN PATHS (user's explicit double-check focus):** View constructor + ViewFinder resolve via `Forge\Core\Structure\StructureResolver` (which reads project's `forge_structure.php` = internal defaults merged with optional user `BASE_PATH/forge_structure.php` override). Defaults (from `kernel/Core/Structure/forge_structure.php`): app root `app`, `app.views = UI/views`, `app.components = UI/views/components`, `app.layouts = UI/views/layouts`, `app.pages = UI/views/pages` → full `{app_root}/UI/views/...`, i.e. `app/UI/views`. If the `app/`-rooted path doesn't exist (app root dir missing), View constructor / `getGlobalAppViewPath()` transparently tries `src/` + substr-relative, i.e. `src/UI/views`. **Note (corrected/confirmed vs earlier docs): these are DEFAULTS NOT HARDCODED — structure keys define them; a user `forge_structure.php` can rename/relocate them.** Module paths: iterate `resolveModulesRoots()` array (default `['modules','capabilities']`) → `$moduleDir = BASE_PATH/{root}/{Name}`; try `StructureResolver getModulePath($module, "views")` (`src/UI/views`) then hardcoded fallback `src/{UI}/views/...`; components via `buildModulePaths()` tries `getModulePath($module,"components")` + `getModulePath($module,"views").'/components'` + hardcoded `src/UI/{type}/` + `src/UI/views/components/`. Disabled modules rejected early (`ModuleHelper::isModuleDisabled` → RuntimeException).
- **Global helpers (Support/helpers.php):** `component()` (module-aware), `slot($name,$default)`, `form_open($action,$method,$attrs)/form_close()` (CSRF + `_method` spoofing for non-GET/POST), `external_asset_config()`/`external_asset($name)` (SRI integrity/crossorigin from CSP config `security.csp.external_assets` / `forge_router.csp.external_assets`, registers in `AssetRegistry`), `merge_classes($base,$additional,$overrides)`. Plus kernel `e()`/`raw()`. `Html` helper: `link()/script()` (defer, integrity, crossorigin, referrerpolicy no-referrer). `ModuleResources::pathTo()` → `/assets/modules/{module}/{resource}`.
- **Tests guarantee:** simple view render w/ extracted vars; layout+wrapper sections; suppressLayout bypass; missing view → RuntimeException. ViewState: layout/suppress/section/slot(resolve callable+fallback)/reset.
- Catalog line 105 (Foundation list): ForgeView entry existed as plain text "the view engine: layouts, sections, components. (page coming)" — now linked to the new page.

**Phase 2.2.24 Verification (2026-08-27):**
- Created NEW `forge-view.html` (753 lines; no prior page existed) with app-assembly framing — a view engine *your app* assembles, with the **"Where Things Live"** section making the StructureResolver-driven path story explicit (defaults table: app `app/UI/views/...` vs module `modules/{Name}/src/UI/views/...`; note these are defaults not hardcoded rules, app-root configurable via structure, modules resolve across multiple roots with `src/UI/...` fallback, disabled modules rejected). Sections (9): Overview, Views, Layouts & Parent Layouts, Props Sections & Slots, Components, Where Things Live, From Your Code, Helpers, Installation. Related box links ONLY existing pages: forge-templates.html, forge-sprinkle.html, capabilities.html (NOT forge-router.html — doesn't exist yet; ForgeWire/ForgeHtmx referenced in prose only, not linked). Added catalog link (`capabilities.html` line 105 Foundation list).
- No literal `<?php`/`<?=` (grep tool = 0). Page shows raw `.php`/view-file content so uses FULL escaping `&lt;?php`/`&lt;?=` (10) + `->` as `-&gt;` (4) + `=>` as `=&gt;` (10) ONLY inside code blocks — consistent with forge-templates.html (the other view-file page), NOT the forge-admin-console literal-`->` style (that page showed method snippets without opening tags). All 9 sidebar anchors resolve to section ids.
- **User correction applied (2026-08-27):** removed the claim "usually already there in a fresh install" from both Overview and Installation — ForgeView is only present if the install includes it (e.g. from an HTTP blueprint/web starter); otherwise it must be installed. Overview now says it's powered by an HTTP blueprint/web starter, Installation says web starter/HTTP blueprint typically includes it.
- `python3 -m http.server 8781` → 200 (`forge-view.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-templates.html`, `forge-sprinkle.html`, `anatomy.html`).

### Phase 2.2.25 - `forge-router.html` (new page, Foundation/HTTP routing)

**Phase 2.2.25 Deep Analysis (2026-08-27) — full ForgeRouter source read (module `ForgeRouter` v1.0.39, `type: 'core'`, `order: PHP_INT_MAX`, tags `['router','http']`, description "Forge Router and Http", Requires none, `#[PostInstall(command: "modules:forge-router:init", args: ["--force"])]`, `#[PostUninstall(command: "modules:forge-router:cleanup", args: ["--force"])]`):**
- Lifecycle: `register()` runs `RouterHookManager::init()` + `registerEngineMiddlewares()` + `registerCollectors()`; `boot()` on APP_BOOTED builds Request → `RouterSetup::setup` → Kernel → BEFORE_REQUEST hook → `$kernel->handler($request)` → AFTER_REQUEST → `$response->send()` → AFTER_RESPONSE → exit.
- Routing: `#[Endpoint(path, method, middleware, permissions, override)]` (repeatable) on methods; `#[Routable(prefix)]` on classes (deprecated `#[Route]`); `#[ApiRoute]` → `/api/{prefix}/{version}{path}` default `/api/v1`; `#[UseMiddleware]` class/method repeatable; `#[RequiresRole]`; `#[Layout]`. Params `{id}`, `{id:\d+}`, `{path:.+}` (greedy), `{slug:no-slash}`; typed casting int/float/bool; param named `request` → Request injected. Static map + per-method RadixTree + regex; cache `storage/framework/cache/controller-map.php`.
- Middleware: abstract `Middleware::handle(Request, callable): Response`; pipeline `array_reduce`; `config/middleware.php` groups (global/web/api) + module `registerMiddleware()`; engine ones: Observability(global,-1), RateLimit(0), CircuitBreaker(1), SanitizeInput(3), Session(web,0), Csrf(web,1); optional Corse/Compression/HttpCache/IpWhiteList/RelaxSecurityHeaders/Cookie/ApiMiddleware/ApiKey.
- Request/Response: `input/query/all/json/getHeader/getClientIp` (REMOTE_ADDR-preferring, spoof-safe)/`file`/UploadedFile (moveTo verify-then-rename)/`isSecure`/`_method` spoof/`cookies`/`cookie` Cookie objects/route exposed as `_route`. Response status/headers/Cookie/send; `ApiResponse` (`{data,meta}`); `Redirect::to/back`; `ResponseHelper` trait (json/api/csv/download/create/createError).
- Sessions/CSRF: SessionMiddleware + CsrfMiddleware (419 on mismatch; `_token` or `X-CSRF-TOKEN` header; TokenManager HMAC-signed 1-day). Helpers csrf_token/csrf_meta/csrf_input/window_csrf_token/request/is_link_active/add_timeline_event; collectors Database/Exception/Timeline/View.
- Hooks/errors: `RouterHookAttribute(RouterHookName::BEFORE_REQUEST|AFTER_REQUEST|AFTER_RESPONSE)` compiled+cached; ErrorPageRenderer custom `{code}.php` templates else default; security defaults cors origins `['*']`, rate_limit 40/60s (off in dev, localhost bypassed), circuit_breaker 5/300s, csp, ip_whitelist (403, no-op when empty).
- Commands: CoreCommand bare `serve` (`--host` localhost `--port` 8000, serves `BASE_PATH/public`, compiles hooks) / `down` / `up` (503, writes `storage/framework/`) / `generate:endpoint` / `generate:middleware`; module-prefixed `modules:forge-router:init` (scaffolds public entry + middleware/config) / `modules:forge-router:cleanup`.
- Controllers auto-discovered from app + module dirs via `ControllerLoader` + `StructureResolver` (`['modules','capabilities']` roots), cached `controller-map.php` (mtime invalidation, per-host).

**Phase 2.2.25 Implementation (2026-08-27):**
- Created NEW `forge-router.html` (1001 lines; no prior page existed) with app-assembly framing — HTTP routing your app wires in. Sections (12): Overview, Routes & Controllers, Requests, Responses, Middleware, Sessions & CSRF, Security & Hardening, Hooks & Extension Points, Errors & Maintenance, The Command Line, Configuration, Installation. Related box links ONLY existing pages: forge-view.html, forge-wire.html, capabilities.html. Catalog link added (`capabilities.html` line 104 Foundation list, replacing the `(page coming)` placeholder).
- **User directive applied (2026-08-27):** used the corrected "fresh install" wording (web starter / HTTP blueprint includes it, otherwise install yourself), consistent with the ForgeView correction. No stale `App\Modules\ForgeRouter\` namespace anywhere (all `Modules\ForgeRouter\`).

**Phase 2.2.25 Verification (2026-08-27):**
- No literal `<?php`/`<?=` (grep tool = 0). Page shows raw PHP controller/attribute content so uses FULL escaping `&lt;?php`/`&lt;?=` + `->` as `-&gt;` (15) + `=>` as `=&gt;` (1) ONLY inside code blocks — the ONLY literal ASCII `->` in the file is in HTML comments (`<!-- ... -->`), confirming all code arrows are entities. Consistent with forge-templates/forge-view (raw-PHP-content pages use full escaping), not the forge-admin-console literal-`->` style.
- All 12 sidebar anchors resolve to section ids.
- `python3 -m http.server 8783` → 200 (`forge-router.html`, `index.html`, `getting-started.html`, `core-concepts.html`, `capabilities.html`, `api-reference.html`, `tutorial.html`, `forge-view.html`, `forge-wire.html`). All 8 same-origin `.html` links 200.
- ForgeRouter is now the last core Foundation capability without a page → now has one; only ForgeStatic (Examples "Not listed" callout) remains as the outstanding catalog item.

### Phase 2.2.26 - Catalog restructure (new `catalog.html` marketplace + concepts-only `capabilities.html`)

**Phase 2.2.26 Decision (2026-08-27) — user asked for a catalog that "presents the catalog much better" and capability pages that "focus on explaining what, why, how":**
- Split into two pages (user chose "capabilities=concepts, catalog.html=new"; individual per-capability pages stay as-is — they already explain what/why/how for each capability).
- `capabilities.html` (top-level nav destination) becomes a **concepts-only** page: What/Why/How + Capability vs Module table (kept from before, prose lightly expanded), plus a CTA that points to the catalog. Removed The Catalog + Examples lists (moved to the new catalog page).
- NEW `catalog.html` — a **marketplace / directory** feel (user: "like the module/capabilities marketplace etc directory"): clickable per-capability cards grouped by category (Foundation / Application / Frontend / Tooling & Management / Examples), each card with a FontAwesome icon, name, and one-liner; "new" badges on ForgeSprinkle/ForgeSockets; a blue "How do I install these?" panel linking ForgePackageManager; sidebar with category anchors + a "Concepts" link back to capabilities.html; "Not listed" ForgeStatic callout kept in Examples.
- **Placement follow-up (user directive):** ForgePackageManager moved OUT of Tooling & Management INTO Foundation as the leading card — it's the bootstrap capability that comes preinstalled in every blueprint and is how you install all the others, so it's more foundational than tooling. Card carries an "always present" badge + "Comes with any blueprint" tagline; the Foundation section description now notes this. Tooling & Management retains ForgeDeployment as its first card.
- Both pages rebuilt on the **clean nav shell** (matching forge-view/forge-router/core-concepts — no legacy Kernel dropdown, which is only correct on the Kernel-family pages: index, kernel-overview, anatomy, lifecycle, forging-your-own). capabilities.html was the stale outlier still carrying the legacy dropdown; now migrated. Nav "Capabilities" still points at capabilities.html; catalog is discoverable via that page's sidebar "Browse" block + bottom CTA + intro link.

**Phase 2.2.26 Verification (2026-08-27):**
- `catalog.html` (778 lines), `capabilities.html` (359 lines). HTML parser: no unclosed tags (only expected void `<meta/>`/`<link/>` end-tag noise).
- All sidebar anchors resolve to section ids: catalog `#foundation/#application/#frontend/#tooling/#examples`; capabilities `#what-why-how/#capability-vs-module`.
- `python3 -m http.server 8784/8785` → every same-origin `.html` link 200 (catalog: nav shell + all 28 capability pages; capabilities: nav shell + catalog.html). No literal `<?php`/`<?=` in either (grep tool = 0).
- Both pages include the scrollspy (`.nav-link.active` on scroll) matching forge-view's tail; mobile menu toggle retained.

### Phase 2.3 - Promote ForgePackageManager

- Status: `DONE`

**Phase 2.3 Pre-flight verification (2026-08-27):**
- Source is `modules/ForgePackageManager/` (not `capabilities/`). Module `name: ForgePackageManager, version: 3.3.32, type: 'management', order: 1, isCli: true` (`ForgePackageManagerModule.php`). CLI-only: binds `PackageManagerInterface` → `PackageManagerService` in CLI SAPI; no web middleware.
- **Lock file is `forge-lock.json`** (NOT `forge.lock.json`) — confirmed from source. Also a separate `forge.json` is the editable declaration. Went further and fixed all wrong `forge.lock.json` refs across the docs (`anatomy.html:53,92,216,217`).
- `config/source_list.php` does NOT exist in the app — the module auto-generates it on first use via `#[ConfigDefaults]` + `setupConfigDefaults`, pre-filled with the official `kernel-module-registry` git source. Real shape: `return ['registry' => [ [name,type,url,branch,private,personal_token,description] ], 'cache_ttl' => 3600]`.
- Note: there is no `installation.html`; the getting-started page is `getting-started.html` (plan's Phase 3.0 still decides whether to rename). Promotion here targeted `capabilities.html` directly.

**Phase 2.3 Verification (2026-08-27):**
- Rewrote `forge-package-manager.html` 2742 lines → 837 lines with app-assembly framing. Kept the strong non-composer conceptual framing (units-not-source, old-school mirrors, trusted sources, control) but tightened and made technically accurate. Sections: Overview, "It's Not Composer", Units Not Source, Registries & Mirrors (registry structure + modules.json index), forge.json & forge-lock.json, Trusted Sources, Commands, Custom Registries, Config & Source Types, Dependencies, Suggestions.
- **All 4 commands verified — they are `#[CoreCommand]`, so NO `modules:` prefix:** `package:install-module` (args verified: `--module`, `--force`, `--debug`, `--non-interactive`/`--auto`, `--trust-source`, `--replace`, `--config-mode=defaults|publish|env`, `--category=module|capability`, interactive wizard), `package:remove-module` (`--module`, `--force`, `--debug`), `package:list-modules`, `package:install-project` (installs from forge-lock.json).
- **KEY factual corrections vs old page:**
  - `forge-lock.json` entry verifies to keys `version, registry, module_path, integrity (sha256 hex), source_type, source_config (secrets stripped), category` — old page wrongly showed `url` + a top-level `registries` map. Fixed.
  - Integrity is SHA-256 of the zip; `installFromLock` re-verifies expected vs calculated (re-downloads/mismatch errors); trust NEVER disables integrity.
  - Trusted-sources mechanism verified: boolean flag per registry `name` in `storage/framework/trusted_sources.json` (NOT PKI); trusting suppresses per-command Y/N/A/R PostInstall prompts; `--trust-source`/`--non-interactive` auto-trusts.
  - Source types confirmed from `SourceFactory`: `git, http, ftp, sftp, local, network` (git default).
  - Dependencies via `#[Requires(module, version)]`: auto-install, skip installed, reject circular with clear error.
  - Config generation modes confirmed (`defaults|publish|env`; env → `.env` overrides like `forge_wire.use_minified` → `FORGE_WIRE_USE_MINIFIED`).
  - Escaped `&lt;?php` in the `source_list.php` example (old page had a literal `<?php`).
- **Promotion:** added a highlighted "Where do these come from?" callout at the top of the capabilities catalog (`capabilities.html`, right after the catalog intro) pointing to Package Management — discoverable within 1 click. Package manager already had a direct link in the Tooling & Management list.
- `python3 -m http.server 8092/8093` → 200 (package-manager, capabilities, anatomy); 11 anchors resolve; no literal `<?php`/`<?=`; all same-origin `.html` links resolve.

---

## Phase 3 - Getting Started & Core Concepts Surgery

> Why third: Now we can trim framework-like teaching from these two heaviest pages without losing content (it moves to capability pages).

### Phase 3.0 - Pre-flight: Diff planned moves

- Status: `DONE`
- Pre-flight check:
  - `forge-documentation/getting-started.html:133-206` (sidebar) + `forge-documentation/getting-started.html:353-956` (First Controller through Database sections)
  - `forge-documentation/core-concepts.html:144-210` (sidebar)
  - Live CLI help: `php forge.php generate:controller --help`, `php forge.php package:install-module --help`, `php forge.php --help` to confirm wizard flags `getting-started.html:376-407`
- Changes: Produce mapping table in this file: which `getting-started.html` / `core-concepts.html` H2 moves where.

**Phase 3.0 Decision (2026-08-27):**
- **Keep the `getting-started.html` filename — do NOT rename to `installation.html`.** Renaming would force nav updates across ~40 pages plus a redirect stub; that belongs in Phase 5.1 (redirect pass). The page is slimmed in place instead.
- Mapping table (getting-started H2 → destination):
  - `Requirements` → stays (kernel requirement phrasing).
  - `Installation` (Quick + Manual) → stays.
  - `Configuration` → stays, but DB_* block dropped (moved to capability; note now points to forge-database-sql.html).
  - `First Controller`, `Services & DI`, `Routing`, `Views & Templates`, `Database & ORM` → REMOVED in favor of a "Build Your App" section of 4 cards linking to forge-router.html / forge-view.html / forge-database-sql.html + the catalog, behind a prerequisite callout.
  - `Developer Mode` → stays as a summary → links to forging-your-own.html.
  - `CLI Commands` → stays, trimmed to `CLI Browser` (interactive command browser) + a couple of representative commands; long per-scope command lists dropped.
  - `Next Steps` → stays, links updated to catalog/core-concepts/tutorials.

### Phase 3.1 - Slim `getting-started.html` -> `installation.html` + Assemble flow

- Status: `DONE`
- Pre-flight check:
  - `install.php`, `installer/installer.sh`, `.env` example, `php forge.php serve` (`forge-documentation/getting-started.html:331-348`)
  - Confirm `FORGE_DEVELOPER_MODE` key `getting-started.html:978-999`
- Changes:
  - `getting-started.html`: keep `Requirements`, `Installation (Quick install + Manual)`, `Configuration`, `Developer Mode (summary -> link to forging-your-own.html)`, `CLI Browser`. Remove `First Controller`, `Services & DI`, `Routing`, `Views & Templates`, `Database & ORM` (replace with 3 cards linking to `forge-router.html`, `forge-view.html`, `forge-database-sql.html` with prerequisite callout).
  - Or optionally rename file to `installation.html` and keep `getting-started.html` as redirect. Decide in 3.0.
  - Sidebar trimmed from 10 links to 5-6.
- Exit criteria: Getting Started no longer teaches Router/View/DB as kernel steps; every controller/route example is gated behind capability install.

**Phase 3.1 Implementation & Verification (2026-08-27):**
- Rewrote `getting-started.html` 1431 → 654 lines, keeping the clean nav shell. Sidebar trimmed from 10 to 6 links: Requirements, Installation, Configuration, Build Your App, CLI Browser, Developer Mode (+ a "Next: The Catalog / Core Concepts" block).
- Kept: Requirements, Installation (Quick + Manual), Configuration (DB_* block dropped; env example now kernel-only: APP_*/CACHE/SESSION + FORGE_DEVELOPER_MODE; note points to forge-database-sql.html), Developer Mode (summary → links forging-your-own.html), CLI Browser (interactive command browser kept; long per-scope command lists removed; note that capability commands appear only once installed).
- Removed: First Controller, Services & DI, Routing, Views & Templates, Database & ORM. Replaced with a "**Build Your App**" section: a blue prerequisite callout (these are capabilities, install via ForgePackageManager, see the catalog) + 4 cards linking to forge-router.html, forge-view.html, forge-database-sql.html, and the catalog.
- **Removed stale content:** the "Services & DI" section's false claim that services are "discovered from any folder"/"scans all directories recursively" is gone — consistent with the scoped/`injectable`-paths discovery (ServiceDiscoverSetup) corrected in Phase 1.2. Also removed the stale `App\Modules\ForgeRouter\...` namespace in the old controller sample.
- Verification: all 6 sidebar anchors resolve; all 12 same-origin `.html` links return 200 (port 8788); no literal `<?php`/`<?=` (grep tool = 0); HTML well-formed (no unclosed tags); page serves 200. Next Steps links updated to catalog/core-concepts/tutorials.
- **Blueprint-wizard follow-up (user directive, 2026-08-27):** the Quick Install description was wrong ("clones the starter project"). Correction from `installer/create-project.php`: the one-liner `bash <(curl -Ls ...installer.sh)` runs a wizard that (1) fetches the blueprint registry and interactively selects a **blueprint** (verified sources: `blueprint-templates/{blank, minimal-http, auth}`; `blank` = bare + package manager, `minimal-http` = web starter wiring routing/views), (2) offers optional blueprint configuration options, (3) prompts for a project name/path, (4) confirms, then scaffolds + installs Kernel + downloads ForgePackageManager + runs `package:install-project`. Updated Quick Install with a "What the wizard asks" callout; non-interactive path now shows installer-forwarded flags (`--blueprint=minimal-http --yes`, `. --blueprint=blank`, `--list`); removed the inaccurate `git clone forge-starter` manual block. Verified `create-project` is a standalone `create-project.php` (NOT a `forge.php` command) — args are forwarded via `installer.sh "$@"`.
- **`serve` gating follow-up (user directive, 2026-08-27):** `php forge.php serve` is an HTTP concern (ForgeRouter), so it's only available on a web blueprint (e.g. `minimal-http`) or after installing the router — a `blank` blueprint has no `serve`. Corrected both the manual-install "From there: serve runs it locally" line and the Configuration "Start the development server" block to say `serve` depends on the routing capability. All remaining `serve` refs are now gate-aware; no `db:migrate`/`down`/`up` snippets remain in the page.

### Phase 3.2 - Slim `core-concepts.html` (kernel-only)

- Status: `DONE`
- Pre-flight check:
  - `kernel/Core/DI/Attributes/Service.php`, `kernel/Core/Module/Attributes/LifecycleHook.php`
  - `kernel/Core/Contracts/` (to keep `Database & ORM (Capabilities)` as contracts-only section)
- Changes:
  - Keep: `Architecture (Kernel Components only)`, `DI Container`, `CLI Kernel`, `Configuration`, `Capability System (loader)`, `Contracts`.
  - Demote to summaries + links: `Routing System`, `Middleware`, `View Engine`, `Components`, `Database & ORM` -> each becomes 2-3 line summary with `-> See ForgeRouter` etc., not full tutorials (`core-concepts.html:469-1053` largely removed).
  - Update sidebar `core-concepts.html:144-210` to 6 items max.
- Exit criteria: Core Concepts page title could be `Kernel Concepts` without lying.

**Phase 3.2 Verification (2026-08-27):**
- Rewrote `core-concepts.html` (2817 -> 1007 lines) as a **kernel-only** concepts page with the clean nav shell. Sidebar reduced to 6 items: Architecture, Dependency Injection, Capability System, Configuration, CLI Kernel, Beyond the Kernel.
- Kept + corrected: **Dependency Injection** now documents the only real attribute `#[Injectable(id: null, singleton: true)]` (`Injectable.php:10-16`) and `register()/make()/get()/bind()/singleton()/tag()/tagged()` from `Container.php`; removed the false `#[Service]`/`#[Discoverable]` attributes and the stale "recursively scans any folder" discovery claim (Container has no auto-recursive scan — you register services). **Capability System** uses the verified `#[Module]` options (`Module.php`: name/version/description/order/core/isCli/type/category/tags) and the real `LifecycleHookName` enum (`EARLY_BOOT`, `BEFORE_MODULE_LOAD`, `AFTER_MODULE_LOAD`, `AFTER_MODULE_REGISTER`, `AFTER_CONFIG_LOADED`, `AFTER_BOOT`, `APP_BOOTED`), plus the real companion attributes (`#[Compatibility]`, `#[ConfigDefaults]`, `#[PostInstall]`/`#[PostUninstall]`, `#[Repository]`/`#[Provides]`/`#[Requires]`). **Architecture** lists real kernel components (Bootstrap, container, autoloader, config, cache, module loader, CLI, contracts) and points router/view/middleware to capabilities. **CLI Kernel** lists only real kernel commands (verified `command:` names): generate:command/entity/event/migration/module/seeder/test, cache:flush/warm/rebuild, asset:link/unlink, storage:link/unlink, structure:info/init, key:generate, help, stats + `dev:*` registry set; removed false kernel claims (`generate:controller/model/service/component/middleware/trait/enum/dto`, `serve`, `maintenance:*`) — `serve` is ForgeRouter, `migrate*` is ForgeDatabaseSQL.
- Demoted to a curated **"Beyond the Kernel"** section (summary cards linking ForgeRouter / ForgeView / ForgeDatabaseSQL / ForgeEvents), replacing the old in-page Routing / Middleware / View Engine / Components / Database & ORM / Async-Queues full tutorials. Async/queues correctly redirected to ForgeEvents (verified `forge-events.html` is "an event and queue system" and kernel has no queue workers).
- Escaping: raw PHP blocks use `&lt;?php`, arrows escaped `-&gt;` inside code blocks; no literal `<?php`/`<?=`/`->`/`=>` in code. Verified: `python3 -m http.server 8790` -> all 11 same-origin `.html` links 200; all 6 section anchors present; tidy well-formed (only benign empty `<i>` warning).

---

## Phase 4 - API Reference & Tutorials Alignment

### Phase 4.1 - `api-reference.html` -> `kernel-api.html` clarify

- Status: `DONE`
- Pre-flight check:
  - `kernel/Traits/`, `kernel/CLI/Traits/`, `kernel/Core/Support/helpers.php`, `kernel/Core/Cache/Traits/`
  - `storage/framework/cache/` files listed `api-reference.html:281-374` (class-map.php etc.)
- Changes:
  - Rename or add header banner: `Kernel API Reference - For Capability APIs see ForgeRouter / ForgeView / ForgeDatabaseSQL pages`.
  - Keep `Performance Optimizations`, `Traits`, `Helper Functions`, `DI Container`, `Contracts`, `Cache` but add `Internal` badge where marked `api-reference.html:239-243`.
  - Update sidebar `api-reference.html:140-207`.
- Exit criteria: No user confuses kernel API with app API.

**Phase 4.1 Verification (2026-08-27):**
- Rewrote `api-reference.html` (2372 -> 1862 lines) as **Kernel API Reference** with the clean nav shell (active API Reference, kept filename `api-reference.html` — rename deferred to 5.1, mirroring the getting-started decision). Title + H1 now "Kernel API Reference"; header banner links capability APIs (ForgeRouter / ForgeView / ForgeDatabaseSQL) so the page never confuses kernel API with app API; trailing "Building your app" cross-link cards.
- Removed non-kernel APIs that had leaked in: CSRF/helper functions, `App\Modules\*` lazy-loading claim, `#[Service]`/`#[Discoverable]`/`#[Inject]` attributes, stale traits (AuthorizeRequests, EndpointHelper, HasMetaData, HasTimestamps, PaginationHelper, RepositoryTrait, ResponseHelper), stale setup classes (ContainerWebSetup, HelperDiscoverSetup, RouterSetup), stale services (DatabaseManager, CacheRefreshListener, ModuleAssetManager, TokenManager).
- Corrected stale facts vs source: **autoloader caches via in-memory + `storage/framework/cache/class_file_map.php` (10-8000 cap), NOT APCu**; **cache default driver is SQLite (`CACHE_DRIVER`, `storage/database/cache.sqlite`) NOT File**; cache files are `compiled_hooks.php`/`module_registrations.php`/`class_file_map.php` (not app_command_class_map/class-map/middleware-map/helper-map); DI attribute is **only `#[Injectable]`**; helpers are exactly 9 (`env/cache/config/data_get/e/raw/tap/dd/request_host`); traits = 17 real `kernel/Traits/` + 5 `CLI/Traits/`; contracts = 11 real; services = 11 real; bootstrap classes use real names (ServiceDiscoverSetup, KernelServiceSetup, ModuleSetup, ContainerAppSetup, ContainerCLISetup, ErrorHandlerSetup, SessionSetup, AppCommandSetup, LoadsCommands, LoadsIncludes, CliErrorHandler). Added `Internal` badge on Bootstrap Process; version `7.0.17` noted.
- `cache()` helper corrected to its real signature `cache($key, $value = null, $ttl = null)` (get or set by key), not "resolve manager"; tagged-cache example now uses `Container::getInstance()->make(CacheManager::class)`.
- Verified: `python3 -m http.server 8789` -> all 10 same-origin `.html` links 200 (incl. forge-router/forge-view/forge-database-sql/kernel-overview); all 10 section anchors present; no literal `<?php`/`<?=`; literal `->` only (method-snippet page, allowed); tidy well-formed (one benign empty-`<i>` warning shared with all pages).

### Phase 4.2 - Tutorials re-label as Assembled App Recipes

- Status: `DONE`
- Pre-flight check:
  - `forge-documentation/tutorial-building-todo-app.html` (2047 lines, legacy nav) read fully; flow order & stale APIs documented against research.
- Changes:
  - **Reworked `tutorial-building-todo-app.html` into an incremental "assembled-app recipe"** (auth-later): Step 1 builds a plain HTTP todo app (migration, model, controller, views) before auth; Step 2 adds accounts; Step 3 adds ForgeWire reactivity; Step 4 adds events; Step 5 adds tests.
  - Fixed stale APIs vs `modules/` source: `App\Modules\ForgeAuth\*`/`ForgeWire\*`/`ForgeDatabaseSQL\*` -> real `Modules\*`; removed `#[Service]` DI attribute (only `#[Injectable]`), `#[Route]` -> `#[Endpoint]`, `#[Middleware]` -> `#[UseMiddleware]`; `Forge\Core\Helpers\Redirect` -> `Modules\ForgeRouter\Helpers\Redirect`; `module:package-install` -> `package:install-module`; `middlewares.php` -> `middleware.php`; `generate:controller` -> `generate:endpoint`; `generate:model` -> `generate:entity`; migration run `db:migrate`; `fw_id()` (deprecated) -> `scope()`; Step 2 uses **ForgeAppAuth** (distributable auth capability with the complete flow): User model -> `Modules\ForgeAppAuth\Models\User`, current user -> `Modules\ForgeAppAuth\Services\UserContext::current()` (no global `getCurrentUser()` helper — controller passes `user` to the view instead), auth middleware -> `Modules\ForgeAppAuth\Middlewares\AuthMiddleware` (`auth` group in `config/middleware.php`), endpoints `/auth/register|login|logout|forgot-password|reset-password`.
  - Updated nav shell (Capabilities link stays, Tutorials active) + `<title>` -> "Building a Todo App - Forge Kernel Documentation"; sidebar 8 anchors (introduction, project-setup, step-1..5, putting-together).
  - Escaping pass: all raw-PHP code blocks `->`->`-&gt;`, `=>`->`=&gt;` inside code blocks (only comments + JS `=>` remain literal).
- Exit criteria: Tutorial reads incrementally (build plain todo app first, add capabilities one per step); code snippets match real module namespaces/APIs; page renders and links validate.

**Phase 4.2 Verification (2026-08-28):**
- Rewrote `tutorial-building-todo-app.html` 2047 -> 2116 lines; content restructured into incremental steps (auth-later per user selection). Follow-up (2026-08-28): Step 2 switched AppAuth -> **ForgeAppAuth** (full auth flow), file 2116 -> 2189 lines.
- Escaping: no literal `<?php`/`<?=` in file (all `&lt;?php`/`&lt;?=`); code-block `-&gt;`/`=&gt;` escaping applied (remaining literal `->`/`=>` only in HTML comments + `<script>` JS arrow functions, which must stay literal).
- API accuracy cross-checked against source: `Cast::{INT,BOOL,STRING}` (`Modules\ForgeSqlOrm\ORM\Values\Cast`), `HasTimeStamps` (`Modules\ForgeSqlOrm\Traits\HasTimeStamps`), `Todo::query()-&gt;id($id)-&gt;first()` (ModelQuery has `id()/first()`, no `find()`), `ForgeAuthService`, `EventDispatcher`, `queue:work`. Auth verified against `modules/ForgeAppAuth/` source (not AppAuth): `User` (`Modules\ForgeAppAuth\Models\User`), `UserContext::current(): ?AuthUserInterface` + `isAuthenticated()`, `AuthMiddleware`, full flow endpoints (`AuthController` prefix `/auth`, register/login/logout/forgot-password/reset-password), binds `UserProviderInterface` -> `UserRepository` + `UserContextInterface` -> `UserContext`, `#[PostInstall(db:migrate)]`. Follow-up (per user): replaced AppAuth with ForgeAppAuth throughout Step 2 (and Step 3 reactive controller import); removed AppAuth-only global `getCurrentUser()` from nav snippet (uses `$user` passed from controller) since ForgeAppAuth has no such helper.
- Links: started `python3 -m http.server 8831`; all 7 same-origin pages incl. tutorial returned `200` (api-reference, capabilities, core-concepts, getting-started, index, tutorial, tutorial-building-todo-app).
- Anchors: all 8 sidebar `href="#..."` resolve to section ids (introduction, project-setup, step-1-plain-app .. step-5-testing, putting-together).
- Tidy: `tidy -q -e` reports only benign empty `<i>`/breadcrumb `<li>` warnings shared across all pages; no structural/unclosed-tag errors.

---

## Phase 5 - Polish, Links & Verification

### Phase 5.1 - Cross-link & redirect pass

- Status: `PENDING`
- Pre-flight check: `forge-documentation/sitemap.xml`
- Changes:
  - Grep all `href="modules.html` -> `capabilities.html`, `href="#routing"` -> correct new page, fix duplicate sidebar IDs `forge-wire.html:212-219` duplicates `wire-protocol`.
  - Add redirects: `modules.html` -> `capabilities.html`, `getting-started.html#routing` -> `forge-router.html`, etc. via meta refresh or JS.
  - Regenerate `sitemap.xml`.
- Exit criteria: `grep -r "modules.html" forge-documentation/` returns only redirect stub; no 404s in `python3 -m http.server` crawl.

### Phase 5.2 - Docs outdated sweep

- Status: `PENDING`
- Pre-flight check: Re-read every changed HTML vs kernel source freshness timestamp (`kernel/Core/*` mtimes).
- Changes: Fix stale `APP_KEY` vs `JWT_SECRET` examples, `DB_DRIVER=sqlite` vs `database.driver` real keys, CLI flags `--type=app` etc. to match `php forge.php --help`.
- Exit criteria: Examples copy-paste work on fresh `bash <(curl -Ls ...)` install.

---

## Tracking

| Phase | Page / Scope | Status | Owner | Pre-flight Done | Date |
|-------|--------------|--------|-------|-----------------|------|
| 1.0 | Audit nav & kernel surface | DONE | audit | 2026-08-27 | 2026-08-27 |
| 1.1 | Nav in `index.html` + 4 stubs | DONE | build | 2026-08-27 | 2026-08-27 |
| 1.2 | `kernel-overview.html` | DONE | build | 2026-08-27 | 2026-08-27 |
| 1.3 | `anatomy.html` | DONE | build | 2026-08-27 | 2026-08-27 |
| 1.4 | `lifecycle.html` | DONE | build | 2026-08-27 | 2026-08-27 |
| 1.5 | `forging-your-own.html` | DONE | build | 2026-08-27 | 2026-08-27 |
| 2.0 | Capabilities audit | DONE | audit | 2026-08-27 | 2026-08-27 |
| 2.1 | `capabilities.html` | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2 | Rewrite catalog cap pages | IN PROGRESS |  |  |  |
| 2.2.0 | `forge-database-sql.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.1 | `forge-sql-orm.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.2 | `forge-error-handler.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.3 | `forge-auth.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.4 | `forge-events.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.5 | `forge-language.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.6 | `forge-multi-tenant.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.7 | `forge-wire.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.8 | `forge-htmx.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.9 | `forge-tailwind.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.10 | `forge-testing.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.11 | `forge-debugbar.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.12 | `forge-deployment.html` rewrite | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 2.2.13 | `forge-storage.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.14 | `forge-notification.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.15 | `forge-logger.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.16 | `forge-hub.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.17 | `forge-templates.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.18 | `forge-saas.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.19 | `forge-billing.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.20 | `forge-sprinkle.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.21 | `forge-sockets.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.22 | `forge-markdown.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.23 | `forge-admin-console.html` create | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.24 | `forge-view.html` create (Foundation) | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.25 | `forge-router.html` create (Foundation) | DONE | create | 2026-08-27 | 2026-08-27 |
| 2.2.26 | Catalog restructure (`catalog.html` new + concepts-only `capabilities.html`) | DONE | split+new | 2026-08-27 | 2026-08-27 |
| 2.3 | Promote PackageManager | DONE | rewrite+promote | 2026-08-27 | 2026-08-27 |
| 3.0 | Diff planned moves | DONE |  |  | 2026-08-27 |
| 3.1 | Slim `getting-started.html` | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 3.2 | Slim `core-concepts.html` | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 4.1 | `api-reference.html` clarify | DONE | rewrite | 2026-08-27 | 2026-08-27 |
| 4.2 | Tutorials re-label (todo recipe incremental) | DONE | rewrite | 2026-08-28 | 2026-08-28 |
| 5.1 | Links & redirects | PENDING |  |  |  |
| 5.2 | Outdated sweep | PENDING |  |  |  |

**How to update this file:** Change `PENDING` -> `IN PROGRESS` when starting a sub-phase, `IN PROGRESS` -> `DONE` when exit criteria met, add date + commit hash. Never mark `DONE` without pre-flight check and manual `python3 -m http.server` verification.

**Next up:** Phase 4.2 (todo tutorial incremental rewrite, incl. AppAuth -> ForgeAppAuth follow-up) is DONE. Remaining tutorial-housekeeping: add assembled-app intro callout to `tutorial.html` and confirm each card's dependency lists match real capability names (confirm tutorial pages reference `forge-app-auth`, not `app-auth`). Also outstanding: Phase 2.2.27 "Not listed" ForgeStatic callout, and the `forge-wire.html` page still uses deprecated `fw_id()` (research confirms current directive helper is `scope()`). Phases 3.2, 4.1, and 4.2 (todo) are DONE.

**Phase 2.1 Verification (2026-08-27):**
- User directive (per "this page does not need to be this big"): `capabilities.html` is a **tighter catalog**, not a mega-card page. It explains the *what/why/how* of capabilities as a system and defers per-capability detail to each capability's own page. So instead of the plan's "copy modules.html + fix cards", the page was **rewritten compact** (162 lines) with a role-based taxonomy.
- Rewritten `capabilities.html` = nav + 4 sidebar anchors (`#what-why-how`, `#capability-vs-module`, `#catalog`, `#examples`), What/Why/How section (what=capability, why=assemble not accept, how=Module metadata order/type/core + loader), Capability-vs-Module glossary table (`capabilities/`+`Capability\` vs `modules/`+`Modules\`, not enforced, gradual migration note), The Catalog grouped by role (Foundation / Application / Frontend / Tooling & Management — one-line entries linking to real `forge-*.html`; Router/View marked `page coming`; Sprinkles/Sockets marked `new`), Examples & Implementations (ForgeUi+ForgeComponents UI-lib examples, ForgeAppAuth+AppAuth business impls on ForgeAuth, ForgeWelcome, ForgeLanding) + "Not listed" callout for ForgeStatic* not-ready.
- `modules.html` replaced (backed up to `modules.html.bak`, 1420 lines) with a `<meta http-equiv="refresh" content="0; url=capabilities.html">` redirect (keeps site chrome for graceful transition; all 30+ inbound links forward automatically).
- All `href="modules.html"` → `href="capabilities.html"` updated across all doc pages (desktop nav + mobile menu; verified none remain).
- Applied audit findings: NO ForgeNexus (phantom dropped); ForgeUi normalized (not `ForgeUI`, framed as UI-lib example); duplicates gone (fresh page has no duplicate cards); ForgeStatic* excluded (not ready); ForgeRouter/ForgeView present in catalog (Foundation) though unlinked until Phase 2.2.
- `python3 -m http.server 8014` → all 38 doc pages HTTP 200; `modules.html` contains the redirect meta; all internal `.html` links + `assets/css/docs.css` on `capabilities.html` resolve to real files.

**Phase 2.1 pre-flight (2026-08-27):**
- `kernel/Core/Module/ModuleLoader/Loader.php:165` sort by `order` ascending; `:147` `type` = descriptive metadata only, defaults `'module'`; `:382-397` `core:true` modules load separately (after normal modules via `loadCoreModules`), `registerAllModules` skips core. Used verbatim in the "How" section.

**Phase 1.5 Verification (2026-08-27):**
- `python3 -m http.server 8011` → 200 `forging-your-own.html`; all 9 sidebar anchors (`#what-is-forge` `#repositories` `#package-sources` `#trusted-sources` `#installer` `#install-php` `#blueprint-updates` `#prefix-rename` `#done`) resolve to defined sections; no `IN PROGRESS`/stub banner; code-block `<?php` correctly escaped (`&lt;?php`); no `file:line` citations or stale `docs/FORGING-YOUR-OWN:1-181` refs.
- Facts verified against source: `install.php` root `FRAMEWORK_REPO_URL` constant; `config/source_list.php` is the package-manager capability's config (auto-created on first run, `ForgePackageManagerModule.php:63-81`, default official registry via `env('GITHUB_TOKEN','')`); `storage/framework/trusted_sources.json` exists; `config/registry.php` shape.
- `FORGE_DEVELOPER_MODE` dev-registry commands deliberately NOT included (package-manager/dev-tool domain, not the forking story) per kernel/capability-focus guardrail.

**Phase 1.4 Verification (2026-08-27):**
- Web flow traced from source: `public/index.php` → `Kernel::init()` → `Bootstrap::getInstance()` → `ContainerAppSetup::setup()` (core services → EARLY_BOOT → `ModuleSetup::loadModules()` → error handler → `ServiceDiscoverSetup` → APP_BOOTED → `finishBootstrap`); ForgeRouter answers web requests via `#[LifecycleHook(APP_BOOTED)] boot()` (`ForgeRouterModule.php:139,169-195`) → `Kernel->handler()` → `response->send()`.
- CLI flow: `forge.php` → `Bootstrap::initCliContainer()` → `ContainerCLISetup::setup()` (modules + `preloadCliModules` → services → `AppCommandSetup::init()` → `Application::run()`).
- Stub's stale `#[Discoverable]`/`#[Service]`/`class-map.php` removed; autoloader cache is `class_file_map.php` (`Autoloader.php:379`).

**Previous:** Phase 1.3 — `anatomy.html` full content (folder structure, customizable via `forge_structure.php`). Pre-flight: `kernel/Core/Structure/`, `StructureInitCommand.php`/`StructureInfoCommand.php`, `Config.php`.

**Phase 1.3 Verification (2026-08-27):**
- `python3 -m http.server 8005` → 200 `anatomy.html` + `kernel-overview.html`; 8 sidebar anchors present; `kernel-overview.html` → `anatomy.html#custom-structure` resolves.
- Facts documented: `forge.json` manifest, `forge.php` CLI, `install.php` (URL+integrity+zips into `kernel/`), root `index.php` = 403 guard vs `public/index.php` = real entry, `.env` keys, `config/` files (`forge_router.php`/`middleware.php`/`registry.php`), storage dirs (`Bootstrap.php:25-33` incl. `trusted_sources.json`), `#[Structure]` sovereignty, `structure:init` (5 modes + merge + warnings `StructureInitCommand.php:47-56`) / `structure:info` (interactive + `structure.php` legacy alias `StructureInfoCommand.php:43-44`), `forge.lock.json` pin/integrity.

**Previous:** Phase 1.2 — `kernel-overview.html` full content (What's in / NOT, Contracts vs Implementations). Pre-flight: `kernel/Core/Contracts/`, `kernel/README.md`.

**Phase 1.2 Verification (2026-08-27):**
- `python3 -m http.server 8003` → 200 `kernel-overview.html`; 7 sidebar anchors; `grep -o 'DatabaseConnectionInterface'` 3 hits; `grep -o 'kernel/.*\.php'` 15 verified citations
- Content cites: `kernel/README.md:3-6,14-26,27-42`, `Kernel.php:18`, `Bootstrap.php:40`, `Container.php:22`, `Injectable.php:10`, `Loader.php:74`, `Autoloader.php:44`, `Contracts/DatabaseConnectionInterface.php:11`, `helpers.php:13`, `forge_structure.php:32-33`, `StructureResolver.php:11`

**Previous:** Phase 1.2 — `kernel-overview.html` full content (What's in / NOT, Contracts vs Implementations). Pre-flight: `kernel/Core/Contracts/`, `kernel/README.md`.

**Phase 1.1 Verification (2026-08-27):**
- `grep -n 'Kernel' forge-documentation/index.html` → dropdown present; `index.html:52,137` now `Capabilities` in both desktop+mobile (bug fixed)
- `python3 -m http.server 8001` → 200 for `index.html`, `kernel-overview.html`, `anatomy.html`, `lifecycle.html`, `forging-your-own.html`
- Stubs contain verified citations: `kernel/Core/Kernel.php:18`, `forge_structure.php:32-33`, `StructureResolver.php:13`, `README.md:5`

**Phase 1.0 Verification (2026-08-27):**

**Phase 1.0 Verification (2026-08-27):**
- `grep -rn 'href="modules.html' forge-documentation/` => 30+ hits confirmed, only `index.html:118` mobile text mismatch `Modules` vs `Capabilities`.
- `ls modules/ | wc -l` => 30, `ls capabilities/ | wc -l` => 1, `kernel/Core/Structure/forge_structure.php:32` confirms both roots supported.
- `cat kernel/README.md` lists 11 kernel responsibilities, zero router/view/db.
