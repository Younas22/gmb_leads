# Production security remediation runbook (Phase 0 / 0.1)

Steps that must be done **by a person with production access**. Nothing here is
automated by the code changes. Never paste secret values into tickets, chat or git.

Take a full database backup **and** a copy of `.env` before starting.

---

## 1. First: check whether internal files are publicly downloadable

The app ships a root `index.php`, which suggests the domain's document root is
the project folder, not `public/`. If so, files like `.env` and the SQL dump may
have been downloadable before this release.

From any machine (status codes only — do not save the bodies):

```
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/.env
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/db/gmbleads.sql
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/storage/logs/laravel.log
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/composer.json
```

* Expected after deploying this release: `403` or `404` for all of them.
* Best fix: in the hosting panel, point the document root at `/public`.
* **If any returned `200` before this release** (check old web-server access logs
  for requests to these paths), treat **every value in `.env` as leaked** and do
  the full rotation in section 2.

## 2. Credential rotation

Rotate in the provider's console, then update the server `.env` (or the admin
settings form for values stored in the database). Restrict each new key by API
and by HTTP referrer / IP where the provider allows it.

| Credential | Why | Where it is used |
|---|---|---|
| Google API key (PageSpeed) | Was hardcoded in source → in git history of both repos | `.env` `GOOGLE_PAGESPEED_API_KEY` |
| UniRate API key | Was hardcoded in source → in git history | `.env` `UNIRATE_API_KEY` |
| Search API keys in `admin_api_keys` | Were written to `laravel.log` in full ("Full URL with params") | Admin → API Usage |
| *If section 1 found exposure:* DB password, `RESEND_API_KEY`, Google OAuth client secret, AWS keys, Resend key in `settings` | `.env` / DB readable | `.env`, Admin → Settings |
| *If section 1 found exposure:* `APP_KEY` | Encrypts sessions, `user_api_keys`, `admin_api_keys`, secret settings | see below |

**Rotating `APP_KEY` safely:** generate a new key, move the old one to
`APP_PREVIOUS_KEYS=` (Laravel 11+ keeps decrypting old data with it), deploy,
then re-save encrypted values (or leave the previous key configured). Never
just replace `APP_KEY` — encrypted columns would become unreadable.

## 3. Readable passwords (`users.plain_password`)

Passwords were stored readable for (at least) every signup, password change,
team-member creation and Google signup, and were shown to admins and company
owners. Each signup also wrote the password to `laravel.log`.

1. After this release is deployed and stable:
   ```
   php artisan security:purge-plain-passwords          # dry run, prints a count only
   php artisan security:purge-plain-passwords --force  # clears the column; hashes untouched
   ```
2. Password reset decision:
   * **Recommended:** ask every user to change their password (email notice),
     because readable copies existed in the database, backups and logs, and
     people reuse passwords.
   * **Required (force a reset)** if section 1 or the access-log review shows
     the database, a backup or logs were downloadable.
3. Drop the column in a later, separate release (a migration), once no backup
   restore plan depends on it.

## 4. Log cleanup

Old log lines contain personal data and secrets. On the server:

* `storage/logs/laravel*.log` — lines with `Signup API hit` and
  `Validation failed` (names, emails, **passwords**), `Full URL with params` /
  `Request params` (search API key), `User update request received` (profile data).
* `storage/logs/laravel-YYYY-MM-DD.log` (daily channel) — `EXT_LOCATION_DEBUG`,
  `Extension save lead error` (scraped lead data).

Recommended: archive the current logs to an encrypted, access-controlled
location (if you must keep them for incident investigation), then delete them
from the server. Also check the hosting panel's log/backup retention.

## 5. Git history

These are in the history of **both** GitHub repositories (`gmb_leads` and
`customernearme_v2`), and in every clone:

* `db/gmbleads.sql` — database dump (users, leads, API keys).
* `public/assets/avatar/*` — user profile photos.
* The two hardcoded API keys (now removed from the code).

Steps (coordinate with everyone who has a clone; this rewrites history):

1. Make the repositories private if they are not already.
2. Rotate the keys first (section 2) — removing history does not un-leak them.
3. Rewrite history, e.g. with `git filter-repo`:
   `git filter-repo --path db/gmbleads.sql --path-glob 'public/assets/avatar/*' --invert-paths`
   and `--replace-text` with a file listing the old key values (kept off-repo).
4. Force-push all branches and tags; ask collaborators to re-clone; ask GitHub
   support to purge cached views if the repo was ever public.
5. Keep avatars out of git (add `public/assets/avatar/*` with an exception for
   the placeholder images to `.gitignore`).

## 6. Secret settings encryption

After deploying:

```
php artisan settings:encrypt-secrets          # dry run: lists keys (never values)
php artisan settings:encrypt-secrets --force  # encrypts legacy plaintext secrets
```

Rolling back to a release **older than Phase 0.1** after this step requires
`php artisan settings:encrypt-secrets --decrypt --force` first (older code does
not decrypt). Values saved through the admin form after the deploy are always
encrypted, so run `--decrypt --force` before any such rollback.
