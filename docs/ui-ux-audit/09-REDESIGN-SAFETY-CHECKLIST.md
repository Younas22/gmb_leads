# 09 — Redesign Safety Checklist

Classification: **MUST PRESERVE** · **CAN MODIFY SAFELY** · **REQUIRES EXPLICIT APPROVAL**

## 1. Constraint table

| Area | Constraint | Class |
|---|---|---|
| Brand | Orange `#f97316`/`#ea580c` as primary action & brand accent; blue `#3b82f6`/`#2563eb`/`#1d4ed8` for nav/info; gray neutrals; light theme | MUST PRESERVE |
| Brand | Exact shade usage, gradients, card radius/shadow, icon tiles | CAN MODIFY SAFELY (within palette) |
| Brand | New accent colors, dark mode, new fonts in panels (e.g. adding Inter), new icon library | REQUIRES EXPLICIT APPROVAL |
| Logo | `Setting::get('site_logo'|'site_favicon'|'site_name')` fallbacks and `asset('public/...')` paths | MUST PRESERVE |
| Stack | Laravel 12 + Blade + Tailwind Play CDN + Font Awesome + vanilla JS/jQuery | MUST PRESERVE |
| Stack | Switching to `@vite`/Tailwind 4 build, adding npm packages or new CDN libraries, pinning the Tailwind CDN version | REQUIRES EXPLICIT APPROVAL |
| Auth | Login/signup/forgot/reset AJAX endpoints, Google OAuth links, CSRF refresh (`/csrf-token`), referral `ref` capture in `localStorage` | MUST PRESERVE |
| Authorization | Middleware groups, `AdminMiddleware`, `CheckSubscriptionAccess`, `EnsurePlanFeature`, `EnsureProfileIsComplete`, policies, Gate `manage-sales-profile` | MUST PRESERVE |
| Authorization UI | `@if` gates in sidebar/pages (`PlanFeatures::enabled`, `isTeamMember`, `isCompany`, `hasRestrictedAccess`, `session('admin_preview_user')`) | MUST PRESERVE (may restyle what they wrap) |
| Routes/URLs | Route names, URIs, methods, query parameter names | MUST PRESERVE |
| Controllers | Controller logic, view data variables | MUST PRESERVE (adding a view variable for pure presentation = REQUIRES EXPLICIT APPROVAL) |
| API contracts | JSON shapes (`success`/`ok`/`message`/`missing`/...), HTML-fragment endpoints, extension API | MUST PRESERVE |
| Database | Migrations, models, columns | MUST PRESERVE (no changes) |
| Business logic | Plan limits, daily lead limits, export limits, AI quotas, pricing data from DB | MUST PRESERVE |
| AI | AI gates (`$canGenerate`, plan quotas, AI-off fallback, team-member read-only), review-before-send flow, polling, status values, Offer Builder `.ob-*` hooks, `cnmAnalyze*` modal (see 11) | MUST PRESERVE |
| Payments | Manual payment flow: method selection, screenshot upload (`screenshot`, `image/*`, required), `package_id`, `payment_method_id`, admin approval | MUST PRESERVE |
| Localization | none today; adding translations | REQUIRES EXPLICIT APPROVAL |
| SEO | `home` meta description/keywords, canonical, OG/Twitter, JSON-LD, `robots` (index on home, `noindex` on panels), `sitemap.xml`, single `h1`, section anchors | MUST PRESERVE |
| SEO copy | Marketing headings/body copy wording | REQUIRES EXPLICIT APPROVAL |
| Forms | `action`, `method`, `@csrf`, `@method`, input `name`, `required`, `maxlength`, `accept`, `old()`, `@error`, hidden inputs | MUST PRESERVE |
| Forms | Visual layout, grouping, labels' styling, helper text | CAN MODIFY SAFELY |
| Data rendering | Escaping (`{{ }}`, `cnmEscape`, `cnmSafeUrl`), loops, pagination `links('pagination::tailwind')` + `appends` | MUST PRESERVE |
| Interactions | JS hook ids/classes/`data-*`/`data-testid`, inline `onclick` function names, polling, Select2 targets, Leaflet map container ids | MUST PRESERVE |
| Interactions | Replacing `alert()`/`confirm()` with styled dialogs | REQUIRES EXPLICIT APPROVAL (behavior change) |
| Workflows | Search → save → My Leads → Lead Center; AI profile → offers → AI outreach → email/campaigns → analytics/follow-ups; subscription → payment → admin approval | MUST PRESERVE |
| Security | CSRF meta + headers, signed unsubscribe URLs, throttled routes, `noindex` on panels | MUST PRESERVE |
| Content | Sidebar module names, button labels referenced by tutorials/tests | CAN MODIFY only with test updates → REQUIRES EXPLICIT APPROVAL |
| Layout shell | Sidebar (left, 256px, off-canvas < `lg`), header bar | CAN MODIFY SAFELY (style/grouping); replacing the navigation model REQUIRES EXPLICIT APPROVAL |
| Dead code | Removing unused views (`old*hero`, `emails/oldlayout`, empty `admin/settings.blade.php`) | REQUIRES EXPLICIT APPROVAL |

## 2. Regression risks

1. **Tests assert HTML strings.** 31 test files use 347 `assertSee/assertDontSee` calls (e.g. `tests/Feature/OfferBuilder/OfferBuilderTest.php` checks exact `<option ... selected>` and `<mark class="...">` markup). Changing markup/classes/text can fail tests — update deliberately and list every change.
2. **JS hooks**: renaming/removing an id/class silently breaks fetch actions (no JS tests).
3. **Duplicated markup for mobile** can create duplicate ids → wrong element bound.
4. **Undefined Tailwind shades** (`primary-200/300/400`) render nothing — use defined shades.
5. **Play CDN** generates classes at runtime: classes built by string concatenation in JS still work, but avoid relying on classes that only exist inside `<template>`/`innerHTML` when testing visually.
6. **Plan/role variants** are easy to miss in screenshots (restricted user, team member, plan without feature, company filter).
7. **Long single-file pages** (`user/leads.blade.php` 2,661 lines): editing near script blocks risks breaking Blade/JS syntax.
8. **Standalone pages** have their own Tailwind config — tokens differ (`primary-orange` vs `orange-500`).
9. **Asset paths** must keep the `public/` prefix.
10. **Select2 / Leaflet** need their container elements and widths; restyling wrappers can collapse them.

## 3. Validation checklist (run for every redesigned page)

- [ ] Every control from the old page exists in the new page (control mapping table ticked).
- [ ] All forms submit to the same route with the same field names (inspect Network tab).
- [ ] All `fetch` actions work (status changes, bulk actions, modals, uploads, polling).
- [ ] Filters, pagination and query-string persistence work.
- [ ] Role/plan variants checked: individual user, company owner, team member, restricted (no subscription), plan lacking the feature, admin preview mode.
- [ ] Empty, loading, error, success states visible and styled.
- [ ] 375 / 768 / 1024 / 1280px: no horizontal page scroll, sidebar works, modals fit, tables usable.
- [ ] Keyboard: tab order, visible focus, ESC closes modals/sidebar.
- [ ] Colors only from the brand palette; contrast AA for text.
- [ ] No console errors; no new external requests/libraries.
- [ ] `php artisan view:clear && php artisan test` → all green (539 at audit time); intentional test updates listed.
- [ ] `git diff` touches only the agreed view/partial files.
