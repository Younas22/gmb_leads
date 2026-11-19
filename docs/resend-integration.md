# Resend integration (Phase 5)

CustomerNearMe customers can connect **their own** Resend account ("bring your own API key") next to Gmail.
This is completely separate from the platform's own Resend account (`RESEND_API_KEY` in `.env`, used for
verification, password and billing emails). Customer keys never send platform mail, and the platform key is
never used for customer mail.

> **Resend is for opted-in recipients only.** Resend's Acceptable Use Policy forbids "cold outreach,
> purchased lists, or scraped contact data" and requires that "all mail must be sent to recipients who have
> explicitly opted in" (resend.com/legal/acceptable-use). Leads found on Google Maps are therefore never
> emailed through Resend. Use Gmail for one-to-one first contact. Resend also requires complaint rates below
> 0.08% and bounce rates below 4%.

## Gmail vs Resend

| | Gmail | Resend |
|---|---|---|
| Connection | OAuth (send-only scope) | API key (Full access), stored encrypted |
| Recipients | Any valid business address (one at a time) | Opted-in contacts with consent evidence only |
| Campaigns | No | Yes (Resend Broadcasts) |
| Delivery evidence | **Not available** (Gmail reports none) | delivered / delayed / bounced / complained / failed / suppressed via signed webhooks |
| Opens / clicks | Optional CustomerNearMe link tracking (estimates) | Resend's own per-domain open/click tracking (provider-reported) |
| Replies | Recorded manually | Recorded manually (inbound processing not enabled) |

## 1. Create a Resend account
Sign up at resend.com. Resend's own plan limits (daily/monthly quotas) apply in addition to your
CustomerNearMe plan limits.

## 2. Generate the API key
Resend dashboard → **API Keys** → **Create API Key** → permission **Full access**. A "Sending access" key is
refused, because domains, webhooks, contacts and broadcasts cannot be managed with it.

## 3. Connect Resend
CustomerNearMe → **Email Sending → Resend** → paste the key → **Connect Resend** (account owner only).
The key is checked with `GET /domains`, stored with Laravel encryption (APP_KEY) and shown only as
`re_••••••••abcd`. **Check connection** re-validates it; a revoked key is marked *invalid* and sending stops.
**Replace key** rotates it; if the new key belongs to a different Resend account, domains, senders and the
webhook are reset and must be set up again. **Disconnect** deletes the key (blocked while a campaign is
scheduled or preparing).

## 4. Add a domain
Email Sending → Resend → **Add domain** (a subdomain such as `mail.yourcompany.com` is recommended) and a
region. CustomerNearMe calls `POST /domains` and shows the records Resend returns.

## 5. Configure DNS
Copy each record (type, name, value, priority) exactly as shown into your DNS provider, e.g. Hostinger →
Domains → DNS / Nameservers. CustomerNearMe never edits DNS and never invents values; records are copied
from the Resend API.

## 6. Verify the domain
Click **Verify** (calls `POST /domains/{id}/verify`, then reads the result). DNS can take time; use
**Refresh** later. Status mapping: `not_started` → Pending, `pending` → Verifying, `verified` → Verified,
`partially_verified` / `partially_failed` / `temporary_failure` → Needs attention, `failed` → Failed.
Only **Verified** domains can be used, and the status is checked again before every campaign.
Then add a **sender identity** (name + address on the verified domain, optional reply-to).

## 7. Set up the webhook
Either **Set up automatically** (with confirmation; calls `POST /webhooks` and stores the returned signing
secret encrypted) or manually: Resend → Webhooks → Add Webhook, endpoint shown on the page
(`{APP_URL}/webhooks/resend/{token}`), events `email.sent, email.delivered, email.delivery_delayed,
email.bounced, email.complained, email.failed, email.suppressed, email.opened, email.clicked,
contact.updated, domain.updated`, then paste the `whsec_...` secret.
Every request is verified with the Svix scheme (HMAC-SHA256 over `svix-id.svix-timestamp.raw-body`,
5-minute timestamp tolerance); unsigned, stale or replayed requests are rejected. The token in the URL only
selects the configuration; data from one customer's webhook can never change another customer's emails.
The page shows last event, verified/rejected counts and recent events.

## 7a. Optional: one-to-one Resend emails to saved leads
Off by default. In Email Sending → Resend the account owner can switch on **"One-to-one Resend emails to saved
leads"** after accepting a risk notice (recorded with time and user; audit log `resend_lead_outreach_enabled`).
When on, an approved AI draft for a saved lead can be sent through Resend to **that lead's own stored address only**
(a typed-in address is refused), one email at a time, with its own confirmation ("this person has not opted in").
No consent record is created. The postal address, unsubscribe link (`List-Unsubscribe`), do-not-email list and
Resend daily/monthly limits all still apply, and the check is repeated right before the API call: switching the
setting off stops emails that are still queued. **Campaigns are not affected** and always go to opted-in contacts.
Resend's Acceptable Use Policy does not allow cold outreach; complaints or bounces can get the Resend account
limited, so Gmail remains the recommended route for first contact.

## 8. Send a controlled individual test
1. Add yourself (or a colleague who agreed) as a contact with consent evidence.
2. Approve an AI draft for a lead that has that email address.
3. On the draft page choose **Review & send via Resend**, select the sender, tick both confirmations.
The email is queued (`resend` queue), sent with an `Idempotency-Key`, a `List-Unsubscribe` header and your
postal address + unsubscribe link. Status and delivery appear in Email Sending history and Email Analytics.

