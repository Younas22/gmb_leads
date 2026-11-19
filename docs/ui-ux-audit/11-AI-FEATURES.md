# 11 — AI Features (modules, UI, integrations, recent updates)

> Added after review: the first ten reports only mentioned AI modules in passing. This report covers every AI feature the redesign skills must understand. [Confirmed] from code unless stated. No API keys were read.

## 1. AI engine (backend, do not change)

| Item | Finding | Source |
|---|---|---|
| Entry point | `App\Services\Ai\AiService` (`isAvailable()`, `quota()`) — every AI call goes through it | `app/Services/Ai/AiService.php` |
| Providers | `anthropic` (default; model from `AI_ANTHROPIC_MODEL`, default `claude-opus-5-5`, effort `medium`) and `openai` (`AI_OPENAI_MODEL`). `NullAiClient` when not configured, `FixtureAiClient` for tests | `config/ai.php`, `app/Services/Ai/Providers/*` |
| Admin control | **Admin → AI Setup** (`/admin/ai-setup`, `admin/ai-setup/index.blade.php`): choose active provider, save keys, "Test" button (`POST admin.ai-setup.test/{provider}`) | `Admin\AiSetupController`, `App\Services\Ai\AiSettings` |
| Kill switch | `AI_ENABLED`; when AI is off or quota is 0 the UI hides/disables AI buttons and shows a message | `config/ai.php:16`, `$canGenerate` in views |
| Quotas (per plan, monthly) | `website_audit → ai_audits_monthly`, `email_draft → ai_email_drafts_monthly`, `offer_builder → ai_offers_monthly`; `-1` = unlimited, `0` = not in plan | `config/ai.php:64-92`, `App\Services\Ai\AiQuota` |
| Usage log & cost | `AiUsageLog` (tokens, failures, estimated USD) shown in AI Outreach "Usage" | `GET user.ai-outreach.usage` |
| Queue | AI jobs run on `ai_database` connection, queue `ai`; job timeout 170 s, 3 tries, batch max 25, max 100 open items; stuck items recovered by `ai:recover-stuck` | `config/ai.php:135-153`, `docs/ai-outreach-operations.md` |
| Context | `SalesProfileContext` builds the user's AI Sales Profile (company, services, skills, offers, target industries/locations, tone, language) for every prompt | `app/Services/Ai/SalesProfileContext.php` |
| Safety | `PromptSanitizer` cleans scraped/user text; website facts verified (`LeadFacts`); AI never sends email by itself — every send is user-confirmed | `app/Services/Ai/Outreach/*` |
| Languages / tones offered in UI | languages: English, Urdu, Arabic, Spanish, French, German, Portuguese, Italian, Dutch, Turkish, Hindi; tones: Professional, Friendly, Consultative, Short & direct, Formal, Casual | `config/ai.php:101-121` |

## 2. AI features in the UI

### 2.1 AI Sales Profile — `/user/ai-sales-profile` (`user/ai-sales-profile/index.blade.php`)
Gate: plan flag `ai_sales_profile_enabled`; editing only for account owners (Gate `manage-sales-profile`; team members read-only).
Sections: Business · Target audience (**Target industries** picked from Find Leads niches, **Target locations** Country → State → Cities, shown as removable chips) · Outreach preferences (tone, language) · **Skills** (main skill → sub skill dropdown from `config/skills.php`, 3–10) · **Services** (1–5, title + price + description) · **Offers** · right-hand guidance column.
Interactions: profile `PUT user.ai-profile.update`; skills/services/offers add/edit/toggle/delete without reload (`user.ai-profile.offers.*`, `cnmToast`).

