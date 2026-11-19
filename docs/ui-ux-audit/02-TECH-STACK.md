# 02 — Verified Technology Stack

> Versions come from `composer.json`, `package.json`, `php artisan --version`, `php -v` and the actual `<script>`/`<link>` tags in Blade views.

## 1. Backend [Confirmed]

| Item | Value | Evidence |
|---|---|---|
| Language | PHP `^8.2` (platform pinned to 8.2.0; local CLI 8.2.12) | `composer.json` `require.php`, `config.platform.php` |
| Framework | Laravel **12.50.0** (`laravel/framework ^12.0`) | `php artisan --version` |
| View engine | **Blade** (`resources/views/**/*.blade.php`, 130 files) | |
| Auth | Session auth (custom `AuthController`), Google login via `laravel/socialite ^5.23`, extension auth via `laravel/sanctum ^4.2` | `routes/web.php`, `routes/api.php` |
| AI | `anthropic-ai/sdk ^1.8` (provider set in config `ai.*`) | `composer.json`, `phpunit.xml` env |
| Email | `resend/resend-laravel ^0.23`, Gmail API over `guzzlehttp/guzzle ^7.9` | |
| Excel | `maatwebsite/excel ^3.1` (lead exports) | `LeadsController@exportExcel` |
| Payments | `stripe/stripe-php ^17.5` is **installed but not used** (0 references in `app/`, `routes/`, `resources/`). Payments are manual: screenshot upload + admin approval | `partials/payment-modal.blade.php`, `Admin\PaymentController` |
| API style | No public REST API. Web routes return HTML or JSON (`expectsJson()`); extension API under `/api/extension/*` | |

## 2. Frontend [Confirmed]

| Item | Value | Notes |
|---|---|---|
| Framework | **None (server-rendered Blade + vanilla JS)** | No React/Vue/Alpine/Livewire/Inertia |
| Language | JavaScript (inline `<script>` blocks in Blade; no TypeScript) | 21 views push scripts via `@push('scripts')`; layouts render `@stack('scripts')` |
| CSS framework at runtime | **Tailwind CSS Play CDN** `<script src="https://cdn.tailwindcss.com">` — **unversioned** (serves Tailwind v3.x Play CDN) | used in all 3 layouts + all standalone pages (11 includes). Configured inline with `tailwind.config = {...}` per page |
| CSS framework installed | `tailwindcss ^4.0.0` + `@tailwindcss/vite ^4.0.0` via Vite | **Not used at runtime**: no view contains `@vite`, `public/build` does not exist, `node_modules` not installed |
| Custom CSS | Inline `<style>` blocks in 24 views (Select2 skin, search page, home page, auth pages). No global stylesheet | |
| Component library | None. Hand-written Tailwind markup + Blade partials. 2 Blade components (`<x-user-filter>`, `components/modern-hero` included with `@include`) | |
| Icons | **Font Awesome 6.4.0** (cdnjs, `all.min.css`) — `fas`, `fab` classes | every layout |
| Fonts | User/admin panels: **no web font loaded** → Tailwind default `font-sans` system stack. Public/auth pages: **Inter** 400–900 from Google Fonts (`display=swap`). One page also loads Roboto. `resources/css/app.css` declares "Instrument Sans" but is never loaded | see 03 |
| jQuery | **3.6.0** (code.jquery.com) on auth pages, `user/search`, `user/leads`, and via `partials/select2-assets` | `$.ajax` used in 4 views |
| Enhanced selects | **Select2 4.1.0-rc.0** (jsDelivr) — `partials/select2-assets.blade.php`, search, lead center, AI profile | |
| Choices.js | **10.2.0** — 1 view | |
| Charts | **Chart.js** — 8 views load the **unversioned** `cdn.jsdelivr.net/npm/chart.js`, 2 views pin `chart.js@4.4.0` (admin reports, user affiliate, email analytics) | |
| Maps | **Leaflet 1.9.4** (cdnjs) — Lead Center coverage map (`user/lead-center/_coverage-map-*.blade.php`) | |
| State management | None (DOM + `data-*` attributes; small `window.*` globals like `window.obSetField`, `window.cnmEscape`) | |
| Forms / validation libs | None. Native forms + Laravel server validation (`FormRequest` classes and `$request->validate`) | |
| Tables | Plain `<table>` (49) wrapped in `overflow-x-auto` (46); Laravel paginator `->links('pagination::tailwind')` | |
| Animation | Tailwind `transition`, `animate-spin`/`animate-pulse`; some custom keyframes in inline `<style>` (home/auth) | |
| Toasts / notifications | No library. Several hand-made helpers: `#successToast` (layout), `cnmToast()` (AI Sales Profile), ad-hoc toasts, session flash banners, `alert()` (9 views) and `confirm()` (25 views) | |
| Date/time | Server-side Carbon formatting; no JS date library | |
| Client routing | None — URLs are Laravel named routes (`route('...')`) | |
| HTTP client | `fetch` (33 views). `axios` is in `package.json`/`resources/js` but **not loaded** | |
| XSS helpers | `partials/safe-html-helpers.blade.php` → `window.cnmEscape`, `window.cnmSafeUrl`, `window.cnmEscapeDeep` (included by both panel layouts) | |

