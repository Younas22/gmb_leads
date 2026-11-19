# 06 — Skill 1: UI/UX Redesign Architect — Requirements

This file also contains the **responsive audit (Phase 7)** and the **redesign opportunity matrix (Phase 8)**, because Skill 1 uses both.

---

## Part A — Responsive design audit [Confirmed from code]

| Area | Current behavior | Evidence |
|---|---|---|
| Breakpoints | Tailwind defaults; `sm` (640) used most (776×) for small tweaks; **`lg` (1024) is the structural breakpoint**; `md` rare (60×), `xl` almost unused (6×) | class counts |
| Panel sidebar | `fixed w-64`, hidden off-canvas below `lg` (`-translate-x-full`), docked at `lg` (`lg:translate-x-0`); main area `lg:ml-64` | `layouts/sidebar.blade.php:5`, `layouts/app.blade.php` |
| Mobile nav | floating hamburger `fixed top-4 left-4 z-50` (blue `bg-primary-600`), backdrop `#sidebar-backdrop` (`z-40`), close chevron, ESC closes, body scroll locked | `layouts/app.blade.php:50-55`, script ~`:640-680` |
| Desktop collapse | "fullscreen" button hides the sidebar with inline `transform` and swaps `lg:ml-64` → `ml-0`; state is **not remembered** | `toggleSidebarFullscreen()` |
| Header | `pl-16 lg:pl-8` to leave room for the hamburger; header buttons become icon-only below `sm` (comment says built for 360–414px) | `layouts/app.blade.php:63-105` |
| Countdown banner | `flex justify-between` without wrapping → can overflow on phones | `layouts/app.blade.php:~108-145` (known risk) |
| Grids | typical `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4` / `grid-cols-2 lg:grid-cols-4` for stat cards | dashboard, leads |
| Tables | wrapped in `overflow-x-auto` (46 wrappers for 49 tables) → horizontal scroll on phones; no card/stacked mobile view | leads, lead center, admin |
| Filters on mobile | My Leads has a mobile toggle that collapses filters (`user/leads.blade.php:116-140`); other list pages simply stack | |
| Forms | single column on mobile, `sm:grid-cols-2` on larger screens (newer forms) | offer builder, AI profile |
| Modals | `p-4` gutter, `max-h-[90vh]` + inner scroll in newer modals; older modals may exceed viewport height | extension modal vs others |
| Marketing | fixed top navbar `z-50`, sections `py-20`, `max-w-7xl` containers | `home.blade.php:363` |
| Auth | two cards side-by-side ≥ `lg`, sliding tab toggle below `lg` | `auth/login.blade.php:320-336` |
| Touch targets | sidebar links `py-3` (~44px) OK; many small buttons `px-2.5 py-1 text-xs` (~28px) and `text-[10px]` badges are below 44px | |
| Fixed elements | hamburger, toasts (`top-4 right-4` and `bottom-4 right-4`), lightbox, modals | possible overlap of hamburger with page content top-left |

Known responsive issues: tables scroll sideways instead of adapting; tiny text (`text-[8px]`–`text-[10px]`); countdown/affiliate banners crowd phones; long pages (Leads, Search, Subscription) have very long vertical stacks on mobile; sidebar collapse not persisted.

Rules for both skills: keep `lg` as the sidebar breakpoint, keep the off-canvas sidebar + backdrop pattern (ids and JS), design every screen at **375px, 768px, 1280px**, give tables a mobile pattern (stacked cards or priority columns) **only by changing markup/CSS, never the data or actions**, keep touch targets ≥ 40–44px.

---

## Part B — UI/UX redesign opportunity matrix

| # | Opportunity | Evidence | Impact | Effort | Priority |
|---|---|---|---|---|---|
| 1 | One consistent **card / stat-card** style (radius, border, shadow, header) | `rounded-xl` vs `rounded-2xl`, `border-gray-100` vs `200`; 4+ stat card variants | High | Low | **P1** |
| 2 | Clear **primary vs secondary action** colors (orange = primary action, blue = navigation/info) | orange and blue both used as primary buttons | High | Low | **P1** |
| 3 | **Header bar**: show the real page title (currently always "Dashboard"), breadcrumbs, tidy action buttons | `layouts/app.blade.php:70` | High | Low | **P1** |
| 4 | **Dashboard composition**: too many stacked sections (12+); group into hero + KPI row + "next actions" + recent activity | `user/dashboard.blade.php` | High | Medium | **P1** |
| 5 | **List pages** (My Leads, Lead Center): compact filter toolbar, sticky bulk bar, better table density, mobile card view | 2,661-line `user/leads.blade.php` | High | High | P2 |
| 6 | Unified **toast / flash / confirm** visual style (keep current functions, restyle) | `#successToast`, `cnmToast`, `alert()`, `confirm()` | Medium | Medium | P2 |
| 7 | Unified **modal** look (header, body, footer, close button, max-height scroll) | 53 modal wrappers | Medium | Medium | P2 |
| 8 | **Empty / loading / error states** with icon + one line + one action; skeletons for fetched fragments | per-page empty states, spinners only | Medium | Low | P2 |
| 9 | **Typography**: consistent page/section title sizes; drop `text-[8px]`/`[9px]`; consider Inter in panels (approval) | size counts | Medium | Low | P2 |
| 10 | **Forms**: one label style, one focus ring (orange), helper text + error text pattern | mixed `focus:ring-orange-200` / `focus:ring-primary-500` | Medium | Low | P2 |
| 11 | **Sidebar**: group links (Leads / AI / Email / Account), section labels like the admin sidebar, collapse persisted | flat list of 12+ items | Medium | Low | P2 |
| 12 | **Pricing / subscription** page clarity: plan cards, comparison table, current-plan summary | `user/subscription.blade.php` | High (revenue) | Medium | P2 |
| 13 | **Marketing home**: tighten 10 sections, consistent section headers, stronger hero proof | `home.blade.php` | High | High | P3 |
| 14 | **Accessibility**: focus-visible styles, `aria-label` on icon buttons (only 125 aria attributes total), modal focus trap, color contrast of `text-gray-400` on white for body text | | Medium | Medium | P2 |
| 15 | **Admin reports**: one chart card style, consistent date filters | 10 report views | Low | Medium | P3 |

