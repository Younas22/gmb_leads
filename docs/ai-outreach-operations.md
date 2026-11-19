# AI Outreach — operations guide (queue, cron, monitoring)

Applies to Hostinger Web Hosting (shared, 1 CPU / 2 GB RAM) and similar hosts
without long-running processes. Nothing here sends email.

## 1. Required settings (`.env`)

```
QUEUE_CONNECTION=database        # other app jobs
AI_QUEUE_CONNECTION=ai_database  # AI jobs (default) — retry_after 210s
AI_QUEUE=ai
ANTHROPIC_API_KEY=...            # AI stays off while empty
```

Defaults that matter (config/ai.php, config/queue.php):

| Setting | Value | Why |
|---|---|---|
| AI call timeout (`AI_TIMEOUT`) | 75 s | one model call |
| SDK retries (`AI_MAX_RETRIES`) | 0 | retries happen at job level with backoff (30 s, 120 s) |
| Malformed-output retries | 1 | one corrected answer, same quota unit |
| AI job timeout (`AI_JOB_TIMEOUT`) | 170 s | > 2 calls × 75 s |
| `ai_database` retry_after (`AI_QUEUE_RETRY_AFTER`) | 210 s | must stay > job timeout |
| Job attempts | 3 | |
| Batch size / open items | 25 / 100 per account | |
| Website budget | 30 s, 3 pages, 1 MB per page | |

Check them any time with `php artisan ai:status`.

## 2. Cron — check the host first

Over SSH (hPanel → Advanced → SSH Access):

```
cd ~/domains/<your-domain>/public_html      # or wherever the app lives
which php && php -v                          # need PHP 8.2+ (the CLI version can differ from the web version)
php -r 'var_dump(function_exists("proc_open"));'   # needed by option A
which flock                                  # needed by option B
php artisan ai:status
```

### Option A (preferred) — Laravel scheduler

Works when `proc_open` is available. One cron entry (hPanel → Advanced → Cron Jobs → Custom, every minute):

```
* * * * * cd /home/<user>/domains/<your-domain>/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Replace `/usr/bin/php` with the path `which php` printed. The scheduler then runs:

- every minute: `queue:work ai_database --queue=ai --stop-when-empty --max-time=50 --sleep=3 --tries=3 --timeout=170 --memory=256`
  with `withoutOverlapping(10)` → at most one AI worker at a time;
- every 10 minutes: `ai:recover-stuck` (closes items whose worker was killed);
- daily 03:30: `ai:prune-audits` (retention);
- existing: `subscriptions:check-expired` at midnight.

### Option B — without the scheduler (if `proc_open` is disabled)

```
* * * * * cd /home/<user>/domains/<your-domain>/public_html && flock -n /tmp/cnm-ai-worker.lock /usr/bin/php artisan queue:work ai_database --queue=ai --stop-when-empty --max-time=50 --tries=3 --timeout=170 --memory=256 >> /dev/null 2>&1
*/10 * * * * cd /home/<user>/domains/<your-domain>/public_html && /usr/bin/php artisan ai:recover-stuck >> /dev/null 2>&1
30 3 * * * cd /home/<user>/domains/<your-domain>/public_html && /usr/bin/php artisan ai:prune-audits >> /dev/null 2>&1
0 0 * * * cd /home/<user>/domains/<your-domain>/public_html && /usr/bin/php artisan subscriptions:check-expired >> /dev/null 2>&1
```

`flock -n` keeps a single worker. Without `flock`, overlapping workers are still
safe (the database queue reserves each job), just heavier.

### No cron at all

AI jobs stay queued. For testing only, process them by hand:
`php artisan queue:work ai_database --queue=ai --stop-when-empty`.

## 3. Throughput and resources

- One worker, one job at a time: roughly 1 lead per 10–60 s with a real model
  (two model calls + a website check). 25 leads ≈ 5–20 minutes.
- Memory: the worker restarts above 256 MB; typical use is far lower.
- Without the `pcntl` extension (common on shared hosting) `--timeout` is not
  enforced by the worker; the HTTP timeouts (website 10 s, AI 75 s) still are,
  and `ai:recover-stuck` closes anything a killed process leaves behind.

## 4. Monitoring

- `php artisan ai:status` — configuration, backlog, pcntl.
- Failed jobs: `php artisan queue:failed`. Items are already marked failed in the
  UI; users retry from AI Outreach. `queue:retry` is not needed for AI jobs.
- Usage and cost: AI Outreach page, or `ai_usage_logs` (tokens per call, never prompts).

## 5. Before turning AI on

1. Set `ANTHROPIC_API_KEY`, then `php artisan ai:status --check-model` (free metadata call).
2. Run 3–5 of your own test leads and read every draft. Only then add
   `ai_audits_monthly` / `ai_email_drafts_monthly` to customer packages.
3. Re-check prices against https://platform.claude.com/docs/en/about-claude/pricing
   and update `config/ai.php` → `pricing` if they change.

## 6. Gmail sending (Phase 3)

Users send **approved** drafts one at a time from their own Gmail (OAuth scope `gmail.send` only — no read/modify). Nothing is ever sent automatically.

**Google Cloud setup**
1. APIs & Services → Library → enable **Gmail API**.
2. OAuth consent screen: add scopes `openid`, `email`, `https://www.googleapis.com/auth/gmail.send` (a *sensitive* scope → Google verification is needed before public launch; until then only listed *test users* can connect, max 100).
3. Credentials → the existing Web OAuth client (or a new one) → Authorized redirect URI: `{APP_URL}/user/email-sending/google/callback` (exact match).
4. `.env`: `GMAIL_CLIENT_ID` / `GMAIL_CLIENT_SECRET` (dedicated client; there is no fallback to the Google Login client), optional `GMAIL_REDIRECT_URI`, `GMAIL_SENDING_ENABLED=true`.