## 3. Tooling [Confirmed]

| Item | Value |
|---|---|
| PHP package manager | Composer (`composer.lock` present) |
| JS package manager | npm (`package.json`; no lock file, no `node_modules`) |
| Build | Vite `^7.0.4` + `laravel-vite-plugin ^2.0.0` — configured (`vite.config.js`) but **not part of the runtime pipeline** |
| Dev command | `composer dev` (server + queue + pail + vite) |
| Lint / format | `laravel/pint ^1.13` (PHP). No ESLint/Prettier/Stylelint |
| Tests | PHPUnit `^11.5.3` (`phpunit.xml`, SQLite in-memory). Run with `php artisan test` — 539 tests passing at audit time. Feature tests assert rendered HTML (`assertSee`) for many views |
| Browser/E2E tests | None |
| `.editorconfig` | 4 spaces, LF, UTF-8 |

## 4. Installed vs used

| Package | Installed | Used at runtime |
|---|---|---|
| tailwindcss 4 / @tailwindcss/vite | yes (package.json) | **no** (CDN v3 Play is used) |
| axios | yes | **no** |
| vite / laravel-vite-plugin | yes | **no** (no `@vite` in views) |
| stripe/stripe-php | yes | **no** |
| Tailwind Play CDN | via `<script>` | **yes** — the real styling engine |
| Font Awesome 6.4.0, jQuery 3.6.0, Select2 4.1.0-rc.0, Chart.js, Leaflet 1.9.4, Choices 10.2.0 | via CDN | yes |

## 5. Consequences for the two AI skills

1. Write **Tailwind v3 utility classes** that work with the Play CDN (e.g. `bg-opacity-50`, `space-x-*`, arbitrary values `text-[11px]`). Do **not** rely on Tailwind v4-only features (`@theme`, `@utility`, CSS-variable color syntax like `bg-(--x)`, `text-shadow-*`, built-in container queries `@container`). Opacity modifiers such as `bg-black/50` are fine in v3.
2. Custom colors exist only through the per-page inline `tailwind.config` (see 03). A class like `bg-primary-600` works in `layouts.app`/`layouts.admin` pages but **not** on `home.blade.php` (which defines `primary-blue`, `primary-orange` instead).
3. Do **not** add npm dependencies or switch the page to `@vite` as part of a redesign — that changes the build/deploy pipeline (REQUIRES EXPLICIT APPROVAL).
4. New JS goes in inline `<script>` / `@push('scripts')` using `fetch` + `X-CSRF-TOKEN` from `<meta name="csrf-token">`, matching current code.
