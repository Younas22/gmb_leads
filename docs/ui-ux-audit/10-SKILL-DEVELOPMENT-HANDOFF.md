# 10 — Skill Development Handoff

For the AI assistant that will build the two skills from this audit.

## 1. What you have

| File | Use it for |
|---|---|
| `01-PROJECT-OVERVIEW.md` | product, roles, modules, architecture diagram |
| `02-TECH-STACK.md` | what the code really runs on (Tailwind **v3 Play CDN**, not the installed Tailwind 4/Vite) |
| `03-DESIGN-SYSTEM.md` | brand palette, typography, tokens, inconsistencies, preservation tiers |
| `04-COMPONENT-AND-PAGE-MAP.md` | layouts, partials, patterns, every important route → view → sections |
| `05-FRONTEND-INTEGRATIONS.md` | UI → JS → route → controller chains; contracts never to break |
| `06-UIUX-SKILL-REQUIREMENTS.md` | Skill 1 spec + responsive audit + opportunity matrix |
| `07-FRONTEND-SKILL-REQUIREMENTS.md` | Skill 2 spec + design-to-code facts + coding-prompt template |
| `08-PROJECT-KNOWLEDGE-PACK.md` | compact YAML context to embed in both skills |
| `09-REDESIGN-SAFETY-CHECKLIST.md` | MUST PRESERVE / SAFE / APPROVAL table, risks, validation checklist |
| `11-AI-FEATURES.md` | AI engine, every AI screen and flow, status values, recent AI updates, AI redesign rules and tests |

## 2. How to build Skill 1 — UI/UX Redesign Architect

1. **Skill folder**: `SKILL.md` + `references/knowledge-pack.md` (copy of 08) + `references/design-system.md` (03 §2–§6) + `references/page-map.md` (04 §4–§5) + `references/safety.md` (09 §1).
2. **SKILL.md trigger**: "redesign / modernize / improve the UI of a CustomerNearme page from a screenshot".
3. **Body**: the workflow in 06 §C9, input requirements (06 §C1–C2), rules (06 §C3–C7) and the output format (06 §C8). Put the "genuinely new composition, not a re-skin" rule and the "every control must survive" rule at the top.
4. **Self-check list** at the end of SKILL.md: brand palette only · all controls mapped · role/plan variants noted · 375/1280 designs · states designed · no new data/backend.
5. **Output artifact**: mockup image(s) + design spec Markdown with the control mapping table (this is the hand-off to Skill 2).

## 3. How to build Skill 2 — Frontend Implementation Architect

1. **Skill folder**: `SKILL.md` + `references/knowledge-pack.md` (08) + `references/integrations.md` (05) + `references/safety.md` (09) + `references/prompt-template.md` (07 §B5).
2. **Trigger**: "turn this redesigned screenshot/spec into an implementation prompt for CustomerNearme".
3. **Body**: file discovery steps (07 §B3), visual-to-code mapping (07 §B2, §B4), conventions (07 §B9–B11), validation (07 §B12).
4. **Output**: one filled-in coding prompt (template 07 §B5) containing: page + route + files in scope, the STEP 1 contract to extract, region-by-region Tailwind instructions using tokens from 03 §6, states, responsive rules, accessibility, tests to run (find with `grep -rln` in `tests/`), and the report format.
5. It must never instruct the coding AI to change routes, controllers, models, migrations, layouts (unless in scope), dependencies or the build pipeline.

## 4. Recommended skill-building order
1. Build the knowledge-pack reference (08) and safety reference (09) first; both skills share them.
2. Include `11-AI-FEATURES.md` as a reference in both skills (AI screens change often).
3. Build Skill 1, test it on three page types (plus one AI screen: `user.ai-profile.index` or `user.ai-outreach.analysis`): `user.dashboard` (dashboard), `user.leads` (list/table), `user.subscription` (transactional) — plus `home` (public).
4. Build Skill 2, feed it Skill 1's output for the same pages, run the generated prompts on a branch, then run `php artisan test` and the manual checklist (09 §3).
5. Fold lessons back into both skills (missing tokens, recurring test updates).

## 5. Open questions to settle with the owner before/while building
1. **Panel font:** keep system font, or adopt Inter in the user/admin panels for a premium look?
2. **Primary action color:** confirm orange for primary buttons everywhere in panels (blue currently also used) and blue for navigation/info.
3. **Tailwind runtime:** stay on the unversioned Play CDN, pin a version, or move to the installed Vite + Tailwind 4 build (big change — affects every page)?
4. **Dialogs:** may `alert()`/`confirm()` be replaced with styled modals?
5. **Copy changes:** may the redesign rewrite headings/labels (tests and tutorials reference some)?
6. **Mobile tables:** card view on phones acceptable for My Leads / Lead Center?
7. **Unused files** (old hero components, old email layout, empty admin settings view): remove or keep?
8. **Header title:** may the shared header show the page's own title (needs `@yield('title')` wiring in `layouts/app.blade.php`)?