### 2.2 AI Offer Builder (inside the Offers section)
- Catalog: 4 categories × 16 offer types (based on Alex Hormozi's offer frameworks, shown with a short intro):
  - **Attraction:** Win Your Money Back, Free Giveaways, Decoy Offers, Buy X Get Y, Pay Less Now, Free with Consumption
  - **Upsell:** Classic Upsell, Menu Upsell, Anchor Upsell, Rollover Upsell
  - **Downsell:** Payment Plans, Free Trials, Feature Downsells
  - **Continuity:** Continuity Bonus Offers, Continuity Discounts, Waived Fee
  (`database/seeders/data/offer_builder_catalog.php`, models `OfferCategory`, `OfferStrategy`)
- Flow: choose offer type card → short form loaded as HTML (`GET user.offer-builder.quick-form`, `_quick.blade.php`) with "Use it when", **Examples (3)**, **Prompt** preview → fields pre-filled from the profile:
  - "Product or service" = **only the user's own Services** (first selected) + "＋ Add a new service" inline (saved to Services) — "Custom" only when no services exist.
  - "Who is it for?" = **only the profile's Target industries** (first selected); general list only when none are set.
  - Price, main problem, strategy-specific fields; "More details (optional)" accordion (offer name, WhatsApp + cold-email oriented details, extra instructions).
- Actions: **Generate with AI** (`POST offers` → `POST offers/{id}/generate` → poll `GET .../generations/{gen}` → result with WhatsApp + cold email messages, user inputs highlighted in colour `_messages`, `_chips`) or **Save without AI** (`POST user.offer-builder.quick-save`). Generated offers are added to the profile Offers list with a live enable/disable toggle.
- Limits: 3 open generations per user, 300 offers per user, field length limits (`config/offer_builder.php`), quota `ai_offers_monthly`.

### 2.3 AI Outreach — `/user/ai-outreach` (+ batch, analysis pages)
- Start: **"Analyze with AI"** in My Leads (bulk bar `user/leads.blade.php:444` and lead modal `:1604`) → confirmation modal `user/ai-outreach/_analyze-modal.blade.php` (`#cnmAnalyzeModal`) → `POST user.ai-outreach.estimate` (shows cost/quota) → `POST user.ai-outreach.start`.
- Pipeline per lead (job `AnalyzeLeadOpportunity`): stages `audit` (website check, `AuditLeadWebsite`) → `analysis` → `draft` (`GeneratePersonalizedEmailDraft`) → `done`.
- Status chips (`_badges.blade.php`): analysis `pending / analyzing (spinner) / completed / failed / quota_exhausted`; website `verified / no_website / pending / invalid_url / unreachable / blocked`; draft `No draft yet / draft / approved / discarded`.
- Index page: usage card (used / limit / tokens / failed / estimated cost), recent requests, filters, results table with checkboxes → **add to lead folder** (`POST user.ai-outreach.folders.add`) for folder sending.
- Batch page: progress via polling `GET .../batches/{batch}/status`; "retry failed".
- Analysis page (`show.blade.php`): left = evidence (verified findings, strengths), opportunity (likely needs); right = **email draft** editor (edit `PUT drafts/{draft}`, approve, discard, restore, regenerate, retry), "What this draft is based on", draft history; send sections **Gmail** (approved drafts only, review modal with tracking info) and **Resend** (opted-in contacts only, review modal). Nothing is sent without the final confirmation checkbox.

### 2.4 Campaigns — "Write with AI"
`user/campaigns/form.blade.php:71-120`: writes subject + message from the AI Sales Profile and an optional brief (`POST user.campaigns.ai-write`, `App\Services\Ai\CampaignWriter`). Suggestion only — user edits; counts as one email-draft quota unit; footer/unsubscribe added by the system.

### 2.5 Folder sends (each lead gets its own AI email)
`/user/campaigns/folder-send` (`Outreach\FolderSendController`): pick a lead folder → each lead's own AI draft is sent; progress page `folder-sends/{send}`, cancel.

### 2.6 Admin
AI Setup page (active provider, per-provider API key as a password field `{provider}[api_key]`, model settings, "Test" button). Plan AI quotas are defined in `App\Support\PlanFeatureCatalog` (per-package values via `PackageFeature`) and shown on the home pricing table, pricing cards (`partials/pricing-features.blade.php`) and the subscription page (commit `1596bcd`). [Unverified: which admin screen edits the AI quota values per package — not found in `admin/packages/index.blade.php`.]
Note: `admin/ai-setup/index.blade.php:51` uses `border-primary-300`, which is undefined (renders no colored border) — same issue as in 03.

## 3. Recent AI updates (git history, 2026-11-13 → 2026-11-17)

| Date | Commit | Update |
|---|---|---|
| 11-13 | `5ca706e` | Phase 2: AI draft review UI, analyze modal, scheduler, commands |
| 11-13 | `04410d0` | Gmail email sending (for approved AI drafts) |
| 11-13 | `92e7570` | Phase 4: email tracking, manual replies, follow-ups, analytics |
| 11-13 | `41a1793` | Phase 5: customer Resend integration (BYOK), consent-based campaigns |
| 11-14 | `62c32b4` | Dynamic AI provider setup (admin AI Setup) |
| 11-14 | `b28a1c7` | Lead folders in AI Outreach + send to a lead folder (each lead's own AI email) |
| 11-14 | `e396053` | Campaigns: "Write with AI" from the AI Sales Profile |
| 11-14 | `921b009` | AI Sales Profile: target industries & locations from Find Leads data |
| 11-14 | `1596bcd` | Pricing/subscription show the new plan features (AI quotas) |
| 11-16 | `fd50654`, `7761378`, `75a78c3` | Skills main/sub dropdown (3–10), services 1–5, delete without reload, target chips |
| 11-16 | `15fbc01` | AI Offer Builder: 4 categories, 16 strategies, prompt engine |
| 11-16 | `6698cec` … `5655535` | Offers moved into the AI Sales Profile: examples, prompt view, coloured inputs, live toggle, shorter AI messages, Hormozi intro |
| 11-16 | `249898c` | Offer form product dropdown lists your Services; add a service inline |
| 11-17 | `4a3cc31` | Product = own Services only; "Who is it for?" = profile Target industries (first selected) |

The AI area is the **most actively changing part of the product** — skills must re-check these files before redesigning AI pages.

## 4. Redesign guidance for AI screens

**Must preserve**
- Every AI button's gate: `$canGenerate`, plan flags, quota messages ("not in plan", "used all … this month"), `AI is not configured` fallbacks, team-member read-only state.
- The review-before-send model: approve ≠ send; Gmail/Resend modals with confirmation checkbox; tracking disclosure text.
- Polling endpoints and status values (map chips 1:1 — colours may change but every status needs a distinct, labelled style; `analyzing` keeps a progress indicator).
- Offer Builder hooks: `.ob-quick[data-strategy]`, `.ob-field[data-key]`, `.ob-pick`, `.ob-input`, `.ob-product`, `__custom`, `__new_service`, `.ob-new-service[data-url]`, `.ob-q-generate/.ob-q-save/.ob-q-status/.ob-q-prompt*/.ob-q-examples*`, `data-testid="quick-offer-form" | "offer-examples" | "new-service-form"`; AI Outreach ids `cnmAnalyzeModal/Body/Confirm`, `data-testid` `outbound-status`, `resend-status`, `resend-problems`, `send-from`, `resend-tracking*`.
- The highlighted-input rendering (`InputHighlighter` `<mark … title="Field label">`) — asserted by tests.
- "Generate with AI" uses the wand icon `fa-wand-magic-sparkles` and orange; keep a consistent AI visual language.

**Opportunities (P1–P2)**
1. One **AI visual language**: wand icon + orange accent + "AI" badge used the same way on AI Sales Profile, Offers, AI Outreach, Campaigns.
2. **Quota meter** component (used / limit / resets) reused on AI Outreach, Offers and Campaign AI write.
3. **Job progress** pattern (queued → running → done/failed with retry) shared by offer generation, batch analysis and folder sends.
4. **AI Sales Profile** as a guided setup (progress: Business → Audience → Skills → Services → Offers) with completeness score, since every AI feature depends on it.
5. **AI result layout**: evidence on the left, editable output on the right, sticky action bar (Approve / Regenerate / Send) — already the structure of `show.blade.php`, can be made clearer.
6. Empty states that point to the next step ("Add a service first", "Set target industries", "Analyze leads from My Leads").

## 5. AI tests to run after any AI-page redesign
`php artisan test tests/Feature/AiSalesProfile tests/Feature/OfferBuilder tests/Feature/AiOutreach tests/Feature/Ai tests/Unit/Ai tests/Feature/Resend tests/Feature/EmailSending`
