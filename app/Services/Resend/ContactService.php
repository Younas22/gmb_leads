<?php

namespace App\Services\Resend;

use App\Models\EmailSuppression;
use App\Models\MarketingConsentLog;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Models\User;
use App\Services\Gmail\OutboundEmailService;
use App\Services\PlanFeatures;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opted-in contacts. Every contact needs a consent source, a consent date and
 * written evidence. Suppressed / unsubscribed / bounced / complained addresses are
 * never (re)activated by adding or importing them again.
 */
class ContactService
{
    public const CSV_COLUMNS = ['email', 'first_name', 'last_name', 'organization', 'consent_source', 'consent_date', 'consent_evidence', 'tags'];

    public function limit(User $user): int
    {
        return PlanFeatures::limit($user, 'resend_contacts_limit');
    }

    public function count(User $owner): int
    {
        return MarketingContact::ownedBy($owner)->count();
    }

    /**
     * Validate one contact row. Returns [clean data, problem].
     *
     * @return array{0: ?array, 1: ?string}
     */
    public function validateRow(array $row): array
    {
        $email = EmailSuppression::normalize((string) ($row['email'] ?? ''));
        if (!OutboundEmailService::validRecipient($email)) {
            return [null, 'invalid email'];
        }
        $source = trim((string) ($row['consent_source'] ?? ''));
        if (!array_key_exists($source, (array) config('resend_user.consent_sources'))) {
            return [null, 'missing or unknown consent_source'];
        }
        $evidence = trim((string) ($row['consent_evidence'] ?? ''));
        if (mb_strlen($evidence) < 10) {
            return [null, 'consent_evidence missing (describe where and how they opted in)'];
        }
        try {
            $date = Carbon::parse((string) ($row['consent_date'] ?? ''));
        } catch (\Throwable) {
            return [null, 'invalid consent_date'];
        }
        if (trim((string) ($row['consent_date'] ?? '')) === '' || $date->isFuture()) {
            return [null, 'consent_date missing or in the future'];
        }

        $tags = collect(is_array($row['tags'] ?? null) ? $row['tags'] : preg_split('/[;|]/', (string) ($row['tags'] ?? '')))
            ->map(fn ($t) => mb_substr(mb_strtolower(trim((string) $t)), 0, 40))->filter()->unique()->take(10)->values()->all();

        return [[
            'email' => $email,
            'first_name' => mb_substr(trim((string) ($row['first_name'] ?? '')), 0, 100) ?: null,
            'last_name' => mb_substr(trim((string) ($row['last_name'] ?? '')), 0, 100) ?: null,
            'organization' => mb_substr(trim((string) ($row['organization'] ?? '')), 0, 150) ?: null,
            'consent_source' => $source,
            'consent_at' => $date,
            'consent_evidence' => mb_substr($evidence, 0, 2000),
            'consent_purpose' => mb_substr(trim((string) ($row['consent_purpose'] ?? '')), 0, 150) ?: null,
            'tags' => $tags ?: null,
        ], null];
    }

    /**
     * Add or update one contact. Never re-subscribes a blocked address.
     *
     * @return array{contact: ?MarketingContact, result: string} result: created|updated|blocked|limit
     */
    public function upsert(User $user, array $clean, string $source, ?MarketingContactList $list = null): array
    {
        $owner = $user->getAccountOwner();
        $hash = MarketingContact::hashFor($clean['email']);
        $existing = MarketingContact::ownedBy($owner)->where('email_hash', $hash)->first();
        $suppressed = EmailSuppression::isSuppressed($owner->id, $clean['email']);

        if ($existing && ($existing->unsubscribed_at || $existing->bounced_at || $existing->complained_at || $existing->consent_status === MarketingContact::WITHDRAWN)) {
            return ['contact' => $existing, 'result' => 'blocked'];
        }
        if (!$existing && $suppressed) {
            return ['contact' => null, 'result' => 'blocked'];
        }
        if (!$existing) {
            $limit = $this->limit($user);
            if ($limit !== -1 && $this->count($owner) >= $limit) {
                return ['contact' => null, 'result' => 'limit'];
            }
        }

        $contact = DB::transaction(function () use ($user, $owner, $existing, $clean, $hash, $source) {
            $contact = $existing ?? new MarketingContact(['owner_user_id' => $owner->id, 'email_hash' => $hash, 'source' => $source]);
            $before = $existing?->consent_status;
            $contact->fill(array_merge($clean, [
                'consent_status' => MarketingContact::OPTED_IN,
                'consent_recorded_by_user_id' => $user->id,
                'tags' => $clean['tags'] ?? $contact->tags,
            ]))->save();
            MarketingConsentLog::record($contact, $user->id, $existing ? 'consent_updated' : 'consent_recorded', $before, MarketingContact::OPTED_IN,
                ['source' => $clean['consent_source'], 'via' => $source]);

            return $contact;
        });

        if ($list) {
            $list->contacts()->syncWithoutDetaching([$contact->id => ['created_at' => now()]]);
        }

        return ['contact' => $contact, 'result' => $existing ? 'updated' : 'created'];
    }

    /**
     * Import a CSV with header row (see CSV_COLUMNS). Bounded size; rows without
     * consent evidence are rejected and reported, never imported.
     *
     * @return array{created: int, updated: int, blocked: int, rejected: int, limit: int, errors: list<string>}
     */
    public function import(User $user, string $path, ?MarketingContactList $list = null): array
    {
        $report = ['created' => 0, 'updated' => 0, 'blocked' => 0, 'rejected' => 0, 'limit' => 0, 'errors' => []];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $report['errors'][] = 'The file could not be read.';
            return $report;
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header ?: []);
        if (!in_array('email', $header, true) || !in_array('consent_evidence', $header, true)) {
            fclose($handle);
            $report['errors'][] = 'The first row must contain column names including email, consent_source, consent_date and consent_evidence.';
            return $report;
        }

        $max = (int) config('resend_user.import_max_rows', 5000);
        $line = 1;
        $seen = [];
        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if ($line - 1 > $max) {
                $report['errors'][] = "Only the first {$max} rows were processed.";
                break;
            }
            if ($cells === [null] || $cells === []) {
                continue;
            }
            $row = array_combine(array_slice($header, 0, count($cells)), array_slice($cells, 0, count($header))) ?: [];
            [$clean, $problem] = $this->validateRow($row);
            if ($problem) {
                $report['rejected']++;
                if (count($report['errors']) < 50) {
                    $report['errors'][] = "Row {$line}: {$problem}";
                }
                continue;
            }
            if (isset($seen[$clean['email']])) {
                $report['rejected']++;
                continue; // duplicate within the file
            }
            $seen[$clean['email']] = true;

            $result = $this->upsert($user, $clean, 'csv', $list)['result'];
            $report[$result]++;
        }
        fclose($handle);

        return $report;
    }

    /** Stop emailing a contact (their request, or they told you so). Permanent for sending. */
    public function withdraw(User $user, MarketingContact $contact, string $note = ''): void
    {
        $before = $contact->consent_status;
        $contact->forceFill(['consent_status' => MarketingContact::WITHDRAWN, 'unsubscribed_at' => $contact->unsubscribed_at ?? now()])->save();
        EmailSuppression::suppress($contact->owner_user_id, $contact->email, EmailSuppression::REASON_UNSUBSCRIBED, null, $user->id);
        MarketingConsentLog::record($contact, $user->id, 'consent_withdrawn', $before, MarketingContact::WITHDRAWN, array_filter(['note' => mb_substr($note, 0, 200)]));
    }
}
