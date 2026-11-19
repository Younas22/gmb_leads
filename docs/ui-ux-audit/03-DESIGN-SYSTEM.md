# 03 — Design System & Brand Identity

> There is **no formal design system file**. Tokens below were extracted from the inline `tailwind.config` blocks, class-usage counts across all 130 Blade views, and the logo assets. Counts are occurrences in `resources/views`.

## 1. Where tokens are defined [Confirmed]

| Context | Definition | File |
|---|---|---|
| User panel | inline `tailwind.config.theme.extend.colors` → `primary` (blue scale) + `orange` overrides | `layouts/app.blade.php:21-44` |
| Admin panel | identical copy | `layouts/admin.blade.php:21-44` |
| Auth pages | `primary-orange`, `dark-orange`, `primary-blue`, `dark-blue`, `primary` scale, `orange` overrides, `fontFamily.inter` | `auth/login.blade.php:23-53` (similar in other auth pages) |
| Marketing home | `primary-blue`, `dark-blue`, `primary-orange`, `dark-orange`, `fontFamily.inter` (no `primary-*` scale) | `home.blade.php:57-71` |
| Theme color meta | `#f97316` | `home.blade.php` `<meta name="theme-color">` |
| Unused | `--font-sans: 'Instrument Sans'` | `resources/css/app.css` (never loaded) |

## 2. Brand colors

### Brand identity (MUST PRESERVE)
| Role | Token | HEX | Evidence |
|---|---|---|---|
| **Brand orange (primary CTA / brand accent)** | `orange-500` / `primary-orange` | `#f97316` | most-used hex (35×), theme-color, "Add Extension" button, offer UI, pricing |
| Brand orange hover | `orange-600` / `dark-orange` | `#ea580c` | 32× hex, 166 class uses |
| Orange dark | `orange-700` | `#c2410c` | |
| Orange tints | `orange-50` `#fff7ed`, `orange-100` `#ffedd5`, `orange-200` `#fed7aa` | badges, highlighted panels |
| **Brand blue (navigation / active state / secondary CTA)** | `primary-500` / `primary-blue` | `#3b82f6` | 229 class uses |
| Blue strong | `primary-600` | `#2563eb` | mobile menu button, dashboard hero gradient `from-primary-600 to-primary-700` |
| Blue darker | `primary-700` `#1d4ed8`, `primary-800` `#1e40af`, `primary-900` `#1e3a8a` | active sidebar text `text-primary-700` |
| Blue tints | `primary-50` `#eff6ff`, `primary-100` `#dbeafe` | active sidebar bg `bg-primary-50` |

Note: the `primary` scale is **exactly Tailwind's default blue**; `orange` overrides equal Tailwind's default orange. So `blue-*` and `primary-*` render the same colors (both are used: `blue-600` 176×, `primary-600` 195×) — an inconsistency to normalize, not a brand difference.

### Neutrals (Tailwind gray) [Confirmed]
| Use | Class | HEX |
|---|---|---|
| App background | `bg-gray-50` (`<body>`) | `#f9fafb` |
| Surface | `bg-white` | `#ffffff` |
| Borders | `border-gray-200` / `border-gray-100` / inputs `border-gray-300` | `#e5e7eb` / `#f3f4f6` / `#d1d5db` |
| Headings | `text-gray-900` / `text-gray-800` | `#111827` / `#1f2937` |
| Body text | `text-gray-700` / `text-gray-600` | `#374151` / `#4b5563` |
| Muted text | `text-gray-500` (most used class, 771×) / `text-gray-400` | `#6b7280` / `#9ca3af` |
| Marketing dark section | `bg-[#111111]` | `#111111` (home final CTA) |

### Status colors (Tailwind defaults) [Confirmed]
| Status | Classes in use |
|---|---|
| Success | `green-50/100/200` backgrounds, `green-600/700/800` text, `bg-green-500` toasts/WhatsApp button |
| Error / danger | `red-50/100/200`, `red-600/700/800`; destructive buttons `bg-red-600 hover:bg-red-700` |
| Warning | `yellow-50`, `yellow-400` border, `yellow-600/800` text; also `amber-*` in places |
| Info | `blue-50/100`, `blue-600/700/800` |
| Admin identity | `purple-50/600/700` (admin badge, "Switch back to Admin") |
| Feature accents | `indigo-*` (stat icons), `emerald/teal` (affiliate banner gradient), YouTube `red-600`, WhatsApp `green-500` |

### Gradients in use
`from-orange-500 to-orange-600` (9×, CTAs/headers), `from-primary-600 to-primary-700` (dashboard hero), soft section headers `from-blue-50 to-indigo-50`, `from-orange-50 to-amber-50`, `from-green-50 to-emerald-50`, affiliate banner `from-emerald-600 via-green-500 to-teal-600`.

