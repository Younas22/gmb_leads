<?php

namespace App\Services;

use App\Models\LeadCenterLead;
use App\Models\SavedLead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared parsing / validation / duplicate-detection logic for getting leads into
 * Lead Center — from an uploaded CSV, pasted text, or existing "My Leads" records.
 */
class LeadCenterImportService
{
    /** Hard ceiling so a single import can't exhaust memory/time. */
    const MAX_ROWS = 20000;

    /**
     * Parse raw CSV/pasted text ("Company Name,Website" per line, header optional)
     * into a flat list of ['company_name' => ..., 'website' => ...] rows.
     * Blank lines are skipped. Does not validate or persist.
     */
    public function parseRawText(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = explode("\n", $raw);

        $rows = [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cols = str_getcsv($line);
            $company = trim($cols[0] ?? '');
            $website = trim($cols[1] ?? '');

            // Skip an obvious header row ("Company Name,Website" / "Business Name,URL" ...)
            if ($i === 0 && $this->looksLikeHeader($company, $website)) {
                continue;
            }

            $rows[] = ['company_name' => $company, 'website' => $website];

            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }

        return $rows;
    }

    private function looksLikeHeader(string $company, string $website): bool
    {
        $c = strtolower($company);
        $w = strtolower($website);

        return (str_contains($c, 'company') || str_contains($c, 'business') || str_contains($c, 'name'))
            && (str_contains($w, 'website') || str_contains($w, 'url') || $w === '');
    }

    /**
     * Validate + classify a list of raw rows against what's already in the user's
     * Lead Center. Does NOT persist anything — used to build the import preview.
     *
     * @return array{valid: array, invalid: array, duplicates: array, total: int}
     */
    public function analyze(int $ownerUserId, array $rows): array
    {
        $valid = [];
        $invalid = [];
        $duplicateRows = [];
        $seenKeys = [];

        foreach ($rows as $index => $row) {
            $company = trim((string) ($row['company_name'] ?? ''));
            $website = trim((string) ($row['website'] ?? ''));

            if ($company === '') {
                $invalid[] = ['row' => $index + 1, 'company_name' => $company, 'website' => $website, 'reason' => 'Missing company name'];
                continue;
            }

            if ($website !== '' && !$this->isValidWebsite($website)) {
                $invalid[] = ['row' => $index + 1, 'company_name' => $company, 'website' => $website, 'reason' => 'Invalid website format'];
                continue;
            }

            $website = $website !== '' ? $this->normalizeWebsite($website) : null;
            $key = LeadCenterLead::buildDedupeKey($company, $website);

            if (isset($seenKeys[$key])) {
                $duplicateRows[] = ['row' => $index + 1, 'company_name' => $company, 'website' => $website, 'reason' => 'Duplicate within this import'];
                continue;
            }

            if ($this->existsForUser($ownerUserId, $key)) {
                $duplicateRows[] = ['row' => $index + 1, 'company_name' => $company, 'website' => $website, 'reason' => 'Already in Lead Center'];
                continue;
            }

            $seenKeys[$key] = true;
            $valid[] = ['company_name' => $company, 'website' => $website, 'dedupe_key' => $key];
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'duplicates' => $duplicateRows,
            'total' => count($rows),
        ];
    }

    /**
     * Persist previously-analyzed valid rows. Re-validates and re-checks duplicates
     * server-side (defense in depth — never trust client-held state blindly) before
     * insert, using a single bulk insert for efficiency.
     *
     * @return array{imported: int, skipped_duplicate: int, failed: int}
     */
    public function import(int $ownerUserId, array $rows, ?int $countryId, ?int $stateId, ?int $cityId): array
    {
        $imported = 0;
        $skippedDuplicate = 0;
        $failed = 0;
        $now = now();
        $batch = [];
        $seenKeys = [];

        foreach ($rows as $row) {
            $company = trim((string) ($row['company_name'] ?? ''));
            $website = trim((string) ($row['website'] ?? ''));

            if ($company === '') {
                $failed++;
                continue;
            }

            if ($website !== '' && !$this->isValidWebsite($website)) {
                $failed++;
                continue;
            }

            $website = $website !== '' ? $this->normalizeWebsite($website) : null;
            $key = LeadCenterLead::buildDedupeKey($company, $website);

            if (isset($seenKeys[$key]) || $this->existsForUser($ownerUserId, $key)) {
                $skippedDuplicate++;
                continue;
            }

            $seenKeys[$key] = true;
            $batch[] = [
                'user_id' => $ownerUserId,
                'saved_lead_id' => null,
                'folder_id' => null,
                'company_name' => $company,
                'website' => $website,
                'country_id' => $countryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'status' => LeadCenterLead::STATUS_PENDING,
                'dedupe_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $imported++;
        }

        if (!empty($batch)) {
            // Chunk the bulk insert to stay well under any packet-size limits on very large imports.
            foreach (array_chunk($batch, 500) as $chunk) {
                DB::table('lead_center_leads')->insert($chunk);
            }
        }

        return ['imported' => $imported, 'skipped_duplicate' => $skippedDuplicate, 'failed' => $failed];
    }

    /**
     * Copy/link existing "My Leads" (SavedLead) records into Lead Center, preserving
     * company name, website and location. Skips ones already present.
     *
     * @return array{imported: int, skipped_duplicate: int}
     */
    public function importFromSavedLeads(int $ownerUserId, Collection $savedLeads): array
    {
        $imported = 0;
        $skippedDuplicate = 0;
        $now = now();
        $batch = [];
        $seenKeys = [];

        foreach ($savedLeads as $lead) {
            $company = trim((string) $lead->name);
            $website = $lead->website ? $this->normalizeWebsite(trim($lead->website)) : null;
            $key = LeadCenterLead::buildDedupeKey($company, $website);

            if (isset($seenKeys[$key]) || $this->existsForUser($ownerUserId, $key)) {
                $skippedDuplicate++;
                continue;
            }

            $seenKeys[$key] = true;
            $batch[] = [
                'user_id' => $ownerUserId,
                'saved_lead_id' => $lead->id,
                'folder_id' => null,
                'company_name' => $company,
                'website' => $website,
                'country_id' => is_numeric($lead->country) ? (int) $lead->country : null,
                'state_id' => is_numeric($lead->state) ? (int) $lead->state : null,
                'city_id' => is_numeric($lead->city) ? (int) $lead->city : null,
                'status' => LeadCenterLead::STATUS_PENDING,
                'dedupe_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $imported++;
        }

        if (!empty($batch)) {
            foreach (array_chunk($batch, 500) as $chunk) {
                DB::table('lead_center_leads')->insert($chunk);
            }
        }

        return ['imported' => $imported, 'skipped_duplicate' => $skippedDuplicate];
    }

    private function existsForUser(int $ownerUserId, string $dedupeKey): bool
    {
        return LeadCenterLead::where('user_id', $ownerUserId)->where('dedupe_key', $dedupeKey)->exists();
    }

    private function isValidWebsite(string $website): bool
    {
        if (str_contains($website, ' ')) {
            return false;
        }

        $candidate = str_contains($website, '://') ? $website : 'https://' . $website;

        if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Must look like a real host (has at least one dot) — rejects things like "https://abc"
        $host = parse_url($candidate, PHP_URL_HOST) ?: '';

        return str_contains($host, '.');
    }

    private function normalizeWebsite(string $website): string
    {
        return str_contains($website, '://') ? $website : 'https://' . $website;
    }
}
