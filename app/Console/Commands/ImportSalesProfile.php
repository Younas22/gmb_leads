<?php

namespace App\Console\Commands;

use App\Http\Requests\AiSalesProfile\SaveUserOfferRequest;
use App\Http\Requests\AiSalesProfile\UpdateSalesProfileRequest;
use App\Models\User;
use App\Models\UserOffer;
use App\Models\UserSalesProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Imports an AI Sales Profile (profile + skills + services + offers) from a JSON file
 * for ONE account owner chosen by exact email. Dry run by default (preview only).
 *
 *   php artisan sales-profile:import database/data/sales-profiles/limoschedule.json --owner=me@example.com
 *   php artisan sales-profile:import ... --owner=me@example.com --apply
 *
 * Safety:
 *  - same validation rules as the AI Sales Profile screens;
 *  - only an account that may manage its profile (not an admin, not a team member);
 *  - an existing, filled-in profile is never overwritten without --replace-profile;
 *  - skills/services/offers are matched by type + title (case-insensitive): new ones are
 *    added, changed ones updated, nothing is ever deleted; other accounts are never touched;
 *  - everything is written in one transaction. Re-running changes nothing.
 */
class ImportSalesProfile extends Command
{
    protected $signature = 'sales-profile:import {file : JSON file} {--owner= : exact email of the account owner}
        {--apply : write the changes (default: preview only)} {--replace-profile : overwrite an existing filled-in profile}';

    protected $description = 'Preview or import an AI Sales Profile with skills, services and offers for one account';

    private const PROFILE_FIELDS = ['company_name', 'description', 'website', 'portfolio_urls', 'target_industries',
        'target_locations', 'language', 'tone', 'signature', 'sender_name', 'sender_postal_address', 'ai_instructions'];

    public function handle(): int
    {
        $data = $this->load((string) $this->argument('file'));
        if ($data === null) {
            return self::FAILURE;
        }
        $owner = $this->owner((string) $this->option('owner'));
        if (!$owner) {
            return self::FAILURE;
        }

        // ── Validate exactly like the UI ─────────────────────────────────
        $profileData = array_intersect_key($data['profile'] ?? [], array_flip(self::PROFILE_FIELDS));
        $v = Validator::make($profileData, (new UpdateSalesProfileRequest())->rules());
        $items = $this->items($data);
        $errors = $v->fails() ? $v->errors()->all() : [];
        foreach ($items as $i => $item) {
            $iv = Validator::make($item, (new SaveUserOfferRequest())->rules());
            foreach ($iv->fails() ? $iv->errors()->all() : [] as $e) {
                $errors[] = "{$item['type']} #" . ($i + 1) . " ({$item['title']}): {$e}";
            }
        }
        foreach (array_count_values(array_map(fn ($t) => $t['type'], $items)) as $type => $n) {
            $max = (int) config('ai.max_offers_per_type', 50);
            if ($n > $max) {
                $errors[] = "Too many {$type} items ({$n} > {$max}).";
            }
        }
        if ($errors) {
            $this->error('The file does not pass the AI Sales Profile validation:');
            foreach ($errors as $e) {
                $this->line('  - ' . $e);
            }
            return self::FAILURE;
        }

        // ── Plan ────────────────────────────────────────────────────────
        $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();
        $profileChanges = [];
        foreach ($profileData as $field => $new) {
            $old = $profile?->{$field};
            if ($this->norm($old) !== $this->norm($new)) {
                $profileChanges[$field] = [$old, $new];
            }
        }
        $filledBefore = $profile && collect(self::PROFILE_FIELDS)->contains(fn ($f) => filled($profile->{$f}) && !in_array($f, ['language', 'tone'], true));
        if ($filledBefore && $profileChanges && !$this->option('replace-profile')) {
            $this->warn('This account already has a filled-in AI Sales Profile. These fields would change:');
            foreach (array_keys($profileChanges) as $f) {
                $this->line('  - ' . $f);
            }
            $this->error('Nothing was written. Re-run with --replace-profile to overwrite them.');
            return self::FAILURE;
        }

        $existing = UserOffer::ownedBy($owner)->get();
        $plan = [];
        foreach ($items as $item) {
            $match = $existing->first(fn (UserOffer $o) => $o->type === $item['type'] && mb_strtolower(trim($o->title)) === mb_strtolower(trim($item['title'])));
            $diff = $match ? array_keys(array_filter(['description', 'price_hint', 'ideal_for', 'is_active'], fn ($f) => $this->norm($match->{$f}) !== $this->norm($item[$f]))) : [];
            $plan[] = ['item' => $item, 'match' => $match, 'action' => !$match ? 'create' : ($diff ? 'update' : 'unchanged'), 'diff' => $diff];
        }

        $this->preview($owner, $profile, $profileData, $profileChanges, $plan, $data);

        if (!$this->option('apply')) {
            $this->info('DRY RUN: nothing was written. Re-run with --apply to import.');
            return self::SUCCESS;
        }

        // ── Apply (one transaction) ─────────────────────────────────────
        DB::transaction(function () use ($owner, $profileData, $plan) {
            $profile = UserSalesProfile::forOwner($owner);
            $profile->fill($profileData);
            $profile->owner_user_id = $owner->id;
            $profile->updated_by_user_id = $owner->id;
            $profile->save();

            $nextSort = [];
            foreach ($plan as $p) {
                $type = $p['item']['type'];
                $nextSort[$type] ??= (int) UserOffer::ownedBy($owner)->where('type', $type)->max('sort_order');
                if ($p['action'] === 'create') {
                    $offer = new UserOffer(array_intersect_key($p['item'], array_flip(['type', 'title', 'description', 'price_hint', 'ideal_for', 'is_active'])));
                    $offer->owner_user_id = $owner->id;
                    $offer->sort_order = ++$nextSort[$type];
                    $offer->save();
                } elseif ($p['action'] === 'update') {
                    $p['match']->fill(array_intersect_key($p['item'], array_flip(['description', 'price_hint', 'ideal_for', 'is_active'])))->save();
                }
            }
        });

        $activeCount = UserOffer::ownedBy($owner)->active()->count();
        $this->info('Imported. Profile completeness: ' . UserSalesProfile::where('owner_user_id', $owner->id)->first()->completeness($activeCount) . '%');

        return self::SUCCESS;
    }

