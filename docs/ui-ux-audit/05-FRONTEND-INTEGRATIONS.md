# 05 — Frontend ↔ Backend Integrations

> Routes: `routes/web.php` (≈300 named routes, verified with `php artisan route:list`). Controllers live in `app/Http/Controllers/**`. No secrets or user data are included here.

## 1. Mechanisms in use [Confirmed]

| Mechanism | How it works | Where |
|---|---|---|
| Full-page GET + server filters | `<form method="GET">` → controller reads query string → Blade re-renders; filter pills are plain links built with `route('...', array_merge(request()->except(...), [...]))` | My Leads, Lead Center, AI Outreach, admin lists |
| Classic form POST | `<form method="POST" action="{{ route(...) }}">` + `@csrf` (+ `@method('PUT'/'DELETE')`) → redirect with `session('success'/'error'/'warning')` → layout flash banner | profile, subscription, AI Sales Profile, admin forms |
| `fetch` JSON | `fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': meta[csrf-token], Accept:'application/json'}, body: JSON.stringify(...)})` → `{success:true/false, message}` (older modules, 175 occurrences in controllers) or `{ok:true/false, message}` (newer modules: AI Sales Profile, Offer Builder) | leads, search, folders, lead center, offers |
| `fetch` + `FormData` | search form submitted via fetch; response is HTML (results) or JSON (error) — branch on `content-type` (`user/search.blade.php:870-960`) | Find Leads |
| `fetch` HTML fragments | server returns a partial view, injected into the page | `GET user.offer-builder.quick-form` (`_quick`), `GET user.offer-builder.offers.{offer}.messages` |
| jQuery `$.ajax` | `$.ajaxSetup({headers:{'X-CSRF-TOKEN':...}})`; CSRF refreshed from `GET /csrf-token` | auth pages (`auth/login.blade.php:644-800`) |
| Polling | `setInterval` / repeated `fetch` until done | AI outreach batch status (`user.ai-outreach.batch.status`, throttled), offer generation (`user.offer-builder.offers.generation`), search progress bar (visual only), subscription countdown |
| File upload | multipart form | payment screenshot (`user.payment.submit`), avatar (`user.avatar.upload`), Lead Center import (`user.lead-center.import.preview` → `.import.store`), contacts import |
| Exports | GET links returning files | `user.leads.export`, `user.leads.export.excel`, `user.export-search-history`, `admin.reports.export.*` |
| OAuth redirects | full-page redirects | Google login (`auth.google` → callback), Gmail connect (`user.email-sending.connect` → `user.email-sending.callback`) |
| WebSockets | **none** | — |
| Localization | **none** (English only) | — |

## 2. Security contracts that every redesign must keep

- `<meta name="csrf-token" content="{{ csrf_token() }}">` in every layout/standalone page; `@csrf` in every POST form; `X-CSRF-TOKEN` header in every `fetch`.
- Authorization lives on the server (middleware, policies such as `BuilderOfferPolicy`, Gate `manage-sales-profile`). UI conditionals (`@if(PlanFeatures::enabled(...))`, `@can`, `isTeamMember()`, `isCompany()`, `hasRestrictedAccess()`) only hide controls — keep them so users do not see actions that would 403.
- Escaping: Blade `{{ }}` (never switch to `{!! !!}` for user/scraped data); JS-built HTML must use `cnmEscape`/`cnmSafeUrl` (`partials/safe-html-helpers.blade.php`). Security tests exist in `tests/Feature/Security`.
- Signed URLs (`email.unsubscribe`), throttled routes (offer builder, batch status, tracked links).

## 3. Critical interaction chains

Format: **UI element → frontend logic → route/API → backend handler → expected result**