**Queue** — separate `mail_database` connection / `mail` queue (retry_after 120 s, job timeout 75 s, Google HTTP timeout 15 s, 3 tries). The scheduler runs a worker for it every minute and `email:recover-stuck` every 10 minutes. Cron line without the scheduler:
`* * * * * cd /path/to/app && php artisan queue:work mail_database --queue=mail --stop-when-empty --max-time=50 --tries=3 --timeout=75`

**Outcomes** — `sent` (Gmail message id stored), `failed` (Gmail clearly refused: user may retry), `failed` + *unknown outcome* (timeout / 5xx / worker killed mid-request: never retried automatically; user must check their Gmail "Sent" folder and confirm before retrying), `cancelled`.

**Limits** — plan feature `email_sends_daily` (default 20/user/day, `-1` unlimited, `0` off), `gmail_sending_enabled` (default on), 5 sends/min per Gmail account (`GMAIL_PER_MINUTE`), 10 send requests/min per user.

**Monitoring** — `email_audit_logs` (connect/disconnect/queued/sent/failed/unsubscribe), `email_send_attempts` (per HTTP attempt), `outbound_emails` where `outcome_unknown = 1`.

**Phase 3.1 notes**
- Packages must set `gmail_sending_enabled = 1` (default is OFF) and `email_sends_daily = N` (shared by the company and its team members; `unlimited` allowed).
- Use a **dedicated** OAuth client for Gmail; `php artisan gmail:status` checks config, timeouts and queue health without printing secrets.
- `schedule:run` starts commands one after another; the mail worker is scheduled before the AI worker. On Hostinger you may also give each worker its own cron line (see above) so they run in parallel.
- Keep `SESSION_SAME_SITE=lax` (strict drops the session on Google's redirect and every connect fails with "invalid state"). Rotating `APP_KEY` invalidates unsubscribe links in already-sent emails.

## 7. Email tracking, replies, follow-ups and analytics (Phase 4)

**Plan features** (all OFF by default; existing packages gain nothing until an admin adds them):
`email_tracking_enabled` (link-click tracking), `email_analytics_enabled` (Email Analytics page + manual reply recording), `email_followups_enabled` (follow-up reminders). Gmail sending permissions (`gmail_sending_enabled`, `email_sends_daily`) are unchanged.

**Link tracking** is opt-in per sender (Email Sending page -> Link tracking, with a disclosure the sender must accept). When on, web links in the email are replaced when the email is queued (before any Gmail call, in the same DB transaction) with `{APP_URL}/r/{token}` links that redirect to the stored original URL. Unsubscribe links, `mailto:` and unsafe URLs (non-http(s), private IPs, `user@host` tricks, localhost) are never changed. The approved draft is never edited; `outbound_emails.body` keeps the untracked text and `sent_body` is exactly what was sent (`mime_sha256` fingerprints the MIME accepted by Gmail).
- No IP address or user agent is stored. Each request is classified as probable person / likely scanner (HEAD, non-browser or scanner user agents, within 60 s of sending) / your own test (signed-in team member). Only probable-person requests count as estimated clicks.
- Click data is deleted after the sender's retention period (30/90/180 days, default 90) by `email:prune-tracking` (daily 03:45). Tracked links stop working after `EMAIL_TRACKING_LINK_DAYS` (default 365).
- **APP_URL must be the public https address** (production: `https://v2.customernearme.com`, no path, no trailing slash); `php artisan gmail:status` warns otherwise.
- Legal: link tracking may need disclosure to recipients (GDPR/ePrivacy, UK PECR). Have the wording reviewed before enabling it for EU/UK audiences.

**Replies** are recorded manually (Email Analytics -> email -> Record reply). No Gmail read scope is requested; automatic reply detection is not available. "Not interested" adds the address to the do-not-email list and cancels open follow-ups; correcting the reply lifts that block. Lead statuses in My Leads and Lead Center move forward only and never overwrite Converted/Closed.

**Follow-ups** are reminders only (nothing is sent). The earliest open one is mirrored to `saved_leads.follow_up_date` (source `email`); manual dates set elsewhere are not cleared. Unsubscribing or a "not interested" reply cancels them.

**Analytics** counts describe emails queued in the selected period. Reply rate = replies on accepted emails / accepted emails. Estimated click rate = tracked accepted emails with a probable-person click / tracked accepted emails. There is no open rate or delivery rate (no reliable data).

**Deploy**: `git pull`, `php artisan migrate --force` (6 new migrations `2026_11_17_*`), `php artisan optimize:clear`, `config:cache`, `route:cache`, `view:cache`, then `php artisan gmail:status`. No new queue worker or cron line is needed.
