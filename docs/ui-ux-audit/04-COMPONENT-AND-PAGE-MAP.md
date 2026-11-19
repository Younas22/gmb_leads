# 04 — Component Inventory & Page Map

> All components are **Blade + Tailwind (Play CDN) + inline vanilla JS** unless stated. "Partial" = included with `@include`; files starting with `_` are partials by convention.

## 1. Layout shells

| Component | File | Purpose | Used by | Safe to redesign | Constraints |
|---|---|---|---|---|---|
| User panel layout | `resources/views/layouts/app.blade.php` | `<head>` (Tailwind CDN + config, FA 6.4, CSRF meta, favicon logic, `safe-html-helpers`), mobile menu button, header bar, subscription countdown banner, affiliate banner, flash messages, `@yield('content')`, feedback modal, extension modal, image lightbox, sidebar JS, `@stack('scripts')` | 33 user views (`@extends('layouts.app')`) | **Conditional** | Keep `<meta name="csrf-token">`, `@include('partials.safe-html-helpers')`, `@yield('content')`, `@stack('scripts')`, ids `sidebar`, `main-content`, `mobile-menu-btn`, `close-sidebar-btn`, `sidebar-backdrop`, `sidebar-toggle-btn`, `extensionModal`, `feedbackModal`, `successToast`, `countdown-*`, `affiliateBanner`; global functions `openExtensionModal()`, `openFeedbackModal()`, `toggleSidebarFullscreen()`, `showSuccessToast()` |
| User sidebar | `resources/views/layouts/sidebar.blade.php` | Logo, admin-preview "Switch back to Admin", nav links (permission + plan-feature gated), pending-subscription notice, due follow-ups counter, user menu/logout | `layouts/app` | **Yes (visual)** | Keep every `@if` gate (`hasRestrictedAccess`, `PlanFeatures::enabled(...)`, `isTeamMember`, `isCompany`, `session('admin_preview_user')`), every `route(...)`, active-state `request()->routeIs(...)` checks, logout `POST auth.logout` form |
| Admin layout | `resources/views/layouts/admin.blade.php` | Same shell with built-in admin sidebar (Overview / Management / Reports (collapsible) / Settings groups), "Admin Panel" purple badge, header with `@yield('title')` | 26 admin views | **Yes (visual)** | Same ids/JS contracts as user layout; `admin.switch.to.user`; logout form `#logoutForm` |
| Standalone public pages | `home.blade.php`, `auth/*.blade.php`, `pages/*.blade.php`, `maintenance.blade.php`, `email-unsubscribe.blade.php`, `email-link-unavailable.blade.php` | Each is a complete HTML document with its own `<head>` and Tailwind config | — | **Yes** | SEO meta, canonical, OG/Twitter, JSON-LD (`home`), referral `?ref=` → `localStorage` logic, auth AJAX endpoints |

## 2. Reusable partials and components