## 9. Import opted-in contacts
Campaigns → **Contacts & lists** → Import CSV. Header row:
`email, first_name, last_name, organization, consent_source, consent_date, consent_evidence, tags`.
`consent_source` must be one of `signup_form, existing_customer, event_signup, written_request, double_opt_in, other`;
`consent_evidence` (at least 10 characters) describes where and how the person opted in. Rows without
evidence are rejected. Addresses that unsubscribed, bounced, complained or are on the do-not-email list are
**never** re-activated by importing. Saved leads are never converted to contacts.

## 10. Create a campaign
Campaigns → **Create campaign** → sender, list and optional filters (tag, organization, consent date),
subject and plain-text message with `{{first_name|there}}` / `{{last_name}}`. Your sender identity, postal
address and Resend's unsubscribe link are added automatically. On the campaign page: preview, eligible
count (suppressed contacts excluded), **Send test** (to your own login address only, max 5), then approve
with two confirmations, **now** or **scheduled** (10 minutes to 30 days ahead).
After approval CustomerNearMe creates a Resend Segment for the campaign, adds each still-eligible contact
(re-checked one by one), then creates **one** Broadcast with `send: true` (and `scheduled_at` if scheduled).
Scheduled campaigns can be cancelled (deletes the broadcast at Resend).
**Opt-outs after approval:** if anyone in an approved campaign unsubscribes, bounces, complains, withdraws
consent or is added to the do-not-email list before it is sent, CustomerNearMe removes them from the segment
while the campaign is still *Preparing*; once it is *Scheduled* the whole broadcast is deleted at Resend and the
campaign shows "Automatically cancelled" (Resend documents that deleting a scheduled broadcast cancels it, but
not whether later segment changes reach it). Create a new campaign for the remaining contacts. If Resend refuses
the cancellation, the campaign shows *Unknown - check Resend*: delete it in the Resend dashboard if still scheduled.
`resend:maintain` re-checks scheduled campaigns every 5 minutes as a safety net. If the broadcast request has an
unclear result the campaign shows **Unknown - check Resend** and is never re-created automatically.

## 11. Interpreting tracking
- *Accepted by Resend* = Resend accepted the request; not delivery.
- *Delivered / Bounced / Complaints* = verified Resend webhooks only.
- *Opens / Clicks reported* = Resend's own tracking (enable per domain in Email Sending → Resend, needs the
  `email_tracking_enabled` plan feature). They can include automatic image loading and security scanners.
- CustomerNearMe redirect link tracking is used for Gmail only, so links are never wrapped twice and the two
  kinds of clicks are never added together. Unsubscribe links are never counted.
- Gmail has no delivery data: shown as "Not available", never as 0.

## 12. Unsubscribes and suppression
One shared do-not-email list (`email_suppressions`) covers Gmail and Resend: unsubscribe links, Resend
contact unsubscribes (`contact.updated`), hard bounces, complaints, Resend suppressions, manual entries and
"not interested" replies. It is checked when queuing **and** again right before each API call.
Correcting a mistaken "not interested" reply removes only the entry that reply created; if the person has
since unsubscribed, bounced or complained, the stronger reason replaces it and is never removed.

## 13. Troubleshooting
- *"restricted to only send emails"*: create a Full access key.
- *Key invalid*: it was deleted or revoked in Resend; connect a new key.
- *Domain not verified*: check the DNS records at your DNS host; click Verify/Refresh.
- *Webhook "Signature failures"*: the signing secret does not match; set up the webhook again.
- *No delivery events*: webhook not set up, or `schedule:run` / the mail worker is not running (events are
  processed on the `webhook` queue).
- *Campaign stuck in Preparing*: the queue worker is not running; check the cron line below.

## 14. Inbound replies
Resend supports receiving email (`email.received` webhook, MX records on a receiving domain). This is **not
enabled** in Phase 5: it needs a separate receiving domain/MX setup, consent to process incoming messages,
and message correlation that has not been tested end to end. `email.received` webhooks are ignored. Replies
are recorded manually in Email Analytics (both providers).

## 15. Deploying Phase 4 + Phase 5
1. Back up the database.
2. Deploy the branch, then `php artisan migrate --force` (Phase 4: 6 migrations `2026_11_17_*`;
   Phase 5: 6 migrations `2026_11_18_*`). All are additive; existing emails become `provider = gmail`.
3. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`.
4. `APP_URL` must be the public https address (production: `https://v2.customernearme.com`, no path, no trailing slash) because webhook URLs
   and unsubscribe links are built from it. `php artisan gmail:status` warns when it is not.
5. Cron (unchanged): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
   The existing mail worker now drains `mail,webhook,resend` (no extra worker); `resend:maintain` runs every
   5 minutes and `email:prune-tracking` daily. Without the scheduler, use:
   `* * * * * cd /path/to/app && php artisan queue:work mail_database --queue=mail,webhook,resend --stop-when-empty --max-time=50 --tries=3 --timeout=75`
6. Enable features per package in Admin → Packages: `resend_connection_enabled`,
   `resend_single_sending_enabled`, `resend_campaigns_enabled`, and limits `resend_emails_daily`,
   `resend_emails_monthly`, `resend_contacts_limit`, `resend_campaigns_monthly` (all default OFF / 0).