---

## Part C — Skill 1 configuration

### C1. Context the skill must receive or remember
1. `08-PROJECT-KNOWLEDGE-PACK.md` (always).
2. For the page being redesigned: route name, view file, layout (APP / ADM / SA), user role, plan-feature gates, and the list of functional controls (from `04` + `05`).
3. The screenshot(s): desktop (≥1280px) and, if available, mobile (375px).
4. Whether the page is a panel page (sidebar + header stay) or a standalone public page.

### C2. What to extract from each screenshot
- Page purpose and primary task ("find leads", "manage pipeline", "pay for a plan"…).
- Every **functional control**: inputs (with labels), selects, toggles, buttons, links, tabs/pills, bulk actions, table columns, row actions, pagination, modals triggered, status badges, counters.
- Data blocks and their source (stat numbers, lists, charts).
- Conditional/locked elements (e.g. "locked" filters that link to Subscription, plan badges, warnings).
- Current visual problems (hierarchy, density, alignment, spacing, contrast).

### C3. Brand tokens to enforce
Use only the palette in `03-DESIGN-SYSTEM.md §6`: orange `#f97316`/`#ea580c` for primary actions and brand highlights, blue `#3b82f6`/`#2563eb`/`#1d4ed8` for navigation active state, links and informational accents, Tailwind gray neutrals, Tailwind green/red/yellow for status, purple only for admin identity. Light theme only. Font Awesome icons. Inter for public pages; panels use the system stack unless approval to add Inter is given.

### C4. Rules for preserving function
- Every control found in C2 must appear in the redesign (it may move, regroup, or change style). Nothing may be silently removed or added without being flagged as "requires backend work".
- Keep labels' meaning (renaming copy is allowed only if flagged; tests assert some texts — see 09).
- Keep the sidebar items, their order groups and their gates; may restyle and group them.
- Keep plan/role-specific variants: produce a note on what changes for team members, restricted users, and plan-locked features.

### C5. How far layouts may change
- **Panel pages:** free to recompose the content area (grid, card grouping, order of sections, toolbars, tabs) inside the existing shell (sidebar 256px left + header). Shell may be restyled, not replaced with a different navigation model (e.g. no top-nav-only panel) without approval.
- **Dashboards:** may fully recompose — hero/summary, KPI row (4 cards), "next best action" list, recent activity, plan usage. Charts only where data already exists.
- **Public landing pages:** may fully recompose sections, but keep SEO headings (one `h1`), section anchors used by nav (`#features`, `#how-it-works`, `#pricing`, `#faq`, `#demo`, `#contact`), pricing data from DB, CTAs to `auth.show`.
- **Forms / transactional screens:** keep field order logic (dependent selects Country→State→City), required markers, step order (choose plan → payment method → upload screenshot → submit). Prefer one-column on mobile, two-column ≥ `sm`.
- **Tables:** may change density, column grouping, sticky header, row action menus, and add a mobile card layout.

### C6. Visual design principles
Premium SaaS look: generous but consistent spacing (4/8px rhythm), clear hierarchy (one primary action per area, orange), calm neutral surfaces, `rounded-xl`/`rounded-2xl` cards with `border-gray-200` + `shadow-sm`, subtle tints for status, consistent icon tiles, readable sizes (min 12px, body 14px), strong empty states, aligned toolbars, visible focus states, WCAG AA contrast.

### C7. Must avoid
Recoloring the old screen only; inventing features, data or metrics; removing controls; new brand colors (teal/purple gradients as primary), dark mode, glassmorphism that hurts contrast; fonts other than Inter/system; icons outside Font Awesome; designs that need a JS framework, new npm packages or a build step; text smaller than 11px; layouts that need data the page does not have.

### C8. Output the skill must produce
1. **Redesigned screenshot / mockup** for desktop (1280–1440px) and mobile (375px) (tablet optional).
2. **Design spec** (Markdown):
   - Page summary & primary task.
   - Section-by-section layout (grid, order, widths).
   - Token usage per element (color HEX + Tailwind class, radius, shadow, type size/weight).
   - **Control mapping table**: old control → new location/style (must list every control from C2).
   - State designs: empty, loading, error, success, disabled/locked, plan-restricted.
   - Responsive notes (375 / 768 / 1280).
   - Accessibility notes.
   - Flags: anything that would need backend/data changes (default: none).

### C9. Ideal workflow
1. Read knowledge pack → identify page (route/view/layout/role).
2. Inventory controls + data from the screenshot (C2) and cross-check with `04`/`05`.
3. Define the page's primary task and information hierarchy.
4. Produce 1 bold layout concept (not a re-skin), check it against brand tokens and preservation rules.
5. Render desktop + mobile; design key states.
6. Write the design spec + control mapping table; self-check: every control present, brand colors only, no new data.
