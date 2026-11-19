# 07 — Skill 2: Frontend Implementation Architect — Requirements

This file also contains the **design-to-code strategy (Phase 9)**.

---

## Part A — How the project is built today (design-to-code facts)

| Question | Answer [Confirmed] |
|---|---|
| How new pages are added | Add a named route in `routes/web.php` inside the right middleware group, a controller method returning `view('user.<module>.<page>', [...])`, and a Blade view `@extends('layouts.app')` (user) or `@extends('layouts.admin')` (admin) with `@section('content')`. Optional `@push('scripts')`. |
| How pages are modified | Directly edit the Blade view; large pages keep markup + inline `<script>` (+ inline `<style>`) in one file. Newer modules split into `_partial.blade.php` files (`user/offer-builder/*`, `user/lead-center/_*`). |
| Reusable pieces | `@include('partials.x', [...])` and `@include('user.<module>._x', [...])`; Blade class components are rare (`<x-user-filter>`). Shared markup is usually copied, not abstracted. |
| Where CSS goes | Tailwind utility classes in markup. Page-specific custom CSS in an inline `<style>` in that view (or in a partial). There is no global CSS file loaded. Tailwind theme extensions live in the inline `tailwind.config` of each layout/standalone page. |
| Responsive authoring | Mobile-first Tailwind prefixes `sm:`/`md:`/`lg:`; sidebar/off-canvas handled by the layout. |
| JS interactions | Inline vanilla JS (`fetch`, delegated `document.addEventListener('click'|'change', ...)` with `.closest()`), some jQuery (Select2, auth, search, leads). Globals are guarded (`if (!window.obPickWired)`). |
| Backend bindings | Blade `{{ route('name', params) }}`, `@csrf`, `@method`, `old('field', $model->field)`, `@error('field')`, `$errors`, `session('success')`; JS reads URLs from `data-url` attributes or Blade-rendered strings. |
| Conditional states | `@if/@unless/@can`, `PlanFeatures::enabled(...)`, `$user->isTeamMember()`, `hidden` class toggled by JS, `@forelse/@empty` for empty lists. |
| Icons & fonts | Font Awesome 6.4 classes (`fas fa-*`, `fab fa-*`); Inter only on standalone public pages. |
| Testing | `php artisan test` (PHPUnit, SQLite memory). Many feature tests assert HTML strings (`assertSee('...', false)`) including classes, `data-testid`, option markup. No browser tests, no JS tests, no linters for Blade/JS. |

---

## Part B — Skill 2 configuration

### B1. Context it needs
1. `08-PROJECT-KNOWLEDGE-PACK.md`, `05-FRONTEND-INTEGRATIONS.md`, `09-REDESIGN-SAFETY-CHECKLIST.md`.
2. The redesigned screenshot(s) **and** Skill 1's design spec with the control mapping table.
3. The target route name/URL → resolved to view file(s), layout, partials, scripts, controller method and related tests (`grep -rn "<route-name>|<view-name>" tests/`).

### B2. How to inspect the redesigned screenshot
- Split into regions: shell (sidebar/header — usually unchanged unless the redesign targets the layout), page header, toolbars/filters, KPI/cards, main content (table/grid/form), side panels, modals, footer actions.
- For each region list: layout (grid/flex, columns per breakpoint), spacing, colors (map to tokens in `03 §6` — never sample new HEX values), typography, radius, shadow, icons (map to Font Awesome names).
- Match every interactive element to an existing control from the current view (by label, `name`, `id`, route). Unmatched new elements → mark "visual only" or "needs backend" (do not implement backend without approval).

### B3. How it identifies files
1. `php artisan route:list --name=<name>` (read-only) → controller@method → `return view('...')`.
2. Open the view, follow `@include`, `@extends`, `<x-...>`, `@push('scripts')`.
3. Find scripts that reference element ids/classes (`grep -n "getElementById\|querySelector\|\$('#" <view>`).
4. Find tests: `grep -rln "<route path or view text>" tests/`.
5. Output the **smallest file set** to change (normally 1 view + its partials; the layout only if the shell is in scope).

### B4. Mapping visual elements to code
- Reuse existing partials (`_field`, `_badges`, `_table`, `payment-modal`, `pricing-features`, `user-filter`) and update them in place when the design changes them for every page that uses them (check all `@include` call sites first).
- New repeated visual pieces (e.g. a new stat card) → a new `_partial.blade.php` in the module folder, not a new framework component.
- Keep ids/classes that JS uses; add new classes alongside, do not rename hooks.

### B5. The coding prompt it must output (template)

