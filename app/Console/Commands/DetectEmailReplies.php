<?php

namespace App\Console\Commands;

use App\Models\ConnectedEmailAccount;
use App\Models\EmailReplyRecord;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\ReplyService;
use App\Services\Gmail\GmailApi;
use App\Services\Gmail\GmailException;
use App\Services\Gmail\GmailTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds replies to sent emails in the connected Gmail inbox (read-only, headers only)
 * and records them, so the user does not have to. Runs only for Gmail accounts that
 * granted the read-only scope (GMAIL_REPLY_DETECTION=true + reconnect).
 *
 *  - Gmail emails: a message from someone else in the same Gmail thread.
 *  - Resend emails: a message from the recipient in the owner's connected Gmail
 *    after the send time (replies land there when Reply-To is that Gmail address).
 */
class DetectEmailReplies extends Command
{
    protected $signature = 'email:detect-replies {--owner= : Only this account owner id}';

    protected $description = 'Find replies to sent emails in connected Gmail inboxes (read-only)';

    public function handle(GmailApi $api, GmailTokenManager $tokens, ReplyService $replies): int
    {
        $accounts = ConnectedEmailAccount::query()
            ->when($this->option('owner'), fn ($q, $id) => $q->where('owner_user_id', (int) $id))
            ->get()->filter->canReadReplies()->keyBy('id');

        if ($accounts->isEmpty()) {
            $this->info('No Gmail account has granted read-only access. Nothing to check.');
            return self::SUCCESS;
        }

        $emails = OutboundEmail::query()
            ->whereIn('owner_user_id', $accounts->pluck('owner_user_id')->unique())
            ->where('status', OutboundEmail::STATUS_SENT)
            ->where('sent_at', '>=', now()->subDays((int) config('gmail.reply_lookback_days', 30)))
            ->whereNotIn('id', EmailReplyRecord::select('outbound_email_id'))
            ->orderBy('reply_checked_at')->orderByDesc('id')
            ->limit((int) config('gmail.reply_check_batch', 100))
            ->get();

        $found = 0;
        foreach ($emails as $email) {
            try {
                $account = $email->provider === OutboundEmail::PROVIDER_GMAIL
                    ? $accounts->get($email->connected_account_id)
                    : $accounts->firstWhere('owner_user_id', $email->owner_user_id);
                if (!$account) {
                    continue;
                }

                $token = $tokens->accessToken($account);
                $mine = mb_strtolower($account->email);
                $sentAt = $email->sent_at;

                if ($email->provider === OutboundEmail::PROVIDER_GMAIL && $email->gmail_thread_id) {
                    $messages = $api->threadMessages($token, $email->gmail_thread_id);
                } else {
                    $messages = $api->search($token, 'from:' . $email->to_email . ' after:' . ($sentAt->timestamp - 60), 3);
                }

                $reply = collect($messages)
                    ->filter(fn ($m) => $m['from'] !== '' && $m['from'] !== $mine && $m['at']->gt($sentAt))
                    ->sortBy(fn ($m) => $m['at']->timestamp)
                    ->first();

                if ($reply && $replies->recordDetected($email, $reply['at'])) {
                    $found++;
                }
                $email->forceFill(['reply_checked_at' => now()])->save();
            } catch (GmailException $e) {
                Log::info('Reply check skipped', ['outbound_email_id' => $email->id, 'code' => $e->getMessage()]);
            } catch (Throwable $e) {
                Log::warning('Reply check failed', ['outbound_email_id' => $email->id, 'exception' => get_class($e)]);
            }
        }

        $this->info("Checked {$emails->count()} email(s), found {$found} new repl(ies).");

        return self::SUCCESS;
    }
}
