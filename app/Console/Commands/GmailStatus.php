<?php

namespace App\Console\Commands;

use App\Models\ConnectedEmailAccount;
use App\Models\OutboundEmail;
use App\Services\Gmail\GmailOAuth;
use Illuminate\Console\Command;

/** Read-only Gmail sending health check. Never prints secrets or tokens. */
class GmailStatus extends Command
{
    protected $signature = 'gmail:status';

    protected $description = 'Show Gmail sending configuration and queue health (no secrets)';

    public function handle(GmailOAuth $oauth): int
    {
        $problems = [];
        $clientId = (string) config('gmail.client_id');
        $redirect = $oauth->redirectUri();

        $this->line('Enabled (GMAIL_SENDING_ENABLED): ' . (config('gmail.enabled') ? 'yes' : 'no'));
        $this->line('Client ID set: ' . ($clientId !== '' ? 'yes' : 'NO'));
        $this->line('Client secret set: ' . (filled(config('gmail.client_secret')) ? 'yes' : 'NO'));
        $this->line('Redirect URI: ' . $redirect . (config('gmail.redirect_uri') ? '' : '  (derived from APP_URL / request)'));
        $this->line('Scopes: ' . implode(' ', config('gmail.scopes')));
        $this->line('Queue: ' . config('gmail.queue.connection') . ' / ' . config('gmail.queue.name')
            . ' (job timeout ' . config('gmail.queue.timeout') . 's, retry_after '
            . config('queue.connections.' . config('gmail.queue.connection') . '.retry_after', 'n/a') . 's, HTTP timeout ' . config('gmail.timeout') . 's)');

        if (!$oauth->isConfigured()) {
            $problems[] = 'Gmail sending is not configured (needs GMAIL_SENDING_ENABLED, GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET).';
        }
        if ($clientId !== '' && $clientId === (string) config('services.google.client_id')) {
            $problems[] = 'GMAIL_CLIENT_ID is the same as the Google Login client. Use a dedicated OAuth client.';
        }
        if (app()->environment('production') && !str_starts_with($redirect, 'https://')) {
            $problems[] = 'Redirect URI must use https in production (set GMAIL_REDIRECT_URI).';
        }
        if (app()->environment('production') && !config('gmail.redirect_uri')) {
            $problems[] = 'Set GMAIL_REDIRECT_URI explicitly in production so it always matches Google Cloud.';
        }
        $retryAfter = (int) config('queue.connections.' . config('gmail.queue.connection') . '.retry_after', 0);
        if ($retryAfter && $retryAfter <= (int) config('gmail.queue.timeout') + 15) {
            $problems[] = 'MAIL_SEND_QUEUE_RETRY_AFTER must be well above the job timeout.';
        }
        if (4 * (int) config('gmail.timeout') > (int) config('gmail.queue.timeout')) {
            $problems[] = 'Job timeout is shorter than 4 Google HTTP calls (refresh + send + refresh + send).';
        }
        // Phase 4: unsubscribe and tracked links must point at the public site
        $appUrl = (string) config('app.url');
        if (app()->environment('production') && (!str_starts_with($appUrl, 'https://') || str_contains($appUrl, 'localhost'))) {
            $problems[] = 'APP_URL must be the public https address of this app (e.g. https://v2.customernearme.com), not ' . ($appUrl ?: 'empty') . '.';
        }

        $this->newLine();
        $this->line('Connected accounts: ' . ConnectedEmailAccount::where('status', 'active')->count() . ' active, '
            . ConnectedEmailAccount::where('status', 'needs_reconnect')->count() . ' need reconnect');
        $this->line('Emails: ' . OutboundEmail::where('status', 'pending')->count() . ' pending, '
            . OutboundEmail::where('status', 'sending')->count() . ' sending, '
            . OutboundEmail::where('status', 'failed')->where('outcome_unknown', true)->count() . ' unknown outcome');
        if (\Illuminate\Support\Facades\Schema::hasTable('email_tracking_settings')) {
            $this->line('Link tracking: ' . \App\Models\EmailTrackingSetting::where('link_tracking_enabled', true)->count() . ' sender(s) opted in, '
                . \App\Models\EmailTrackingLink::count() . ' tracked link(s), click data kept '
                . config('email_tracking.default_retention_days') . ' days by default');
        }

        $this->newLine();
        if ($problems === []) {
            $this->info('OK');
            return self::SUCCESS;
        }
        foreach ($problems as $p) {
            $this->warn('WARNING: ' . $p);
        }

        return self::FAILURE;
    }
}