```
ROLE: You are editing an existing Laravel 12 + Blade project (CustomerNearme). Frontend = Blade +
Tailwind CSS v3 Play CDN (configured inline in the layout) + Font Awesome 6.4 + inline vanilla JS
(fetch) and jQuery/Select2 where already used. There is NO build step at runtime; do not add npm
packages, @vite, Alpine, React or new CDNs.

GOAL: Re-implement the page <PAGE NAME> (route `<route.name>`, URL `<url>`) to match the attached
design and spec, without changing behaviour.

FILES IN SCOPE (smallest safe set):
- <resources/views/...> (main view)
- <partials...>
Out of scope unless stated: controllers, routes, models, migrations, layouts/app.blade.php, other pages.

STEP 1 — Inspect: read the files above and list every form (action/method/@csrf/@method), input
`name`, id/class used by JS, data-* attribute, route() call, @if/@can/PlanFeatures condition,
@foreach/@forelse loop, pagination call and fetch/ajax URL. This list is the contract.

STEP 2 — Implement the design:
<region-by-region instructions generated from the spec: exact Tailwind classes, grid per
breakpoint, tokens (orange-500 #f97316 primary actions, primary-600 #2563eb nav/info, gray
neutrals), radius rounded-xl / rounded-2xl, shadow-sm, typography sizes, Font Awesome icons>

STEP 3 — Preserve: every item in the STEP 1 contract must still exist and work: same field names,
same routes, same JS hooks (ids/classes/data-*), same conditionals, same escaping ({{ }} only; JS
uses cnmEscape/cnmSafeUrl), same flash/session messages, same JSON handling (`success` vs `ok`).
Only use primary-50/100/500-900 (primary-200/300/400 are not defined).

STEP 4 — States: implement empty / loading / error / disabled / plan-locked / team-member variants
as specified.

STEP 5 — Responsive: verify at 375px, 768px, 1024px, 1280px. Sidebar breakpoint stays `lg`.
No horizontal page scroll; tables get <mobile pattern>. Touch targets ≥ 40px.

STEP 6 — Accessibility: labels tied to inputs, aria-label on icon-only buttons, visible focus
(focus:ring-2 focus:ring-orange-200 focus:border-orange-500), modal close on ESC, contrast AA.

STEP 7 — Checks (non-destructive): `php artisan view:clear`, `php artisan test`
(and the specific tests: <list>), `php -l` not needed for Blade. Update test string assertions
ONLY if the visible text/markup they check was intentionally changed — list each one.

STEP 8 — Report: changed files, what was preserved (contract checklist ticked), tests run and
results, screenshots/notes for each breakpoint, and any remaining gaps or items needing backend work.
```

### B6. Preserving backend integrations
Treat the STEP 1 contract as immutable. Never change: route names/URLs, HTTP methods, field names, JSON keys, response handling branches, polling intervals/endpoints, upload field names/accept types, CSRF handling, authorization conditionals, `asset('public/...')` paths, `Setting::get(...)` logo/name lookups.

### B7. Missing design details
Fill from `03` tokens and the nearest existing pattern in the same module; state the assumption in the report. Never invent data, metrics, or endpoints; use placeholders only in comments.

### B8. Responsive handling
Mobile-first classes; collapse multi-column grids at `sm`/`lg`; keep the off-canvas sidebar; tables: wrap `overflow-x-auto` **plus** optional `sm:hidden` card list rendered from the same `@foreach` (same actions, same hooks — avoid duplicate ids: use classes/data attributes for row actions in the duplicated markup).

### B9. Project conventions to keep
4-space indent, Blade comments `{{-- --}}` for explanations, partials prefixed `_`, scripts guarded against double-binding, `fetch` with `X-CSRF-TOKEN` from meta tag, `Accept: application/json`, toasts via existing helpers (`cnmToast` where present), English UI copy, simple wording.

### B10. Avoiding rewrites
Edit markup/classes in place; keep script blocks untouched unless the hook elements moved; do not merge/split files unless it reduces risk; do not refactor the 2,000-line pages wholesale — redesign region by region in separate commits.

### B11. Complex forms and data tables
- Forms: keep `name`, `value`/`old()`, `required`, `maxlength`, `accept`, dependent-select handlers (Country→State→City), Select2 init targets, hidden inputs (`package_id`, `payment_method_id`, `lead_category`, query-preserving hidden fields).
- Tables: keep checkbox `value` = record id and the select-all logic, row action handlers (`onclick="..."` function names), sort/filter query params, `->links('pagination::tailwind')` + `appends(request()->query())`.

### B12. Validation of the result
1. `php artisan test` all green (539 tests at audit time).
2. Manual check at 375/768/1280 in the browser: open the page as user, company owner, team member, restricted user, and with a plan lacking the feature.
3. Exercise every action in the contract once (submit, filter, paginate, bulk, modal open/close, fetch actions, upload).
4. Browser console: no JS errors; Network: same endpoints called with same payloads.
5. Diff review: no changes to `routes/`, `app/` unless approved.