| Component | File | Framework | Purpose | Used by | Reusable | Safe to redesign | Must preserve |
|---|---|---|---|---|---|---|---|
| XSS helpers | `partials/safe-html-helpers.blade.php` | JS | `cnmEscape`, `cnmSafeUrl`, `cnmEscapeDeep` | both layouts | Yes | **No** (logic) | Always escape data inserted with `innerHTML` |
| Payment modal | `partials/payment-modal.blade.php` | Blade+JS | Pick payment method, upload screenshot, submit | `user/subscription` | Yes | Conditional | form `POST user.payment.submit`, `enctype=multipart/form-data`, fields `package_id`, `payment_method_id`, `screenshot` (image, required) |
| Feedback modal | `partials/feedback-modal.blade.php` | Blade+JS | Star rating + message | `layouts/app` | Yes | Yes | `POST user.feedback.store`, rating/message field names, star functions |
| Pricing features list | `partials/pricing-features.blade.php` | Blade | Feature bullets per package | `user/subscription` | Yes | Yes | inputs `$package`, `$boolFeatures`, `$featureLabels`, `$hideFromCards` |
| Select2 assets + skin | `partials/select2-assets.blade.php` | jQuery + Select2 | Loads jQuery 3.6, Select2 4.1 and a custom skin | lead center, AI profile | Yes | Yes (skin CSS) | Select2 init calls/ids |
| User filter | `components/user-filter.blade.php` (`<x-user-filter>`) | Blade component | "Filter by User" for company owners | `user/leads`, `user/search-history` | Yes | Yes | `onchange="filterByUser(this.value)"`, `name="user_filter"` |
| Marketing hero | `components/modern-hero.blade.php` | Blade | Home hero | `home.blade.php:623` | No | Yes | CTA links |
| Old heroes | `components/old_v2modern-hero.blade.php`, `components/oldmodern-hero.blade.php` | — | **Unused** | none | — | — | (candidates for removal, needs approval) |
| Welcome tutorial modal | `user/welcome-tutorial-modal.blade.php` | Blade+JS | First-login tutorial | `user/dashboard` | No | Yes | `POST user.welcome-tutorial.mark-seen` |
| Offer Builder field | `user/offer-builder/_field.blade.php` | Blade | Dropdown of choices + "Custom" + hidden text input (`.ob-pick`, `.ob-input`, `data-key`) | `_quick` | Yes | Conditional | classes `.ob-field/.ob-pick/.ob-input`, `data-key`, "__custom" value |
| Offer product field | `user/offer-builder/_product-field.blade.php` | Blade | User's Services + "+ Add a new service" inline form | `_quick` | No | Conditional | `.ob-product`, `__new_service`, `.ob-new-service[data-url]`, `.ob-ns-*` |
| Offer quick form | `user/offer-builder/_quick.blade.php` | Blade (HTML fragment loaded by fetch) | Short offer form + Generate/Save buttons | AI Sales Profile | No | Conditional | `.ob-quick[data-strategy]`, `.ob-q-generate`, `.ob-q-save`, `.ob-q-status`, test ids |
| Offer scripts | `user/offer-builder/_fields-scripts.blade.php`, `_result-scripts.blade.php` | JS | Delegated handlers, polling generation status | AI Sales Profile | Yes | **No** (logic) | — |
| Offer chips/messages | `user/offer-builder/_chips.blade.php`, `_messages.blade.php` | Blade | Coloured highlights of user inputs in AI text | AI Sales Profile | Yes | Yes (visual) | `InputHighlighter` output (`<mark>` with title) — asserted by tests |
| Sales profile offer fields | `user/ai-sales-profile/_offer-fields.blade.php` | Blade | Skill/Service/Offer add/edit fields | AI Sales Profile | Yes | Yes | field names |
| AI outreach table/badges/modal | `user/ai-outreach/_table.blade.php`, `_badges.blade.php`, `_analyze-modal.blade.php` | Blade | Analyses list, status badges, start-analysis modal | AI Outreach pages, Lead pages | Yes | Yes | form fields, `selectable` checkboxes |
| Campaign nav | `user/campaigns/_nav.blade.php` | Blade | Tabs for Campaigns/Contacts/Folder sends | campaigns pages | Yes | Yes | routes |
| Email sending tabs | `user/email-sending/_tabs.blade.php` | Blade | Gmail / Resend tabs | email-sending pages | Yes | Yes | plan-feature gates |
| Email analytics partials | `user/email-analytics/_timeline.blade.php`, `_follow-up-form.blade.php` | Blade | Event timeline, follow-up form | analytics pages | Yes | Yes | `POST user.email-follow-ups.store` fields |
| Lead Center partials | `user/lead-center/_access-bar`, `_access-controls(+scripts)`, `_coverage-map-modal(+scripts)`, `_folder-modal(+scripts)`, `_import-modal(+scripts)`, `_location-modal(+scripts)`, `_location-select2-helper`, `_share-access-modal(+scripts)`, `_message-bubble`, `_shared-grant-item`, `_target-location-item`, `_text-resource-item` | Blade+JS (Leaflet, Select2) | Modals and rows for Lead Center | Lead Center pages | Yes | Visual: Yes; scripts: No | element ids referenced by the `*-scripts` partials |
| Other errors | `user/partials/_other-errors.blade.php` | Blade | Validation error list | user forms | Yes | Yes | `$errors` usage |

## 3. UI element patterns (no shared component — repeated markup)

