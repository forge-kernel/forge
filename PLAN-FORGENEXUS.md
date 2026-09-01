# PLAN - ForgeNexus: A Standalone CMS Project on the Forge Kernel

> **Goal:** Build **ForgeNexus** — a **standalone CMS project** assembled only from Forge capabilities, that you can spin up locally or online, author documentation/content via a **customized admin panel**, and then **generate a static HTML version** to deploy on any static host. Because it's a complete, running app built from the tiny kernel + capabilities, it also **serves as a blueprint / reference implementation** for anyone who wants their own CMS-with-static-gen.
>
> ForgeNexus is not a framework, and it is not "blueprint-only" scaffolding — it is a real, self-contained product. It demonstrates the kernel's philosophy: take the tiny kernel, assemble the capabilities you need, and ship a working app. **NovaSpace** (`/Users/acidlake/Development/NovaSpace/repos/novaspace`) is the reference for "well-built & customized" auth, uploads, and admin — we reuse its proven patterns.

---

## 0. Overview

### Why
`forge-documentation/` is 52 hand-written `.html` files. We want to dogfood the kernel: run ForgeNexus locally, author those docs through a small admin panel as Markdown, then run **one command** to emit a static HTML site deployable to any static host (GitHub Pages, Netlify, S3, nginx static, etc.).

### What ForgeNexus is
A **standalone project** that assembles three things; because it's a real, complete app it doubles as a blueprint/reference:

1. **A complete CMS app** — a working product you can run and deploy, with the customized application logic it needs:
   - **AdminConsole → Nexus Admin** (customized, app-level) — the authoring/management UI.
   - **ForgeAppAuth → Nexus Auth** (customized, app-level) — tailored accounts flow.
2. **A CMS module** — docs/app-personalization logic: block content models (EditorJS), public renderer, media/uploads, and a **static build command**.
3. **A blueprint reference** — because it's a fully working standalone app assembled from the tiny kernel + capabilities, it is itself the recipe/template others copy to make their own CMS-with-static-gen.

### The NovaSpace reference (why it fits perfectly)
NovaSpace is a full app on this kernel with custom auth, uploads, media, content/journaling, and a big admin surface — all built the "assemble a standalone app" way. We lift its proven patterns instead of inventing:
- **Auth customization:** NovaSpace overrides stock ForgeAppAuth with its own `AuthEndpoint` route table (login/register/logout/forgot/recover) reusing `ForgeAuthService::verifyCredentials/register`, plus matrix-card 2FA, proof-of-work, trusted devices, and account recovery — see `novaspace/Http/Endpoints/Web/Auth/AuthEndpoint.php`. We don't need the matrix/2FA, but we copy the **shape**: auth lives in the app root, wired through `config/middleware.php` `auth` group, and bound via a module (`NovaAuthModule`).
- **Uploads/storage:** two-tier — `ForgeStorage` (local driver, `/storage/...` symlink public URL, signature-gated `/__upload`) for editor/artifacts, and a **media library** (NovaMedia analog) with per-kind validation (`nova_media.allowed.*`), auto-resize→**WebP**, DB-tracked items, and visibility-gated serving (`/media/library/{id}`, `/media/public/{id}`) — see `modules/NovaMedia/src/Services/MediaLibrary.php`.
- **Content:** contracts → app providers (`modules/NovaPublications` binds `PublicationRepository` → `NovaSpace\Data\Providers\PublicationProvider`); ORM models + migrations in the app `Data/Records`/`Data/Migrations`; authoring in `Http/Endpoints/Web/`; WYSIWYG editor via a module component.
- **Assembly:** `forge_structure.php` (`app_root:'novaspace'`, `app_namespace:'NovaSpace'`), `forge.json` pins kernel capability versions, local `modules/Nova*` + capability `capabilities/Forge*`, module `register()` binds interfaces→app providers, `#[Requires]` forms a dependency graph.

---

## 1. Standalone Project Structure & Assembly

### 1.0 Repositories & standalone setup (DECIDED)
ForgeNexus is built as a **new standalone repo, NOT a submodule of forge-v3 and NOT inside it**:

