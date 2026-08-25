# AGENTS.md — woocommerce-on

Guidance for AI coding agents (Claude Code, Cursor, Copilot, …) and new developers working in this Moloni ON plugin. This is the canonical context file; `CLAUDE.md` imports it.

## What this plugin is

The **Moloni ON** WooCommerce/WordPress plugin — a **PHP module** (Composer, `type: wordpress-plugin`, namespace `MoloniOn\` PSR-4 → `src/`, PHP ≥ 7.2). It runs *inside* a WordPress + WooCommerce install and syncs the store to Moloni ON **over the public GraphQL API** (`api_url`, e.g. `https://api.molonion.in/v1`; auth via `/auth/grant`). It issues Moloni documents (invoices, etc.) from WooCommerce orders, syncs products/stock both ways, and manages customers. Distributed free on the WordPress.org plugin directory (slug `moloni-on`).

## Responsibilities & boundaries

**Owns:**
- Reacting to WooCommerce/WordPress **hooks** (order status changes, product create/update/delete/view) — `src/Hooks/`.
- Issuing Moloni documents from orders (`src/Services/Orders/CreateMoloniDocument`, `src/Controllers/Documents.php` + order-part controllers) and the merchant admin surface (settings, automations, pending-orders list, logs) under the `molonion` admin page.
- **Inbound sync from Moloni**: a WordPress REST route (`rest_api_init` → `src/WebHooks/Products.php`) that Moloni calls to push product/stock changes back into WooCommerce, plus registering those Moloni-side webhooks (`Product` `stockChanged`/`create`/`update`).
- The Moloni public-API client (`src/API/`, GraphQL `Queries/` + `Mutations/`, HTTP via `src/Curl.php`).

**Must not:** call any non-public Moloni service (public API only); hard-code or commit credentials (merchant authenticates via the plugin's login form; tokens live in `src/Models/Auth`); double-issue documents (see fiscal idempotency below); depend on WooCommerce features that a given store may not have. UI stays **host-native WordPress/WooCommerce admin** — no invented look, never restyled.

## Architecture map

- **`moloni-on.php`** — WordPress plugin entrypoint. Loads Composer autoload, defines `MOLONI_ON_*` constants, registers activation/deactivation hooks (`src/Activators/`), and `plugins_loaded` → `Plugin::init()`.
- **`src/Plugin.php`** — main orchestrator. Wires the admin menu (`src/Menus/Admin.php`), Ajax, the inbound `WebHook`, and all WooCommerce hooks; routes admin-page actions (`saveSettings`, `saveAutomations`, `genInvoice`, `getInvoice`, `remInvoice`, `reinstallWebhooks`, `remLogs`, `logout`) with capability + nonce checks.
- **`src/Hooks/`** — WooCommerce/WP hook handlers (`OrderStatusChanged` → auto-invoice, `ProductUpdate`/`ProductDelete`/`ProductView`, `OrderView`/`OrderList`/`OrderDetails`, `DownloadOrderDocument`, `WoocommerceInitialize`, `UpgradeProcess`).
- **`src/WebHooks/`** — inbound Moloni→WooCommerce REST routes (`WebHook.php` registers on `rest_api_init`; `Products.php` handles stock/product sync).
- **`src/API/`** — Moloni public GraphQL client: one class per resource (`Documents`, `Products`, `Customers`, `Taxes`, `Stocks`, `Warehouses`, `Categories`, …) plus `Queries/`, `Mutations/`, `Abstracts/`. `src/Curl.php` is the HTTP layer (`wp_remote_post` to `api_url`).
- **`src/Services/`** — business logic (`Orders/`, `Documents/`, `Imports/`, `Exports/`, `MoloniProduct/`, `WcProduct/`, `Mails/`, `Mixed/`).
- **`src/Controllers/`** — build document/order payloads (`OrderProduct`, `OrderCustomer`, `OrderShipping`, `OrderFees`, `Payment`, `Documents`).
- **`src/Models/`** — `Auth` (tokens), `Settings` (WP options), `Logs`.
- **`src/Context.php` + `src/Context/`** — the runtime context (settings, logger, configs incl. `api_url`).
- **`src/Templates/`** — admin UI templates (`MainContainer.php` is the page shell). **`src/Menus/`**, **`src/Scripts/`** (asset enqueue), **`src/Helpers/`**, **`src/Enums/`**, **`src/Exceptions/`**, **`src/Traits/`**.
- **`.dev/`** — the **frontend asset toolchain** (its own `package.json` + `gulpfile.js` + Tailwind/Sass). Compiles CSS/JS into `assets/`. Separate from the plugin's PHP runtime.
- **`builder.php`** — packages a distributable (`build/molonion` → zip); run in CI on `v*` tags. No submodules (`.gitmodules` absent).

## Running locally

PHP deps and frontend assets build in **two different places**, then the plugin is mounted into a Dockerised WordPress.

1. **PHP deps** (project root): `composer install`
2. **Frontend deps** (inside `.dev/`): `cd .dev && npm install`
3. **Build assets** (inside `.dev/`): `npm run build-prod` — gulp compiles CSS/JS. **Required after every JS/CSS change** or the admin page renders broken. (`.nvmrc` = 20 is for *this* toolchain, not the PHP runtime.)
4. **Run the store** (`docker-compose.yml`): a Bitnami WordPress (`localhost:8080/wp-admin`, `admin` / `123456789`) + MariaDB stack. The compose file mounts the plugin into `wp-content/plugins/moloni-on` — **point that volume directly at this checkout** (adjust the `- ./moloni-dev:...` path to the checkout) so edits are live. `dev.md` has the full walkthrough.
5. In WP admin, activate **Moloni ON**, open the plugin page, and log in to Moloni to authorize (no committed credentials).

## Commands

| Command | Where | What it does |
| --- | --- | --- |
| `composer install` | root | Install PHP dependencies |
| `composer run build` (`php builder.php`) | root | Package the distributable (`build/molonion` + zip) |
| `npm install` | `.dev/` | Install the asset toolchain |
| `npm run build-prod` | `.dev/` | Compile CSS/JS into `assets/` (gulp) — rerun after any JS/CSS change |
| `docker compose up -d` | — | Boot local WordPress + MariaDB (see `dev.md`) |

## Verification

**There is no automated test/lint gate** — no `phpunit`, `phpcs`, or `phpstan` config in the repo, and the only CI workflow (`.github/workflows/production-deploy.yml`) just builds assets + `composer install --no-dev` and deploys to the WordPress.org SVN on `v*` tags. Nothing runs on PRs.

So verify a change by:
1. **Build must be clean** — `composer install` resolves and `npm run build-prod` (in `.dev/`) compiles without error.
2. **Exercise the behaviour** — load the plugin into the local Docker WordPress and drive the affected flow by hand (generate a document from an order, product/stock sync, settings save, webhook reinstall). This is the real check for any behaviour change; treat it as required for non-trivial fixes.

## Conventions

- **Namespace `MoloniOn\`**, PSR-4 → `src/`. One class per file; resource-per-file in `src/API/`.
- **WordPress idioms**: `add_action`/hooks, `wc_get_order`, capability checks (`current_user_can('edit_shop_orders')`), request validation via `Helpers\Security` (`verify_request_or_die`, nonces), input sanitized through `Security::sanitizer` with a typed schema (see `Plugin::sanitizeSettingsValues`).
- **i18n**: user-facing strings via `__( …, 'moloni-on' )`; keep the `moloni-on` text domain.
- **Settings** are WP options via `Models\Settings`; read through `Context::settings()`. Never hard-code credentials or `api_url`.
- **Assets are compiled artifacts**: edit sources under `.dev/`, never hand-edit the compiled files in `assets/`; commit the rebuilt output.
- **Idempotency + fiscal correctness are load-bearing** — document creation must not double-issue (the pending-orders list + `Services\Orders\DiscardOrder` mark an order as generated); tax / document-type / exemption-reason mappings are fiscal-legal.

## Testing

No test framework is configured. "Testing" = the manual verification above (build clean + exercise in the Docker WordPress). If you add coverage, PHPUnit is the natural fit — wire it into `composer.json` scripts and note it here.

## Gotchas

Curated, verified conventions and traps. This is the default-trusted source — every agent reads it.

- **Two build roots.** PHP deps install at the project root; the **frontend assets build in `.dev/`** (a *separate* `package.json` + gulp/Tailwind/Sass). Forgetting `npm run build-prod` in `.dev/` after a JS/CSS edit ships a broken admin page — the compiled output in `assets/` is what the plugin loads.
- **This plugin has an inbound webhook surface.** It exposes a WordPress REST route (`rest_api_init` → `src/WebHooks/Products.php`) that **Moloni calls** to push product/stock changes into WooCommerce, and it registers the matching Moloni-side hooks (`Product` `stockChanged`/`create`/`update`) on settings save / `reinstallWebhooks`. Inbound sync breaks silently if the site isn't publicly reachable.
- **Fiscal idempotency is load-bearing** — never let a code path double-issue a document; respect the pending list + `DiscardOrder` "mark as generated" flow, and treat tax/document-type/exemption mappings as legal, not cosmetic.
- **`api_url` is configurable** and points at `api.molonion.in` — in dev it can target staging. Read it from `Context::configs()->get('api_url')`; never hard-code an endpoint.
- **No PR-time quality gate.** The only workflow releases on `v*` tags (builds assets, `composer install --no-dev`, deploys to WordPress.org SVN). PRs run nothing — so the manual verification above is the only safety net.
- **No submodules** here (`.gitmodules` absent).

> Recent, **unverified** findings live in `.claude/journal/` (one dated file per finding). They are promoted into this section by manual curation once re-verified against the code — treat them as leads, not yet rules.