    private function load(string $path): ?array
    {
        $full = is_file($path) ? $path : base_path($path);
        if (!is_file($full)) {
            $this->error("File not found: {$path}");
            return null;
        }
        $data = json_decode((string) file_get_contents($full), true);
        if (!is_array($data) || !isset($data['profile']) || !is_array($data['profile'])) {
            $this->error('The file is not valid JSON with a "profile" object.');
            return null;
        }

        return $data;
    }

    private function owner(string $email): ?User
    {
        if ($email === '') {
            $this->error('Choose the account with --owner=EMAIL (exact email). No account is ever picked automatically.');
            return null;
        }
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
        if (!$user) {
            $this->error('No account with that email.');
            return null;
        }
        if ($user->isAdmin() || $user->isTeamMember()) {
            $this->error('This account cannot own an AI Sales Profile (admins and team members cannot; use the account owner).');
            return null;
        }

        return $user;
    }

    /** @return list<array{type: string, title: string, description: ?string, price_hint: ?string, ideal_for: ?string, is_active: bool}> */
    private function items(array $data): array
    {
        $out = [];
        foreach (['skills' => UserOffer::TYPE_SKILL, 'services' => UserOffer::TYPE_SERVICE, 'offers' => UserOffer::TYPE_OFFER] as $key => $type) {
            foreach ((array) ($data[$key] ?? []) as $row) {
                $row = is_string($row) ? ['title' => $row] : (array) $row;
                $out[] = [
                    'type' => $type,
                    'title' => trim((string) ($row['title'] ?? '')),
                    'description' => $row['description'] ?? null,
                    'price_hint' => $row['price_hint'] ?? null,
                    'ideal_for' => $row['ideal_for'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
            }
        }

        return $out;
    }

    private function norm(mixed $v): string
    {
        if (is_array($v)) {
            return json_encode(array_values($v));
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }

        return trim((string) ($v ?? ''));
    }

    private function preview(User $owner, ?UserSalesProfile $profile, array $profileData, array $changes, array $plan, array $data): void
    {
        $this->newLine();
        $this->line('<options=bold>Target account</>');
        $sub = $owner->activeSubscription();
        $this->table(['id', 'name', 'email', 'type', 'status', 'active plan'], [[
            $owner->id, $owner->name, $owner->email, $owner->user_type, $owner->status, $sub?->package?->name ?? 'none',
        ]]);

        $this->line('<options=bold>Profile</> (' . ($profile ? 'exists — ' . count($changes) . ' field(s) change' : 'will be created') . ')');
        $rows = [];
        foreach ($profileData as $field => $value) {
            $shown = is_array($value) ? count($value) . ' item(s): ' . implode(' | ', array_slice($value, 0, 4)) . (count($value) > 4 ? ' …' : '') : (string) ($value ?? '(empty)');
            $rows[] = [$field, isset($changes[$field]) ? 'set' : 'same', mb_strimwidth(str_replace("\n", ' ⏎ ', $shown), 0, 110, '…'), is_array($value) ? '' : mb_strlen((string) $value)];
        }
        $this->table(['field', 'action', 'value', 'chars'], $rows);

        foreach ([UserOffer::TYPE_SKILL => 'Skills', UserOffer::TYPE_SERVICE => 'Services', UserOffer::TYPE_OFFER => 'Offers'] as $type => $label) {
            $typed = array_values(array_filter($plan, fn ($p) => $p['item']['type'] === $type));
            $this->line("<options=bold>{$label}</> (" . count($typed) . ')');
            $this->table(['#', 'action', 'title', 'price hint', 'ideal for', 'description'], array_map(fn ($p, $i) => [
                $i + 1, $p['action'] . ($p['diff'] ? ' (' . implode(',', $p['diff']) . ')' : ''), $p['item']['title'],
                $p['item']['price_hint'] ?? '', mb_strimwidth((string) ($p['item']['ideal_for'] ?? ''), 0, 45, '…'),
                mb_strimwidth(str_replace("\n", ' ', (string) ($p['item']['description'] ?? '')), 0, 50, '…'),
            ], $typed, array_keys($typed)));
        }

        $counts = array_count_values(array_column($plan, 'action'));
        $this->line(sprintf('Summary: profile %s; items: %d create, %d update, %d unchanged; other accounts: not touched.',
            $profile ? (count($changes) ? 'update' : 'unchanged') : 'create', $counts['create'] ?? 0, $counts['update'] ?? 0, $counts['unchanged'] ?? 0));
        if (blank($profileData['sender_postal_address'] ?? null)) {
            $this->warn('Postal address is empty: profile will be 89% complete and Gmail/Resend sending stays blocked until a real address is added.');
        }
    }
}