### Logo
`public/assets/images/logo.svg` (multicolor mark, mostly `#000000` paths with accents `#3268f1`, `#d51a1a`, `#30c901`, `#d68e3a`…), `white-logo.svg` (used in sidebars), `logo.png`, `favicon.png`. Admin can override with `Setting site_logo` / `site_favicon` — **logo rendering logic must be preserved**.

### Dark mode
**None.** 0 `dark:` classes. Do not introduce a dark mode without approval.

## 3. Typography

| Item | Value | Evidence |
|---|---|---|
| Panel font | System UI stack (Tailwind default `font-sans`) — no web font loaded in `layouts/app` / `layouts/admin` | [Confirmed] |
| Public/auth font | **Inter** 400,500,600,700,800,900 via Google Fonts with `preconnect` + `display=swap` | `home.blade.php`, `auth/*.blade.php` |
| Weights used | `font-medium` 1051×, `font-semibold` 618×, `font-bold` 352×, `font-extrabold` 38×, `font-black` 5× | |
| Sizes used | `text-xs` 1526×, `text-sm` 1503×, `text-base` 173×, `text-lg` 180×, `text-xl` 198×, `text-2xl` 167×, `text-3xl` 94×, `text-4xl` 37×; arbitrary `text-[10px]` 135×, `text-[11px]` 68×, `text-[9px]`/`[8px]` (too small) | |
| Page title (panel) | `text-xl sm:text-2xl font-bold text-gray-900` (e.g. `user/ai-outreach/index.blade.php:17`, `user/ai-sales-profile/index.blade.php:35`) | |
| Section title | `text-base font-semibold text-gray-900` or `text-lg font-semibold text-gray-800` | |
| Card stat number | `text-3xl font-bold text-gray-800` (`user/dashboard.blade.php:245`) | |
| Labels | `text-xs font-medium text-gray-700 mb-1` (newer forms) / `text-sm font-medium text-gray-700` (older forms) | |
| Overline | `text-xs font-semibold text-gray-400 uppercase tracking-wider` (admin sidebar groups), `uppercase tracking-wide` stat labels | |
| Line height / letter spacing | Tailwind defaults; `leading-tight`, `leading-relaxed`, `tracking-tight` on auth headings | |

Undefined: no type scale document, no max line length rule, no heading component.

## 4. UI tokens

| Token | Current values | Status |
|---|---|---|
| Radius | `rounded-lg` (8px) 1151× = default for buttons/inputs/nav items; `rounded-xl` (12px) 442× cards/modals; `rounded-2xl` (16px) 131× newer cards (AI Sales Profile, Offers); `rounded-full` 443× pills/avatars | de-facto standard: **inputs/buttons `rounded-lg`, cards `rounded-xl`** (newer modules `rounded-2xl`) |
| Shadows | `shadow-sm` 315× (cards), `shadow-lg` 61× (floating buttons, toasts), `shadow-xl` 48× (sidebar, modals), `shadow-2xl` 18×; custom `shadow-orange-*` 25× on marketing | |
| Spacing | Tailwind 4px scale. Page padding: content area uses `p-4 lg:p-8`-style wrappers; card padding `p-4`/`p-5`/`p-6`; grids `gap-3`/`gap-4`/`gap-6`; section spacing `mb-6`/`mb-8` | not centrally defined |
| Containers | Panels: full width inside `lg:ml-64` main area. Marketing: `max-w-7xl` (25×). Modals: `max-w-md`, `max-w-lg`, `max-w-2xl`, `max-w-4xl` | |
| Sidebar | fixed `w-64` (256px), white, `shadow-xl` | |
| Header | white, `shadow-sm border-b border-gray-200`, `px-4 lg:px-8 py-4` | |
| Breakpoints | Tailwind defaults: `sm` 640 (776 uses), `md` 768 (60), `lg` 1024 (305), `xl` 1280 (6). **`lg` is the layout breakpoint** (sidebar docks at `lg`) | |
| Z-index | sidebar `z-50`, mobile backdrop `z-40`, overlays `z-30`, modals `z-50`, lightbox `z-[60]`, plus ad-hoc `z-[70]`, `z-[100]`, `z-[999]`, `z-[1000]`, `z-[9999]` | no convention — undefined |
| Motion | `transition-colors`, `transition-all duration-300`, sidebar `transition-transform duration-300 ease-in-out` | |
| Buttons | primary: `px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg` (header) or `bg-primary-600 hover:bg-primary-700`; large: `px-4 py-2.5 text-sm font-semibold rounded-lg` (Offers); secondary: `border border-gray-300 bg-white text-gray-700 hover:bg-gray-50` | two competing primary colors (orange vs blue) |
| Inputs | `w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500` (newer, `offer-builder/_field.blade.php`) vs `focus:ring-primary-500 focus:border-primary-500` (older) | inconsistent focus color |
| Badges | `px-2 py-0.5 rounded-full text-[11px] font-semibold` + status tint | |
| Icons | Font Awesome; sidebar icons `w-5 text-center mr-3` | |