### Auth
1. Sign-in form `#loginFormElement` → jQuery submit handler, loading overlay → `POST /auth/login` (`auth.login`) → `AuthController@login` → JSON; on success JS redirects to dashboard; on error shows alert block.
2. Create-account form `#signupFormElement` (first/last name, email, password, confirm, terms, `ref` code from `localStorage`) → `POST /auth/signup` (`auth.signup`) → `AuthController@signup` → JSON + verification email.
3. Google button → link `GET auth.google` → Socialite → `auth.google.callback`.
4. Forgot-password modal `#forgotPasswordForm` → `POST auth.send.reset.link`.
5. Account type cards → `POST auth.save.account.type`.

### Find Leads (`user/search.blade.php`)
1. Country select → `fetch GET /user/api/states/{country}` (`SearchController@getStates`) → fills State; State → `GET /user/api/cities/{state}` → fills City (Select2).
2. Search form `#searchForm` → submit intercepted → `fetch POST user.search.post` (FormData) → `SearchController@search` → HTML results (rendered into results grid) or JSON error (error modal). Plan/daily limits enforced server-side.
3. "Save" on a result card → reads hidden result data → `fetch POST user.leads.save` → `SearchController@saveLeads` → save-status modal.

### My Leads (`user/leads.blade.php`)
| UI | Route | Handler |
|---|---|---|
| Filter form / pills / folder pills | `GET user.leads` (query: `search`, `country`, `state`, `city`, `status`, `has_follow_up`, `lead_category`, `folder_id`, `user_filter`, has-email/phone/website…) | `LeadsController@index` |
| Bulk status / delete / folder | `POST user.leads.bulk` `{action, lead_ids[], status?}` | `LeadsController@bulkAction` |
| Lead detail modal | `GET /user/leads/{id}` | `LeadsController@show` (JSON) |
| Status / follow-up / notes / contact | `POST /user/leads/{id}/status`, `/follow-up`, `/notes`, `/contact` | `LeadsController` |
| Delete | `DELETE /user/leads/{id}` | `LeadsController@destroy` |
| SEO analytics | `POST /user/leads/{id}/check-seo` | SEO audit service |
| Folders | `GET user.folders.index`, `POST user.folders.store`, `POST /user/folders/{id}/add-leads`, `remove-leads`, `DELETE /user/folders/{id}` | `FolderController` |
| Export | `GET user.leads.export`, `user.leads.export.excel` (with current query) | `LeadsController@export*` |

### Lead Center
Index filters (GET), bulk `POST user.lead-center.bulk` (`LeadCenterController@bulkAction`), status `POST user.lead-center.status` (`@updateStatus`), location `POST /{id}/location`, contact links `POST /{id}/contact-links`, delete, folders CRUD, import (`import.preview` → `import.store`, multipart), add from My Leads (`add-from-leads`), shared access (`access.share`, `access.switch`, `access.respond`, `access.revoke`), coverage map (`resources.map-data`, `resources.boundary` → Leaflet), conversation messages (`conversation.message.store/destroy`).

### AI Sales Profile & Offers
| UI | Logic | Route | Handler | Result |
|---|---|---|---|---|
| Profile form | normal POST | `PUT user.ai-profile.update` | `SalesProfileController@update` | redirect + flash |
| Add skill/service/offer | form POST (also JSON for "+ Add a new service") | `POST user.ai-profile.offers.store` | `SalesOfferController@store` | row added; JSON `{ok,title,price_hint}` when `expectsJson` |
| Toggle / delete row | `.cnm-toggle` / `.cnm-del` forms intercepted by fetch | `PATCH .../offers/{offer}/toggle`, `DELETE .../offers/{offer}` | `SalesOfferController` | `cnmToast`, no reload |
| Offer type card | fetch HTML | `GET user.offer-builder.quick-form?strategy=slug` | `BuilderOfferController@quickForm` | renders `_quick` |
| Prompt button | fetch | `POST user.offer-builder.prompt` | `@prompt` | `{ok,prompt,missing}` |
| Generate with AI | save then generate then poll | `POST user.offer-builder.offers.store` → `POST .../offers/{id}/generate` → `GET .../generations/{gen}` | `BuilderOfferController` + `GenerateBuilderOffer` job | offer added to profile; messages fragment |
| Save without AI | fetch | `POST user.offer-builder.quick-save` | `@quickSave` | `{ok,message,id}` |

