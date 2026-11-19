# 08 — Project Knowledge Pack (condensed, for AI skill configuration)

```yaml
PROJECT_PROFILE:
  name: CustomerNearme
  prod_url: https://v2.customernearme.com   # deploys from git main
  purpose: Google-Maps lead finder + lead CRM (Lead Center) + AI sales outreach + email sending/campaigns/analytics
  business_model: subscription packages (monthly/yearly, plan feature flags, free trial), manual payment by screenshot + admin approval, affiliate program
  roles: [guest, user(individual), company owner, team member, admin]
  gates: [auth, EnsureProfileIsComplete, CheckSubscriptionAccess, EnsurePlanFeature:<flag>, AdminMiddleware, Gate manage-sales-profile]
  apps: [Laravel web app, Chrome extension (Customernearme_Extension, Manifest V3, uses /api/extension/*)]
  language: English only (no localization)
  sources: [app/Models/User.php, routes/web.php, app/Http/Middleware/*, layouts/sidebar.blade.php]

TECH_STACK:
  backend: PHP ^8.2, Laravel 12.50.0, Blade, Sanctum 4, Socialite 5, anthropic-ai/sdk, Resend, maatwebsite/excel
  frontend_runtime: Blade + Tailwind CSS v3 Play CDN (https://cdn.tailwindcss.com, inline tailwind.config) + Font Awesome 6.4.0 + vanilla JS fetch
  extra_libs: jQuery 3.6.0, Select2 4.1.0-rc.0, Chart.js (unversioned + 4.4.0), Leaflet 1.9.4, Choices.js 10.2.0  # all CDN
  installed_not_used: Tailwind 4 + Vite 7 (no @vite in views, no public/build), axios, stripe/stripe-php
  no: [React, Vue, Alpine, Livewire, Inertia, TypeScript, dark mode, websockets]
  sources: [composer.json, package.json, vite.config.js, layouts/app.blade.php:19-45]

BRAND_TOKENS:
  orange: {500: "#f97316", 600: "#ea580c", 700: "#c2410c", 50: "#fff7ed", 100: "#ffedd5", 200: "#fed7aa"}   # primary actions, brand accent, theme-color
  primary_blue: {50: "#eff6ff", 100: "#dbeafe", 500: "#3b82f6", 600: "#2563eb", 700: "#1d4ed8", 800: "#1e40af", 900: "#1e3a8a"}  # nav active, links, dashboard hero; 200-400 NOT defined
  neutrals: {app_bg: "#f9fafb (gray-50)", surface: "#ffffff", border: "#e5e7eb", input_border: "#d1d5db", heading: "#111827", body: "#374151", muted: "#6b7280"}
  status: {success: green-500/600, danger: red-600, warning: yellow-400/600, info: blue-600, admin: purple-600}
  public_page_aliases: {primary-orange: "#f97316", dark-orange: "#ea580c", primary-blue: "#3b82f6", dark-blue: "#2563eb (home) / #1d4ed8 (auth)"}
  logo: public/assets/images/{logo.svg, white-logo.svg, logo.png, favicon.png}; overridable by Setting site_logo/site_favicon/site_name
  sources: [layouts/app.blade.php:21-44, home.blade.php:57-71, auth/login.blade.php:23-53]

TYPOGRAPHY_RULES:
  panel_font: system-ui (Tailwind font-sans; no webfont loaded)
  public_font: Inter 400-900 (Google Fonts, display=swap)
  page_title: "text-xl sm:text-2xl font-bold text-gray-900"
  section_title: "text-base font-semibold text-gray-900"
  label: "text-xs font-medium text-gray-700 mb-1"
  body: "text-sm text-gray-700"; muted: "text-xs/text-sm text-gray-500"
  stat_number: "text-3xl font-bold text-gray-800"
  minimum: 11px (avoid text-[8px]/[9px]/[10px] in new work)

COMPONENT_MAP:
  layouts: {user: layouts/app.blade.php + layouts/sidebar.blade.php, admin: layouts/admin.blade.php, public: standalone pages}
  partials: [partials/payment-modal, partials/feedback-modal, partials/pricing-features, partials/select2-assets, partials/safe-html-helpers]
  components: [components/user-filter (<x-user-filter>), components/modern-hero]
  module_partials: [user/offer-builder/_*, user/lead-center/_*, user/ai-outreach/_*, user/campaigns/_nav, user/email-sending/_tabs, user/email-analytics/_*]
  patterns_without_component: [card, stat card, button, input, modal (fixed inset-0 ... z-50 hidden), toast, table, badge, empty state]
  unused: [components/old_v2modern-hero, components/oldmodern-hero, emails/oldlayout, admin/settings.blade.php (empty)]

ROUTE_MAP (key):
  public: [/ home, /privacy-policy, /terms-and-conditions, /refund-policy, /auth (auth.show), /choose-account-type]
  user: [user.dashboard, user.search, user.search-history, user.leads, user.lead-center.index, user.lead-center.conversation,
         user.lead-center.resources.index, user.ai-profile.index, user.ai-outreach.index, user.email-sending.index,
         user.email-sending.resend.show, user.campaigns.index, user.email-analytics.index, user.email-follow-ups.index,
         user.subscription, user.affiliate.index, user.team-members, user.profile, user.api-keys, user.tutorials]
  admin: [admin.dashboard, admin.users, admin.packages.index, admin.subscriptions.index, admin.payments.index,
          admin.feedback.history, admin.api-usage.index, admin.affiliate.*, admin.reports.*, admin.settings.index, admin.ai-setup.index]
  full_list: php artisan route:list --except-vendor
  sources: [routes/web.php, 04-COMPONENT-AND-PAGE-MAP.md]

LAYOUT_MAP:
  user_panel: "sidebar fixed w-64 (off-canvas < lg) + main lg:ml-64; header white shadow-sm; content padded; body bg-gray-50"
  admin_panel: "same shell; grouped sidebar (Overview/Management/Reports/Settings); purple Admin badge; header shows @yield('title')"
  public: "fixed top navbar z-50, sections py-20, max-w-7xl containers, dark #111111 CTA, footer"
  auth: "decorative gradient background, two cards (Sign In / Create Account) side-by-side >= lg, tabs below"

FRONTEND_CONVENTIONS:
  - Blade views @extends layout, @section('content'), scripts via @push('scripts') or inline <script>
  - fetch with headers X-CSRF-TOKEN (meta csrf-token), Accept: application/json; JSON {success,...} (older) or {ok,...} (newer)
  - escape JS-built HTML with window.cnmEscape / cnmSafeUrl
  - delegated event listeners guarded by window.<flag>Wired
  - assets via asset('public/...') (project root is web root)
  - partial files prefixed "_" inside module folders; Blade comments explain intent
  - flash messages: session('success'|'error'|'warning') rendered by layout

RESPONSIVE_RULES:
  - Tailwind defaults; lg (1024px) = structural breakpoint (sidebar docks); sm for small tweaks
  - design/test at 375, 768, 1024, 1280
  - tables: keep overflow-x-auto; optional mobile card list from same loop
  - header buttons icon-only below sm; hamburger fixed top-left (header pl-16 on mobile)
  - touch targets >= 40px; no horizontal page scroll

INTEGRATION_CONSTRAINTS:
  - never change route names, URLs, HTTP methods, form field names, JSON keys, JS hook ids/classes/data-*, polling endpoints
  - keep @csrf/@method, plan/role conditionals (PlanFeatures::enabled, isTeamMember, isCompany, hasRestrictedAccess, @can)
  - payment = multipart POST user.payment.submit (package_id, payment_method_id, screenshot)
  - search = fetch POST user.search.post (HTML or JSON response), Country→State→City via /user/api/states|cities
  - offer builder = fetch HTML fragment + generate + poll
  - details: 05-FRONTEND-INTEGRATIONS.md

DESIGN_PRESERVATION_RULES:
  - keep orange as primary action/brand, blue as nav/info, gray neutrals, light theme, Font Awesome, Inter on public pages
  - keep logo/site-name settings logic, sidebar items + gates, SEO meta/JSON-LD/canonical on public pages
  - may restyle cards/buttons/forms/tables/modals/toasts and recompose page content
  - no new colors as primary, no dark mode, no new fonts/icon sets without approval

IMPLEMENTATION_RULES:
  - smallest file set; edit in place; reuse partials; new repeated UI -> module _partial
  - Tailwind v3 Play CDN classes only; use only defined primary shades (50,100,500-900)
  - no npm installs, no @vite switch, no new CDN libs, no backend/DB changes without approval
  - keep inline scripts working; do not rename hooks; escape output

TESTING_COMMANDS:
  - php artisan test                       # full suite, 539 passing at audit time (SQLite memory)
  - php artisan test tests/Feature/<Module> # module tests (OfferBuilder, AiSalesProfile, Security, ...)
  - php artisan view:clear                 # after Blade changes
  - php artisan route:list --except-vendor # read-only route map
  - vendor/bin/pint --test                 # PHP style (only for PHP files)

AI_FEATURES:   # details: 11-AI-FEATURES.md
  engine: App\Services\Ai\AiService; providers anthropic (default) | openai; admin page /admin/ai-setup
  quotas_monthly: {website_audit: ai_audits_monthly, email_draft: ai_email_drafts_monthly, offer_builder: ai_offers_monthly}  # -1 unlimited, 0 not in plan
  screens:
    - AI Sales Profile (user.ai-profile.index): business, target industries/locations chips, tone/language, skills 3-10, services 1-5, offers
    - Offer Builder (inside AI Sales Profile): 4 categories x 16 Hormozi-style offer types; product = own Services, audience = Target industries; Generate with AI (queue + poll) or Save without AI; output WhatsApp message + cold email
    - AI Outreach (user.ai-outreach.*): Analyze with AI from My Leads -> estimate -> batch (poll) -> analysis page (evidence + editable draft, approve/regenerate, Gmail/Resend send with confirmation)
    - Campaigns: Write with AI (user.campaigns.ai-write) subject + message suggestion
    - Folder sends: each lead's own AI email
  rules: AI never sends by itself; keep quota/plan/team-member gates; keep status chips 1:1; keep .ob-* and cnmAnalyze* hooks
  most_active_area: yes (daily commits 2026-11-13..17) - re-read files before redesigning

KNOWN_LIMITATIONS:
  - no browser/E2E or JS tests; visual regressions are caught only manually
  - Tailwind Play CDN is unversioned and not meant for production (runtime JIT, flash of unstyled content risk)
  - Chart.js unversioned on 8 pages
  - very large single-file views (user/leads 2,661 lines, home 1,953, admin/settings 1,728)
  - duplicated head/config per page; duplicated sidebar markup; duplicated cascade JS
  - header title hard-coded "Dashboard"; <title> ignores @section('title') in user panel
  - primary-200/300/400 classes used but undefined
  - tests assert some HTML strings — markup changes may need test updates
```