## 5. Brand preservation rules

1. **Must remain unchanged**
   - Brand orange `#f97316` / hover `#ea580c` as the main call-to-action / brand accent.
   - Brand blue `#3b82f6`/`#2563eb`/`#1d4ed8` for navigation active states, links and the dashboard hero.
   - Logo assets and the `Setting site_logo/site_favicon/site_name` override logic.
   - White surfaces on light-gray (`#f9fafb`) background; light theme only.
   - Font Awesome as the icon set; Inter on public/auth pages.
   - Product name "CustomerNearme" and module names used in the sidebar (Find Leads, My Leads, Lead Center, AI Sales Profile, AI Outreach, Email Sending, Campaigns, Email Analytics, Follow-ups…), because tests and tutorials reference them.
2. **Can be modernized**
   - Card radius/shadow (move toward one standard, e.g. `rounded-xl`/`rounded-2xl` + `border-gray-200` + `shadow-sm`).
   - Header bar (page title is hard-coded "Dashboard" in `layouts/app.blade.php:70`, and `<title>` ignores `@section('title')`), header buttons, flash banners.
   - Stat cards, empty states, tables, filter bars, modals, toasts, form layouts.
   - Panel typography (adding Inter to panels is a small, safe improvement but changes metrics — needs approval, see 09).
3. **Can be completely redesigned (visual only)**: dashboard composition, marketing home sections, auth page layout, pricing cards, settings pages, report pages, Lead Center list/table presentation — as long as the controls, field names and routes stay.
4. **Legacy inconsistencies found**
   - `primary-*` vs `blue-*` (same color, two names); `orange-*` vs `primary-orange`.
   - Different Tailwind config per page; home has no `primary-*` scale.
   - Classes `primary-200/300/400` are used (e.g. `user/leads.blade.php:78`, `user/lead-center/index.blade.php:71`, `user/tutorials.blade.php:64`) but those shades are not defined in the panel config, so they silently render nothing.
   - Mixed focus rings (orange vs blue), mixed card radius (`xl` vs `2xl`), mixed labels (`text-xs` vs `text-sm`).
   - Several toast implementations; `alert()`/`confirm()` mixed with modals.
   - Arbitrary tiny sizes `text-[8px]`/`text-[9px]` (accessibility).
   - Duplicate `id="flashMessage"` in the layout when several flash types exist.
   - Unused views: `components/old_v2modern-hero.blade.php`, `components/oldmodern-hero.blade.php`, `emails/oldlayout.blade.php`, empty `admin/settings.blade.php`.
5. **No defined standard**: z-index scale, spacing scale per component, elevation scale, motion tokens, icon sizes, empty/loading/error state patterns, table density, form error display.

## 6. Design System Reference (copy-ready)

```js
// Same as the inline config in layouts/app.blade.php and layouts/admin.blade.php
tailwind.config = { theme: { extend: { colors: {
  primary: { 50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
  orange:  { 50:'#fff7ed',100:'#ffedd5',500:'#f97316',600:'#ea580c',700:'#c2410c' }
}}}}
// Note: primary-200/300/400 are NOT defined in panels → classes like bg-primary-200 produce nothing.
```

| Token | Value |
|---|---|
| brand.orange / hover / dark | `#f97316` / `#ea580c` / `#c2410c` |
| brand.orange.tint | `#fff7ed`, `#ffedd5`, `#fed7aa` |
| brand.blue / strong / dark | `#3b82f6` / `#2563eb` / `#1d4ed8` |
| brand.blue.tint | `#eff6ff`, `#dbeafe` |
| bg.app / bg.surface | `#f9fafb` / `#ffffff` |
| border.default / subtle / input | `#e5e7eb` / `#f3f4f6` / `#d1d5db` |
| text.heading / body / muted | `#111827` / `#374151`–`#4b5563` / `#6b7280` |
| success / danger / warning | green-600 `#16a34a` / red-600 `#dc2626` / yellow-600 `#ca8a04` (Tailwind defaults) |
| radius.control / card / card-new / pill | 8px / 12px / 16px / 9999px |
| shadow.card / float / modal | `shadow-sm` / `shadow-lg` / `shadow-xl` |
| sidebar width | 256px (`w-64`) |
| layout breakpoint | 1024px (`lg`) |
| font.panel / font.public | system-ui stack / Inter |