Field contract: inputs are sent as `inputs[<key>]` from `.ob-input[data-key]` (keys from `OfferPromptBuilderService::GLOBAL_FIELDS` + strategy fields).

### AI Outreach
`POST user.ai-outreach.estimate` → `POST user.ai-outreach.start` (analyze) → batch page polls `GET .../batches/{batch}/status`; drafts `PUT .../drafts/{draft}`, `approve`, `discard`, `restore`; `POST .../analyses/{analysis}/regenerate|retry`; `POST user.ai-outreach.folders.add`.

### Email sending, campaigns, analytics, follow-ups
Gmail: `POST user.email-sending.connect|disconnect`, `POST .../drafts/{draft}/send`, `POST .../emails/{email}/cancel|retry`, `PUT .../tracking-settings`, suppressions CRUD. Resend: connect/check/disconnect, domains (store/verify/refresh/tracking), senders, webhook, `PUT .../resend/lead-outreach`. Campaigns: store/update/test/approve/cancel, `POST user.campaigns.ai-write`, templates, lists, contacts import/withdraw, folder sends (create/cancel). Analytics: reply records `POST/DELETE .../emails/{email}/reply`. Follow-ups: store/complete/cancel.

### Subscription & payment
1. Monthly/Yearly toggle → client-side show/hide of two grids (no request).
2. Free plan → `POST user.subscription.apply-free`.
3. Paid plan → payment modal (`partials/payment-modal.blade.php`) → choose method → upload screenshot → `POST user.payment.submit` (multipart: `package_id`, `payment_method_id`, `screenshot`) → `SubscriptionController@submitPayment` → pending subscription → admin approves in `/admin/payments`.
4. Upgrade/cancel → `POST user.subscription.upgrade|cancel`.
5. Invoice modal → client-side render of billing history row.
No card checkout / Stripe UI exists.

### Global (layout)
Feedback modal → `POST user.feedback.store`; tutorials → `POST user.tutorials.complete|reset`, `user.welcome-tutorial.mark-seen`; extension modal (copy `chrome://extensions`); `GET extension.web-token` (auto-login for the extension); admin preview switch (`admin.switch.to.admin|user`); logout `POST auth.logout`.

### Chrome extension (separate app)
`/api/extension/login|logout|register-device|save-leads|status|devices` (Sanctum). Not part of the web UI redesign.

## 4. Role-based conditional rendering (must survive any redesign)

| Condition | Effect | Where |
|---|---|---|
| `auth()->user()->hasRestrictedAccess()` + pending subscription | sidebar shows only Dashboard + pending notice | `layouts/sidebar.blade.php:47-70` |
| `PlanFeatures::enabled($user, '<flag>')` | show/hide AI Sales Profile, AI Outreach, Email Sending (Gmail or Resend variant), Campaigns, Email Analytics, Follow-ups; locked filters in My Leads link to Subscription | sidebar, `user/leads.blade.php:227-260` |
| `isTeamMember()` | hide Subscription; read-only AI Sales Profile (`Gate manage-sales-profile`) | sidebar, AI profile |
| `isCompany()` | Team Members link, `<x-user-filter>` | sidebar, leads, search history |
| `session('admin_preview_user')` | "Switch back to Admin" | sidebar |
| `$canGenerate`, `$canAddService` | Generate button / "+ Add a new service" | offer builder |
| Subscription `end_date` ≤ 10 days | countdown banner | `layouts/app.blade.php` |

## 5. Never break (summary)
Form `action`/`method`/`@csrf`/`@method`; input `name` attributes; `id`s and classes used by scripts (`#searchForm`, `#paymentForm`, `#screenshotInput`, `.ob-*`, `.cnm-*`, `data-key`, `data-url`, `data-testid`); route names; JSON response shapes (`success` vs `ok`); polling endpoints; exports; plan/role conditionals; escaping helpers; flash-message keys `success|error|warning`; pagination `->links('pagination::tailwind')` with `appends(request()->query())`.