| Pattern | Typical markup / location | Notes |
|---|---|---|
| Card | `bg-white rounded-xl shadow-sm border border-gray-100 p-5` (dashboard) / `bg-white border border-gray-200 rounded-2xl` (newer) | duplicated everywhere; two styles |
| Stat card | label `text-xs uppercase tracking-wide text-gray-500`, number `text-3xl font-bold`, icon tile `bg-indigo-100 rounded-lg p-3` (`user/dashboard.blade.php:239-292`, `user/leads.blade.php:65-110`, `user/lead-center/index.blade.php:55-112`, admin dashboard) | 4+ variants |
| Buttons | see 03 §4; no button component | orange vs blue primary |
| Inputs/selects | raw `<input>`/`<select>` with utility classes; Select2 on location/niche selects | |
| Modal | `<div id="xModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">` + centred `bg-white rounded-xl shadow-xl max-w-*` (53 occurrences in 30 files), opened by `classList.remove('hidden')` | no shared modal, ESC/focus handling inconsistent |
| Toast | `#successToast` (layout), `cnmToast()` (`user/ai-sales-profile/index.blade.php:855`, `bottom-4 right-4`), ad-hoc toast in affiliate banner | 3+ implementations |
| Flash alert | layout `session('success'|'error'|'warning')` → left-border alert with close button (`layouts/app.blade.php:268-322`) | duplicate `id="flashMessage"` |
| Table | `<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">` with `thead bg-gray-50` | 49 tables |
| Pagination | `$paginator->links('pagination::tailwind')` (Laravel's Tailwind view) | |
| Tabs/pills | status/category pill links built from arrays (`user/leads.blade.php:69-76`, `315-400`) | server-side GET filters |
| Accordion | native `<details>/<summary>` (Offers "More details", admin Reports group) | |
| Badges | `rounded-full px-2 py-0.5 text-[11px] font-semibold` + tint | |
| Empty state | per page (e.g. `user/lead-center/index.blade.php:362-405` has 4 empty states) | not shared |
| Loader | `fa-spinner fa-spin`, `animate-spin`, search progress bar (`user/search.blade.php:208`), login loading overlay | no skeletons |
| Domain widgets | Search form with Country→State→City cascade (`/user/api/states`, `/user/api/cities`), lead cards/table with SEO score badge, Lead Center pipeline statuses + conversation view, coverage map (Leaflet), offer builder | |

Duplicates / inconsistent implementations: 3 layout `<head>` copies (app/admin + each standalone page), sidebar markup duplicated between `layouts/sidebar.blade.php` and `layouts/admin.blade.php`, Country/State/City cascade JS duplicated in `user/search.blade.php:793-830` and `user/leads.blade.php:2013-2060` (and Lead Center helper), stat cards, modals, toasts.

## 4. Route & page inventory

Legend — Layout: **APP** = `layouts.app` (user panel), **ADM** = `layouts.admin`, **SA** = standalone HTML. Role gates: **U** = auth + profile complete + active subscription (most user pages), **F:x** = plus plan feature `x`, **A** = admin. Complexity = redesign effort.

### Public & auth
| Route | Name | View | Layout | Role | Main sections / interactions | Complexity |
|---|---|---|---|---|---|---|
| `/` | `home` | `home.blade.php` (1,953 lines) | SA (Inter) | guest | fixed navbar, hero (`components/modern-hero`), demo, sample data, who-is-it-for, features, how it works, pricing (from DB packages), FAQ, final CTA (`#111111`), footer; referral capture | High |
| `/privacy-policy`, `/terms-and-conditions`, `/refund-policy` | `privacy.policy`, `terms`, `refund.policy` | `pages/*.blade.php` | SA | guest | long-form legal text, canonical URLs | Low |
| `/auth`, `/login` | `auth.show`, `login` | `auth/login.blade.php` (962) | SA, jQuery | guest | side-by-side Sign In / Create Account cards (tab toggle < lg), Google sign-in, forgot-password modal, loading overlay; AJAX `POST auth.login`, `auth.signup`, `auth.send.reset.link`; CSRF refresh via `GET csrf.token`; `ref` code | High |
| `/auth/forgot-password`, `/auth/reset-password/{token}` | `auth.forgot.password`, `auth.reset.password` | `auth/forgot-password`, `auth/reset-password` | SA | guest | single form | Medium |
| `/choose-account-type` | `auth.choose.account.type` | `auth/choose-account-type.blade.php` | SA | auth | Individual vs Company choice → `POST auth.save.account.type` | Medium |
| `/email/unsubscribe/{email}` (signed) | `email.unsubscribe` | `email-unsubscribe.blade.php` | SA | guest | confirm form | Low |
| maintenance | — | `maintenance.blade.php` | SA | — | message | Low |

### User panel (APP)
| Route | Name | View | Role | Main sections / interactions | Data | Complexity |
|---|---|---|---|---|---|---|
| `/user/dashboard` | `user.dashboard` | `user/dashboard.blade.php` (712) | U (restricted users allowed) | follow-up reminder, email verification notice, pending subscription warning, welcome hero (blue gradient) + playlist/community, lead status cards ×4, quick actions, plan stats (daily leads, export, devices, support), my devices, getting started, recent leads, account status, recent searches | `$stats`, `$user`, subscription | High |
| `/user/search` (+ `POST`) | `user.search`, `user.search.post` | `user/search.blade.php` (1,337) | U | heading, search form (Country/State/City Select2 cascade, query, radius, reviews filters — "extension mode" variant), progress bar, tips, results header + grid cards with social links, save-lead buttons, error and save-status modals | `/user/api/states/{c}`, `/user/api/cities/{s}`, `/user/api/niches`, `POST user.leads.save` | High |
| `/user/search-history`, `/user/search-results/{id}` | `user.search-history`, `user.view-search-results` | `user/search-history.blade.php` | U | table, rerun (`POST user.rerun-search`), delete, export | | Medium |
| `/user/leads` | `user.leads` | `user/leads.blade.php` (2,661) | U | export info, stats, compact GET filter form (search, user filter, country/state/city, rating/review filters locked by plan), has email/phone/website toggles, status pills, category pills, folder pills, bulk bar, actions (folders, SEO analytics, details toggle, export CSV/Excel), leads table with SEO badge, lead detail modal (status, follow-up, notes, contact), pagination | many `fetch` endpoints (see 05) | High |
| `/user/lead-center` | `user.lead-center.index` | `user/lead-center/index.blade.php` (+20 partials) | U | header with access controls (shared access switcher), summary cards (filters), folders, search+filters, bulk bar, leads table, pagination, 4 empty states, modals (coverage map, import, folder, location, share access) | `LeadCenterController` | High |
| `/user/lead-center/{id}/conversation` | `user.lead-center.conversation` | `user/lead-center/conversation.blade.php` | U | chat-like message list, templates/prompts, add/delete message | | Medium |
| `/user/lead-center/resources` | `user.lead-center.resources.index` | `user/lead-center/resources.blade.php` | U | target locations, prompts, templates CRUD, map data | | Medium |
| `/user/ai-sales-profile` | `user.ai-profile.index` | `user/ai-sales-profile/index.blade.php` (917) | U, F:ai_sales_profile_enabled | 2-column: profile form (business, target audience with industries/locations pickers, outreach prefs), Skills/Services/Offers lists with toggle/delete/edit, offer-type picker → quick form (fetch HTML), right-hand guidance | `PUT user.ai-profile.update`, offers CRUD, offer-builder endpoints | High |
| `/user/ai-outreach` (+ batch, analysis) | `user.ai-outreach.*` | `user/ai-outreach/index.blade.php`, `batch.blade.php`, `show.blade.php` | U, F:ai_sales_profile_enabled | usage meter, recent requests, filter form, results table with select → add to folder; analysis detail with drafts (edit/approve/discard/restore/regenerate); batch progress polling | | High |
| `/user/email-sending` | `user.email-sending.index` | `user/email-sending/index.blade.php` | U, F:gmail_sending_enabled | Gmail connect/disconnect, tracking settings, suppressions, sent emails (cancel/retry) | | Medium |
| `/user/email-sending/resend` | `user.email-sending.resend.show` | `user/email-sending/resend.blade.php` | U, F:resend_connection_enabled | API key connect, domains (verify/refresh/tracking), senders, webhook | | Medium |
| `/user/campaigns*` | `user.campaigns.*`, `user.folder-sends.*` | `user/campaigns/index`, `form`, `show`, `contacts`, `folder-send`, `folder-send-show` | U, F:resend_campaigns_enabled (folder sends: U) | list, create/edit form (AI write, templates, test send, approve, cancel), contacts import/history/withdraw, folder sends | | High |
| `/user/email-analytics*` | `user.email-analytics.*` | `user/email-analytics/index`, `email`, `lead` | U, F:email_analytics_enabled | KPI + Chart.js charts, email detail timeline, reply records, lead view | | Medium |
| `/user/email-follow-ups` | `user.email-follow-ups.index` | `user/email-follow-ups/index.blade.php` | U, F:email_followups_enabled | due/overdue list, complete/cancel | | Low |
| `/user/subscription` | `user.subscription` | `user/subscription.blade.php` (966) | auth + profile (not team members in nav) | free-trial banner, current plan overview, feature stats, AI/email usage, devices, monthly/yearly toggle + pricing grids, "everything included" table, billing history + invoice modal, plan actions (cancel), payment modal | packages, payment methods | High |
| `/user/affiliate*` | `user.affiliate.*` | `user/affiliate/index`, `earnings`, `referrals`, `withdrawals` | auth + profile | stats, Chart.js, referral link, withdrawal request form | | Medium |
| `/user/team-members*` | `user.team-members*` | `user/team-members.blade.php` | company owner | list, add/edit modal, toggle status, delete | | Medium |
| `/user/profile` | `user.profile` | `user/profile.blade.php` | auth | profile form (WhatsApp required), avatar upload, password, preferences, verify email, clear data, delete account | | Medium |
| `/user/api-keys` | `user.api-keys` | `user/api-keys.blade.php` (936) | U | user's Google API keys CRUD/test/toggle | | Medium |
| `/user/tutorials` | `user.tutorials` | `user/tutorials.blade.php` | U | video tutorial cards, complete/reset | | Low |
| `/user/feedback/history` | `user.feedback.history` | `user/feedback_history.blade.php` | auth | feedback list | | Low |

### Admin panel (ADM, role A)
| Route | View | Sections | Complexity |
|---|---|---|---|
| `/admin/dashboard` | `admin/dashboard.blade.php` | 4 KPI cards, recent users, package distribution, activity stats, quick actions, payment history, user feedback | Medium |
| `/admin/users`, `/admin/users/{user}` | `admin/users.blade.php` | user table, filters, view/switch-to-user | Medium |
| `/admin/packages` | `admin/packages/index.blade.php` | package CRUD + features | Medium |
| `/admin/subscriptions` | `admin/subscriptions/index.blade.php` (1,054) | list, edit, devices | High |
| `/admin/payments` | `admin/payments/index.blade.php` | manual payment approval, screenshot view | Medium |
| `/admin/feedback/history` | `admin/feedback/index.blade.php` | feedback list, status | Low |
| `/admin/api-usage` | `admin/api-usage/index.blade.php` | admin API keys, counts, toggles | Medium |
| `/admin/affiliate*` | `admin/affiliate/*` (6 views) | affiliates, conversions approve/reject, withdrawals, settings | Medium |
| `/admin/reports/*` | `admin/reports/*` (10 views, Chart.js) + CSV exports | revenue, users, leads, all-leads, search, package performance, export activity, top performers, user activity, system overview | Medium each |
| `/admin/settings*` | `admin/settings/index.blade.php` (1,728), `admin/settings/email-templates/edit.blade.php` | site settings (logo/favicon/name), email, payment methods, templates | High |
| `/admin/ai-setup` | `admin/ai-setup/index.blade.php` | AI provider keys/test | Low |

## 5. Page families (share layout and patterns)

1. **Panel list pages** (filter bar + stats + table + pagination + bulk bar): My Leads, Lead Center, Search History, AI Outreach, Campaigns, Contacts, Admin Users/Subscriptions/Payments/Affiliate lists.
2. **Panel settings/forms pages** (stacked cards with forms): Profile, API Keys, Email Sending (Gmail/Resend), AI Sales Profile, Team Members, Admin Settings, Admin AI Setup, Admin Packages.
3. **Dashboards/reports** (KPI cards + charts + recent lists): User Dashboard, Admin Dashboard, Admin Reports ×10, Email Analytics, Affiliate.
4. **Transactional**: Subscription + Payment modal, Search → Save leads, Offer generation, Campaign create/approve, Withdrawal request.
5. **Public/auth**: Home, legal pages, Login/Signup, Forgot/Reset, Choose account type.