- **Location:** new sibling folder `/Users/acidlake/Development/UPPER/Forge/repos/forgenexus` (fresh `git init`, self-contained like NovaSpace).
- **Why not inside forge-v3:** forge-v3 is the kernel "kitchen sink" (old conventions, 34 modules). ForgeNexus is a standalone project on the new conventions and should not mix with it.
- **Why not a submodule:** matches the existing pattern (NovaSpace is its own repo with its own `kernel/` + `capabilities/`, not a submodule).
- **Kernel + capabilities: phased COPY→refactor from forge-v3** (fast, gets our fixes + new caps; clean namespace as we go):
  - **P0 first:** create the empty project shell **with 0 modules and 0 capabilities** — `kernel/` + `forge_structure.php` + minimal `forge.json` + empty `nexus/` app root, booting the bare kernel. See Phase P0.
  - `kernel/` → copied from forge-v3 (stays generic, no namespace refactor needed).
  - The needed capabilities are copied from `forge-v3/modules/` **per phase** (not all at once), starting from the empty P0 shell. Each capability ForgeNexus actually uses, at the phase that needs it, is:
    - **copied** from `forge-v3/modules/{Cap}` into `capabilities/{Cap}`, then
    - **refactored** to the new convention: directory `capabilities/{Cap}` + rename `namespace Modules\{Cap}` → `Capability\{Cap}` (and fix all `Modules\{Cap}\` references/middleware/container registrations) — so the capability is genuinely `Capability\`, reusable by any app.
  - So `forge.json` starts minimal (0 caps at P0) and grows each phase; we never bulk-copy ALL 34 forge-v3 modules. Sequence follows the phase plan: router/view first, then DB/ORM, auth, content (MarkDown/Templates/Storage), admin (AdminConsole/AppAuth/Sprinkle), build (StaticGen/StaticHtml), testing/error-handler/package-manager as needed. (+ optional `ForgeEvents, ForgeHtmx[from NovaSpace], ForgeWire`.)
  - The forge-v3-only `modules/` (MarkDown, Templates, StaticGen/StaticHtml, AdminConsole, AppAuth, Sprinkle, Storage) are **not in NovaSpace's registry**, so forge-v3 is the only source for those.
- **Capabilities dir + namespace (DECIDED):** generic/reusable capabilities live under **`capabilities/`** AND are authored **`Capability\`**. The autoloader registers every namespace prefix (`Modules`, `Capability`) over every module dir, so folder/namespace are decoupled — but per the phased-copy convention above, every copy is refactored to `Capability\{Cap}` + `capabilities/{Cap}` so the new repo is fully new-convention (no straggler `Modules\Forge*`). Any capability refactored once lives in forge-v3's `modules/` still (that repo stays legacy); the *namespace/dir migration happens in forgenexus*. If a refactor is non-trivial, flag it and keep the internal `Modules\Forge*` namespace as a stated exception for that cap.
- **Declaration:** list required capabilities in `forge.json`, growing phase-by-phase (add each `Capability\{Cap}` as it's copied). The **lock file is auto-generated** — do NOT hand-edit `forge-lock.json`.
- **Upstreams:** `forge-v3` remains the upstream source of truth for the kernel/capabilities source (we copy from it); `forgenexus` is the app + the namespace/dir migration target. Optionally record a `REFS.md`/README note pointing at both forge-v3 and novaspace.

### 1.1 Code placement convention (DECIDED)
Where each piece of code lives depends on its reuse level. How autoloading works: the kernel registers **every** namespace prefix (`Modules`, `Capability`) as a PSR-4 root over **every** module dir (`modules/` and `capabilities/`) — so the folder and the declared namespace are decoupled. A file is called what its `namespace` line says, and it can live in either dir. Three tiers:

1. **Generic standalone capabilities — `Capability\` (authored) + `capabilities/`**
   A new capability reusable by **any app** is authored with the **`Capability\` namespace** and lives under `capabilities/` (new-conventions, NovaSpace style — e.g. NovaSpace's `capabilities/ForgeHtmx`, which declares `namespace Capability\ForgeHtmx;`). E.g. a fully generic "block CMS" or "static builder" that another app could pull in. These are the shared, app-agnostic building blocks.
2. **Application modules — `Modules\` + `modules/`**
   Reusable **application/business logic** that is still module-shaped but app-specific (not generic enough to be a capability). E.g. a `NexusCms` business module, per-product business modules. Author as `Modules\Nexus*`.
3. **App-root code — `Nexus\` + `nexus/`**
   The application's own wiring, glue, controllers, models, and views. This is where application logic that shouldn't be a module lives (the "app has more code than its modules" reality).

**Guiding rule for ForgeNexus:**
- If you'd reuse a feature in a completely different app → **author a generic capability** (`Capability\`/`capabilities/`).
- If it's app-facing business logic worth encapsulating as a module → **author a module** (`Modules\Nexus*`/`modules/`).
- Otherwise → keep it in the **app root** (`Nexus\`/`nexus/`).

**A note on copies (phased copy→refactor):** forge-v3 keeps its capabilities under `modules/` with `namespace Modules\Forge...`. When a capability is copied into forgenexus it is **refactored on the spot** to the new convention — moved to `capabilities/{Cap}` and re-declared `namespace Capability\{Cap}`, with all `Modules\{Cap}\` references (use sites, middleware, container dial set) updated to `Capability\{Cap}\`. So there are no straggler `Modules\Forge*` references in ForgeNexus; every copied cap ends up genuinely `Capability\`. This is done **per phase**, at the moment the capability is first needed (see P1 start + each phase's cap list). If a refactor proves non-trivial/sweeping, it's flagged and the internal namespace is kept as a stated, documented exception for that cap. ForgeNexus is a **mixed approach**: an app root (`Nexus\`) + a few app modules (`Modules\Nexus*` — application business, genuinely `Modules\`) + generic capabilities (`Capability\*` in `capabilities/`).



```
forge.json                 # required capabilities + versions
forge_structure.php        # app_root='nexus', app_namespace='Nexus',
                           #   modules_root=['modules','capabilities'] (default),
                           #   modules_namespace=['Modules','Capability'] (default)
capabilities/              # TIER 1: generic caps, copied per phase from forge-v3
                           #   modules/ and REFACTORED here to Capability\{Cap}
  ForgeRouter ForgeView ForgeDatabaseSQL ForgeSqlOrm ForgeAuth
  ForgeAppAuth ForgeAdminConsole ForgeMarkDown ForgeTemplates ForgeStorage
  ForgeErrorHandler ForgePackageManager ForgeSprinkle ForgeTesting  (+ optional ForgeEvents/Htmx/Wire)
nexus/                     # TIER 3: the app root, app_namespace='Nexus' (customized logic)
  Http/Endpoints/          #   web controllers (public + admin)
  Http/Middlewares/        #   Nexus auth middleware, etc.
  Data/Records/            #   ORM models (Page, Post, Asset)
  Data/Providers/          #   repositories
  Data/Migrations/         #   (auto-run)
  Data/Seeders/
  Injectable/              #   services (NexusContentService, NexusRenderer, NexusBuilder)
  Common/                  #   config helpers, route constants, requirements, templates
  UI/views/                #   layouts/ pages/ components/  (customized admin UI)
  tests/
modules/                   # TIER 2: app business modules (Modules\ Nexus*), optional
  NexusCms/ NexusAuth/ NexusMedia/ ...
```

### 1.2 Required capabilities (all verified present in the kernel)
| Capability | Role in ForgeNexus |
|---|---|
| **ForgeRouter** + **ForgeView** | routing + views (public + admin) |
| **ForgeDatabaseSQL** + **ForgeSqlOrm** | models/migrations (`Page`/`Post`/`Asset`) |
| **ForgeAuth** | auth primitives/engine (`ForgeAuthService::register/verifyCredentials/login`) |
| **ForgeAppAuth** | base account flow — **customized & renamed** as Nexus Auth |
| **ForgeAdminConsole** | admin shell/layout + `auth` middleware pattern — **customized & renamed** as Nexus Admin |
| **ForgeMarkDown** | `parse(): string` / `parseFile(): array` (content + front matter) |
| **ForgeTemplates** | `TemplateManager::useTemplate()` + `layout()`/`slot()` for page layouts |
| **ForgeStorage** | file uploads (`UploadService`, local driver, `/storage/...`, `/__upload`) |
| **ForgePackageManager** | install modules, `package:install-module` / install project blueprint |
| **ForgeErrorHandler** | error pages (custom 404/500) |
| **ForgeEvents** | optional: emit `PagePublished`/`SiteBuilt` events, async listeners |
| **ForgeSprinkle** | progressive table-enhancement (sort/search on admin lists) without a JS framework |
| **ForgeTesting** | `TestCase` + HTTP/DB assertions for the app |
| **ForgeHtmx** (optional) | partial swaps for admin forms/lists (used by NovaSpace) |
| **ForgeWire** (optional / more complex) | full reactivity in the admin if we want server-state UI |

### 1.3 Data model
ORM models extend `Capability\ForgeSqlOrm\ORM\Model` (`#[Table]`/`#[Column]`, `Cast::{INT,STRING,BOOL}`, `HasTimeStamps`). No `find()` — use `id($id)->first()`.

**Block content is file-backed.** EditorJS blocks are not stored in a JSON column — they live as **JSON files named by UUID**, and the DB stores only the file reference. This keeps the DB rows small, makes content portable/version-controllable, enables atomic writes, and matches how the media library stores binaries on disk with DB-tracked references.

- **`nexus_pages`** — `id, title, slug (unique), content_ref (uuid/filename of the blocks JSON file), excerpt, layout (default 'page'), featured_image (url), published (bool), author_id`. Page content, ordered for sidebar.
- **`nexus_posts`** — `id, title, slug, content_ref (uuid/filename), excerpt, author_id, published, published_at`. Blog/news feed.
- **`nexus_assets`** — media items (NovaMedia-style): `id (uuid), owner_id, category, kind, storage_path, url, mime_type, size, original_name, metadata (JSON), visibility`.
- **`nexus_nav`** (optional) — ordered sidebar/nav entries linking pages.


### 1.4 Rendering pipeline
```
NexusContentService (DB CRUD; block content persisted to JSON files by UUID)
   └─ NexusContentStore  - read/write content_ref -> storage/content/{uuid}.json
   └─ NexusRenderer: blocks = json_decode(file content_ref); render block->HTML
        (EditorJS blocks)  ; optional ForgeMarkDown as a non-block content mode
        └─ TemplateManager::useTemplate($layout, [html, slots, nav, head])
             └─ layout in nexus/UI/views/layouts (or app templates)
   └─ NexusBuilder (static snapshot) → ForgeStaticHtml StaticGenerator crawl
```
Admin authoring produces **EditorJS blocks** (JSON), stored as a **UUID-named JSON file** on disk with the DB row holding only `content_ref`. The renderer loads the file by ref, deserializes the blocks, and renders each block type (paragraph, heading, image, code, list, etc.) to HTML at request time. `nexus:build` crawls the live site into `public/static`.

---

## 2. Status Board

| Status | Phase | Deliverable |
|--------|-------|-------------|
| **✅ Done** | P0 | Project setup — create the forgenexus repo, kernel + forge_structure, minimal manifest, empty app root (0 modules / 0 capabilities) |
| **↻ In progress** | P1 | First capability copy→refactor + data model (Page/Post/Asset + content JSON files + migrations) |
| **○ Todo** | P1 | First capability copy→refactor + data model (Page/Post/Asset + content JSON files + migrations) |
| **○ Todo** | P2 | Content store + public renderer (EditorJS blocks → HTML → layout/slots) |
| **○ Todo** | P3 | Nexus Admin (customized AdminConsole) — page/post CRUD + EditorJS |
| **○ Todo** | P4 | Nexus Auth (customized ForgeAppAuth) — accounts flow |
| **○ Todo** | P5 | Media/uploads (ForgeStorage + media library, auto-WebP) |
| **○ Todo** | P6 | Static build command (`nexus:build`) → static-host-ready output |
| **○ Pending** | P7 | Dogfood: migrate `forge-documentation/` to block content |
| **○ Pending** | P8 | Polish, events, Sprinkle/HTMX admin, tests, seeds |

---

## 3. Phase Breakdown

### P0 - Project setup — DONE
**Task:** Create the standalone project directory and do the **initial setup with 0 modules and 0 capabilities** — the empty app skeleton only. The project must boot the bare kernel before any capability is introduced.
- [x] Create `/Users/acidlake/Development/UPPER/Forge/repos/forgenexus` (fresh `git init`, not a submodule, not inside forge-v3).
- [x] Copy `kernel/` from forge-v3 (the only kernel source of truth).
- [x] `forge_structure.php`: `app_root='nexus'`, `app_namespace='Nexus'`; module roots = defaults `['modules','capabilities']`, namespaces `['Modules','Capability']`;
- [x] Minimal `forge.json` (**0 modules / 0 capabilities** listed — empty manifest). No `forge-lock.json` needed at boot (package-manager/installer artifact only); generated if/when a resolver runs.
- [x] Empty `nexus/` app root scaffolding (controllers/middlewares/models/views dirs) with **no capabilities and no modules** wired yet; empty `modules/` + `capabilities/` dirs.
- [x] Entry files + smoke boot: `kernel/` + `public/index.php` + root `index.php` + `forge.php` + `config/` copied from forge-v3; `.env` created from `env-example`. `install.php` copied to the repo root as the **kernel standalone install** (reads `forge.json`, pulls/updates `kernel/` from the `forge-kernel/kernel-registry` repo on a fresh clone). The separate `installer/` blueprint scaffolder is NOT part of the root install flow and is not copied.
- **Verify (DONE):** `php forge.php` boots with 0 modules + 0 capabilities; `structure:info` reports `modules_root: modules, capabilities`, `modules_namespace: Modules, Capability`, `app_root: nexus`, `app_namespace: Nexus`; web boot via `php -S` on `public/` returns HTTP 200 (no fatal with empty manifest); repo self-contained (standalone, git-initialized).

### P1 - First capability copy→refactor + data model — IN PROGRESS
**Task:** Standing on the P0 empty skeleton, introduce the first tranche of capabilities (copy from forge-v3 `modules/`, **refactor to `Capability\`**), then add models/migrations and the file-backed content store.
- [x] **`ForgeRouter`** copied `modules/ForgeRouter` → `capabilities/ForgeRouter` and refactored `Modules\ForgeRouter` → `Capability\ForgeRouter` (243 refs across 82 files; `Modules\ForgeTesting` + `Modules\ForgeDatabaseSQL` refs preserved for later). `config/middleware.php` ForgeRouter refs updated; dangling Htmx/Wire/AppAuth/Auth/Hub middleware entries trimmed (re-added in their phases). Boots: CLI lists `serve`/`forge-router:init`, web returns 404 (router active, no routes yet). **Note:** the copied `kernel/` still has internal `Modules\ForgeRouter` string/`use` references (e.g. `ErrorHandlerSetup.php:29`, stubs, tests) — those fall back gracefully at boot for now; reconciling the kernel copy's `Modules\Forge*` refs to `Capability\Forge*` is a tracked follow-up.
- [x] **`ForgeView`** copied `modules/ForgeView` → `capabilities/ForgeView` and refactored `Modules\ForgeView` → `Capability\ForgeView` (19 refs / 9 files); cross-refs to the already-refactored Router in `src/Traits/ViewHelper.php` updated `Modules\ForgeRouter` → `Capability\ForgeRouter`; `Modules\ForgeTesting` refs preserved. Loads clean (no commands of its own).
- [x] `forge.json` updated (so far): `forge-router` + `forge-view` registered under `capabilities`.
- [ ] **P1 remaining capability copy→refactor:** `ForgeDatabaseSQL`, `ForgeSqlOrm`, `ForgePackageManager`, `ForgeTemplates`, `ForgeErrorHandler` (MarkDown/Storage when content phase arrives). Refactor = rename `namespace Modules\{Cap}` → `Capability\{Cap}`, move dir to `capabilities/{Cap}`, and update every `Modules\{Cap}\` reference (use sites, middleware, container dial set).
- [ ] `forge.json` updated to list the caps copied so far (grows each phase): `Capability\ForgeRouter, ForgeView, ForgeDatabaseSQL, ForgeSqlOrm, ForgePackageManager, ForgeTemplates, ForgeErrorHandler`. Leave `forge-lock.json` auto-generated.
- [ ] `#[Module(name:'ForgeNexus', ...)]` app module + `#[Requires(...)]` graph over the `Capability\*` caps; `register()` binds `NexusContentService`/`NexusContentStore`/`NexusRenderer`/`NexusBuilder`.
- [ ] `Page`/`Post`/`Asset` ORM models (extending `Capability\ForgeSqlOrm\ORM\Model`, with `content_ref` pointing to a UUID JSON file) + attribute-driven migrations, auto-run via `#[PostInstall(db:migrate --type=module/--type=app)]`.
- [ ] `NexusContentStore`: save/load/delete blocks file at `storage/content/{uuid}.json` (`@return` the `content_ref`), atomic write (temp + rename), orphan cleanup.
- **Verify:** refactored caps load under `Capability\` (`structure:info` / boot log shows no `Modules\Forge*`); `package:install-module --module=nexus-*` mounts; `db:migrate` creates `nexus_pages`/`nexus_posts`/`nexus_assets`; a page row can point to a written `storage/content/<uuid>.json`; `structure:info` reflects the renamed app root.

### P2 - Content store + public renderer — TODO
**Task:** Serve `/{slug}` from EditorJS block files through Templates (layout/slots/nav).
- [ ] `NexusContentStore`: read `content_ref` file → decode blocks JSON.
- [ ] `NexusRenderer`: block→HTML renderer (paragraph, header, image, list, quote, code, checkbox, delimiter, etc.), compose via `TemplateManager::useTemplate` + `layout()`/`slot()`, nav from `nexus_nav`/page ordering. (Non-block mode via `ForgeMarkDown::parse` optional.)
- [ ] `#[Routable] #[Endpoint('/{slug}')]` public page (404/redirect on unpublished/draft; optional `/posts` feed).
- [ ] Layouts in `nexus/UI/views/layouts`: `root`, `page`, `home`, `post` (parent-layout chain like NovaSpace).
- **Verify:** `php forge.php serve` → authored page (blocks file) renders styled HTML.

### P3 - Nexus Admin (customized ForgeAdminConsole) — TODO
**Task:** The authoring/management UI — an app-level, renamed version of AdminConsole.
- [ ] Admin controllers in `nexus/Http/Endpoints/Web/Admin/` (model AdminConsole's `Dashboard.php` pattern: `#[Routable] #[UseMiddleware(['web','auth'])] #[Layout('Nexus:wrappers/admin-default')]`), routes under `/admin`.
- [ ] `GET/POST /admin/pages` (list + create), `/admin/pages/{id}/edit`, publish toggle, delete; the same for `/admin/posts`.
- [ ] **EditorJS** block editor in the admin: on save, serialize blocks → JSON → `NexusContentStore->save()` writes `storage/content/{uuid}.json` and returns `content_ref`; store ref on the row.
- [ ] Re-skin: rename brand/layout from "Admin" → "Nexus"; admin nav entries for Pages / Posts / Assets.
- **Verify:** log in (P4 auth), author + publish a page, see it live at its slug.

### P4 - Nexus Auth (customized ForgeAppAuth) — TODO
**Task:** Accounts flow tailored to a public docs CMS — modeled on NovaSpace's custom `AuthEndpoint` (without the matrix/2FA weight).
- [ ] App-level `NexusAuth` controller for `/auth/register|login|logout|forgot-password|reset-password`, reusing `ForgeAuthService::register/verifyCredentials`.
- [ ] `config/middleware.php` `auth` group → Nexus `AuthMiddleware` (redirect unauthenticated → `/auth/login`, intended-URL, 401).
- [ ] Bind `UserProviderInterface`/`UserContextInterface` to Nexus providers (NovaAuth-module pattern) OR rely on ForgeAppAuth binds.
- [ ] Only authors/admins get CMS access (role check on admin group).
- **Verify:** full register→login→logout→forgot loop; author-only admin gating.

### P5 - Media & uploads (ForgeStorage + media library) — TODO
**Task:** image uploads for page/post featured images, assets, and the docs. NovaSpace two-tier:
- [ ] **Tier A — ForgeStorage:** `storage:link` → `public/storage`; use `UploadService`/`LocalDriver` for simple uploads; validate via `FileValidator`.
- [ ] **Tier B — Nexus media library** (NovaMedia analog): models `nexus_assets`, per-kind validation (images/fonts), auto-resize→WebP via an image processor, visibility-gated serving `/media/library/{id}` + `/media/public/{id}` (ETag/Range/Cache-Control).
- [ ] `uploader`/`picker` components (copy NovaMedia's XHR `FormData` + `X-CSRF-TOKEN` upload pattern) reusable from admin.
- **Verify:** upload an image in admin, resize to WebP, serve, inline into a Markdown page.

### P6 - Static build command — TODO
**Task:** `php forge.php nexus:build` snapshots the site to `public/static` for any static host.
- [ ] `nexus:build` command (`#[Cli(command:'nexus:build')]` via `RegistersCommands`).
- [ ] Seed `ForgeStaticHtml\StaticGenerator` config: `dynamic_routes['/{slug}']` from the DB + `include_paths`, base_url, max_depth; let its crawler emit `/<slug>/index.html` and copy assets (CSS/JS/images from `public/`).
- [ ] Handle absolute vs relative asset base so the output works from a sub-path static host / file:// preview.
- [ ] Optional `--source=md`: export pages to Markdown dir for a pure-`site:build` path (recurse `modules/ForgeStaticGen`).
- [ ] Emit a `nexus:build` success report (pages written, bytes, duration).
- **Verify:** command exits 0; `public/static/<slug>/index.html` exists; copy `public/static` to a plain static host (or open locally) — links/nav/images resolve.

### P7 - Dogfood: migrate forge-documentation — PENDING
**Task:** Actually replace the 52 static docs with ForgeNexus content (the payoff / proof).
- [ ] Port one page (e.g. `core-concepts`) from HTML → an EditorJS block set (via `ForgeMarkDown` parse then block conversion, or direct blocks) + a docs layout; render; diff against current.
- [ ] Build the **docs layout** (sidebar nav, TOC, breadcrumbs) as `nexus/UI/views/layouts/` + ordered `nexus_pages`.
- [ ] Add a **Markdown→blocks importer** so existing docs become EditorJS JSON block files under the same `content_ref` scheme.
- [ ] Add table-of-contents + internal-link validation to the renderer as the migration surfaces needs.
- [ ] Stepwise port of remaining pages; preserve URLs (slug == current filename) so cross-links hold.
- [ ] Sidebar ordering via `nexus_nav` / sort_order column matching the current docs sidebar.
- **Gate:** starts only after P1–P6 verify; feeds discovered layout/sidebar/a11y needs back into ForgeNexus.

### P8 - Polish, events, admin DX, tests, seeds — PENDING
- [ ] `nexus:setup` seed command: default layouts + a sample home page + default nav.
- [ ] **ForgeEvents:** dispatch `PagePublished` / `NexusSiteBuilt` events; async listener to re-trigger `nexus:build` or invalidate caches.
- [ ] **Admin DX:** ForgeSprinkle (sort/search enhancements on admin tables) and/or ForgeWire/ForgeHtmx partial-swap forms; ForgeErrorHandler custom 404/500 pages.
- [ ] Validation: slug uniqueness + allowed chars (reuse `#[Validate]` style rules if ForgeWire adopted).
- [ ] `Capability\ForgeTesting\TestCase` test class: `assertDatabaseHas('nexus_pages', ...)`, HTTP GET page render, `nexus:build` smoke test, admin gating test.
- [ ] Auth hardening review (CSRF, session, upload validation, rate limiting).

---

## 4. Key Decisions / Open Questions
1. **Primary static path:** recommend **ForgeStaticHtml** crawl of the live router (precise, asset-aware, DB-driven routes) with **ForgeStaticGen** (`--source=md`) as a secondary pure-export path. *Confirm.*
2. **Renames/customizations:** the customized surfaces (AdminConsole→Nexus Admin UI, ForgeAppAuth→Nexus Auth) are **app-owned** — delivered as app code (in the `nexus/` app root and/or a `Modules\Nexus*` business module), NOT by editing the copied `Capability\*` files. The copied capabilities stay generic and reusable. *Confirm.*
3. **Content editor:** **EditorJS for blocks** — a block-based editor (paragraphs, headings, images, code, lists, etc.) that serializes to JSON. Blocks are stored as **UUID-named JSON files** (`storage/content/{uuid}.json`) referenced from the DB via `content_ref` — not a JSON column (smaller rows, portable, atomic writes). A `ForgeMarkDown` non-block mode is an optional secondary path (useful for the doc-migration export). *Confirmed.*
4. **Auth depth:** copy NovaSpace's full custom flow (PoW/2FA/recovery) or just the tailored `<brand>Auth` flow (register/login/logout/forgot/reset)? Recommend the latter for v1 — those go far beyond a docs tool. *Confirm.*
5. **Admin interactive layer:** ForgeWire (full reactivity) vs ForgeHtmx (partial swaps) vs ForgeSprinkle (no-JS) for the admin lists/forms. Recommend **ForgeSprinkle + ForgeHtmx** for light, progressive admin UX; ForgeWire optional for a complex admin. *Confirm.*
6. **Content scope:** Pages + Posts + Assets in v1, or Pages-only first? Recommend Pages+Assets first (docs use case), Posts after.
7. **Repo/standalone:** build in a **new standalone repo** `/Users/acidlake/Development/UPPER/Forge/repos/forgenexus` (not a submodule, not inside forge-v3). Kernel + capabilities **copied from forge-v3** (the only source with the forge-v3-only caps ForgeNexus needs); declared in `forge.json` (lock file auto-generated). **P0 creates the empty shell first (0 modules / 0 capabilities)**, then capabilities are added per phase. *Confirmed.*
8. **Capabilities dir + namespace:** generic/reusable capabilities live under **`capabilities/`** AND are authored as **`Capability\`** (NovaSpace style). Folder and namespace are decoupled (the autoloader maps every prefix — `Modules`, `Capability` — over every module dir), so a cap is genuinely `Capability\` only when its source declares that namespace. *Confirmed.*
9. **Code placement (capability vs module vs app):** generic reusable capability → **`Capability\`/`capabilities/`**; app business logic worth modularizing → **`Modules\`/`modules/`**; otherwise app-specific wiring/controllers/models → **app root `Nexus\`/`nexus/`**. ForgeNexus uses a **mixed** app + modules + capabilities approach. *Confirmed.*
10. **Phased copy→refactor of capabilities:** starting from the **P0 empty shell (0 modules / 0 capabilities)**, we **do not bulk-copy** all forge-v3 modules. Instead, for each phase we copy only the capability(ies) that phase needs from forge-v3 `modules/{Cap}` into `capabilities/{Cap}` and **refactor on the spot** to `Capability\{Cap}` (rename namespace, update every `Modules\{Cap}\` reference). `forge.json` grows each phase. Any non-trivial refactor is flagged and kept as a documented exception. *Confirmed.*
11. **Kernel decoupling (error handler) — kernel-owned contract + module self-binds:** the kernel no longer references any capability namespace. Added `Forge\Core\Contracts\ErrorHandlerInterface`; `ErrorHandlerSetup` discovers it by the kernel's own `::class`; the error-handler module self-binds in `register()`/`#[Provides]`; the Router-local interface was deleted and ForgeRouter now uses the kernel contract; `ContainerCLISetup` reordered so the handler is setup after modules load (fixes CLI path). `config/registry.php` left exclusively for git package registries (reverted the earlier `error_handler` key). Details in §5b. *Confirmed & executed.*

---

## 5. Risks / Notes
- **No SSG in the NovaSpace reference** — its `deploy/` is nginx dynamic. ForgeNexus's static shell is *new*; NovaSpace is the model only for everything up to the build. Sanity-check the static output manually (asset base, sub-path hosting).
- **ForgeStaticGen path quirk:** its `getHtmlFilePath()` hardcodes `BASE_PATH.'/docs/'` as the content base — don't rely on it as the primary builder path (ForgeStaticHtml avoids this).
- **Container is explicit** — every Nexus service must be `register()`'d; no auto-discovery.
- **No `find()`** — always `id($id)->first()`.
- **Slug → static file collisions** — `/{slug}` must map to `/<slug>/index.html` cleanly (ForgeStaticHtml already does this).
- **Assets in static output** — ForgeStorage serves dragfiles via a `public/storage` symlink; ensure the static snapshot copies/serves those (or inlines images into `public/static`).
- **Auth in static mode** — admin/auth routes are dev-only; the static output must never include the admin/login surfaces (exclude `/admin`, `/auth` in `include_paths`; ForgeStaticHtml's default `exclude_paths` already covers `/admin`).
- **File-backed content** — blocks live in `storage/content/{uuid}.json`, referenced by `content_ref`; never edit a JSON column. Keep writes atomic (temp file + rename), handle orphaned files on delete, and back up `storage/content/` alongside the DB (`storage/` is already the runtime/backup dir).
- **Copy→refactor is the risk area** — each copied capability must be re-declared `Capability\{Cap}` and every `Modules\{Cap}\` reference (stack configs, middleware aliases, container dials, `require`/autoload hints) updated. Do it per-capability and verify at each phase (`structure:info`, boot log must show zero `Modules\Forge*`); flag any cap whose internal undeclared refs make a full rename too risky and keep it documented as an exception.

---

## 5b. Kernel Decoupling Plan — ForgeRouter (DONE in both forge-v3 + forgenexus)
**Goal:** the `kernel/` must not depend on ForgeRouter (or any capability's FQCN), so ForgeRouter can live as `Capability\ForgeRouter` without the kernel hardcoding `Modules\ForgeRouter`. This fixes `kernel/Core/Bootstrap/ErrorHandlerSetup.php:29`.

**Problem (researched):** the kernel's `ErrorHandlerSetup` hardcodes the string `'Modules\ForgeRouter\Contracts\ErrorHandlerInterface'` and does `interface_exists()` / container lookups against it. After the ForgeRouter refactor that interface lives at `Capability\ForgeRouter\Contracts\ErrorHandlerInterface`, so the kernel check fails and it silently falls back to the default handler — ForgeRouter's custom error handling is never connected.

**Full coupling inventory (14 refs in `kernel/`):**
| File | Kind | Notes |
|---|---|---|
| `kernel/Core/Bootstrap/ErrorHandlerSetup.php:29` | **LIVE** | Hardcoded FQCN string; the main issue. |
| `kernel/CLI/Commands/Registry/BlueprintScaffoldCommand.php:425-437` | **LIVE-ish** | Writes `config/middleware.php` with `\Modules\ForgeRouter\Http\Middlewares\*` class refs into scaffolded blueprints. |
| `kernel/resources/stubs/test.stub` | template | `use Modules\ForgeRouter\Http\Response;` |
| `kernel/tests/…` (Engine tests) | test | `use Modules\ForgeRouter\...` |
| `kernel/CLI/Traits/OutputHelper.php:329`, `kernel/CLI/Commands/HelpCommand.php:375` | docs | Help text example `ForgeRouterModule::registerMiddleware(...)`. |
| `kernel/Core/Module/ModuleLoader/Loader.php:35` | doc | Docblock only. |

**Resolution (EXECUTED — Option A: kernel-owned contract + module self-binds + CLI reorder):**
1. **Kernel-owned contract:** added `kernel/Core/Contracts/ErrorHandlerInterface.php` (`Forge\Core\Contracts\ErrorHandlerInterface`), a framework-agnostic contract (`handle(Throwable, ?object $context = null): mixed`), parallel to `ViewInterface`/`LoggerInterface`/`BootstrapHookInterface`. The kernel references it by its **own `::class`** — no capability FQCN, no config.
2. **`ErrorHandlerSetup` — DECOUPLED:** rewritten to discover the kernel contract via `$container->has()/get()/getAll()`. If none, falls back to `registerDefaultHandler()`. The `config/registry.php` `error_handler.interface` key was **reverted** (that file is only for git package registries).
3. **Modules self-bind to the kernel contract:**
   - `ForgeErrorHandlerModule::register()` binds `Forge\Core\Contracts\ErrorHandlerInterface` → `ForgeErrorHandlerService`.
   - `ForgeErrorHandlerService` implements the kernel contract (its constructor self-installs `set_error_handler`/`set_exception_handler`/`register_shutdown_function`); `handle()` accepts `?object` and builds a Router `Request` from globals when needed.
   - **Deleted** the Router-local `Modules/ForgeRouter/Contracts/ErrorHandlerInterface.php` (and `Capability/ForgeRouter/...` in forgenexus); `ForgeRouterModule::handleException` now uses the kernel contract and guards `$response->send()` on the `mixed` return.
   - forgenexus has no ForgeErrorHandler yet → gracefully falls back to the default handler until that capability is copied.
4. **Fixed the "early enough to work properly" bug — CLI ordering:** `ContainerCLISetup` ran `ErrorHandlerSetup::setup()` **before** `ModuleSetup::loadModules()`, so the container index was empty and the CLI path always fell back to the default handler. Moved `ErrorHandlerSetup::setup()` to **after** `loadModules()` so both web (`ContainerAppSetup`, already correct) and CLI discover module-registered handlers.

**Verified (both forge-v3 + forgenexus):** lint clean; CLI + web boot clean. Probe of `Bootstrap::initCliContainer()` resolves `Modules\ForgeErrorHandler\Services\ForgeErrorHandlerService` on forge-v3 (CLI now uses the custom handler, not default); forgenexus resolves `NONE (default used)` gracefully (no error-handler capability yet). forgenexus kernel copies (contract + ErrorHandlerSetup + ContainerCLISetup) are byte-identical to forge-v3.

**Open follow-ups:** (a) when ForgeErrorHandler is copied to forgenexus (later phase), its `register()`/`#[Provides]` will bind the kernel contract and CLI+web will self-install it. (b) When forge-v3 eventually migrates off `Modules\`, sweep the scaffold/stub/test/doc refs (BlueprintScaffoldCommand, test.stub, Engine tests, help text) from `Modules\ForgeRouter`.

---

## 6. Ground-Truth Source Map
### Forge kernel (`forge-v3`)
- Models/query: `modules/ForgeSqlOrm/src/ORM/{Model,ModelQuery}.php`, `.../Values/Cast.php`, `.../Traits/HasTimeStamps.php`; migrations: `modules/ForgeDatabaseSQL/src/DB/{Attributes,Migrations}`
- Markdown: `modules/ForgeMarkDown/src/ForgeMarkDown.php` (`parse`/`parseFile`)
- Templates: `modules/ForgeTemplates/src/Injectable/TemplateManager.php` (`useTemplate`, `layout()`, `slot()`)
- Auth engine: `modules/ForgeAuth/src/Services/ForgeAuthService.php` + `Contracts/*`; accounts: `modules/ForgeAppAuth/...`
- Admin pattern: `modules/ForgeAdminConsole/src/Http/Dashboard.php`, `.../ForgeAdminConsoleModule.php`
- Storage: `modules/ForgeStorage/src/Services/UploadService.php`, `.../Drivers/LocalDriver.php`, `.../Http/File.php` (`/__upload`), `storage:link`
- Static: `modules/ForgeStaticHtml/src/StaticGenerator.php` (+ `Commands/GenerateStaticCommand.php`); `modules/ForgeStaticGen/src/ForgeStaticGen.php` (+ `Commands/StaticGenBuildCommand.php`)
- Error handling: `modules/ForgeErrorHandler/src/...`; Sprinkles: `modules/ForgeSprinkle/src/ForgeSprinkleModule.php`; Wire: `modules/ForgeWire/...`; HTMX: `modules/ForgeHtmx/...`
- Middleware config: `config/middleware.php` (`auth` group)
- Content store: `storage/content/` (JSON block files by UUID) — no kernel helper needed; plain atomic file write + DB `content_ref`

### NovaSpace reference (`/Users/acidlake/Development/NovaSpace/repos/novaspace`)
- Blueprint/assembly: `forge_structure.php`, `forge.json`, `forge-lock.json`; `kernel/Core/Structure/StructureResolver.php`, `kernel/Core/Module/ModuleLoader/Loader.php`
- **Auth (customized):** `novaspace/Http/Endpoints/Web/Auth/AuthEndpoint.php` (full route table), `novaspace/Http/Middlewares/AuthMiddleware.php`, `novaspace/Injectable/Identity/UserContext.php`, `novaspace/Data/Providers/UserProvider.php`, `modules/NovaAuth/src/NovaAuthModule.php` (DI binds + `auth` group), `capabilities/ForgeAuth/src/Services/ForgeAuthService.php`
- **Admin/authoring:** `novaspace/Http/Endpoints/Web/Spaces/PersonalContentEndpoint.php`, `.../UserArea/JournalEndpoint.php`, `modules/NovaWidgets/src/UI/views/components/widgets/editor.php`
- **Content model:** `novaspace/Data/Records/{Page-less analog: PublicationRecord,JournalEntryRecord}.php`, `.../Data/Providers/{PublicationProvider,JournalEntryProvider}.php`, `.../Data/Migrations/*`
- **Uploads/media:** `modules/NovaMedia/src/Services/MediaLibrary.php`, `.../Services/MediaValidator.php` (`nova_media.allowed.*`), `modules/NovaImageEditor/src/Processor/ImageProcessor.php` (`process()`→WebP), `novaspace/Http/Endpoints/Web/MediaLibraryEndpoint.php` (upload⇒resize⇒serve), `capabilities/ForgeStorage/...`
- **Views:** `novaspace/UI/views/layouts/{root,app-shell,content}.php` (parent-layout chain + `$parentLayout`)
- **Catalog/dogfood targets:** current `forge-documentation/*.html` (52 files) in `forge-v3`

