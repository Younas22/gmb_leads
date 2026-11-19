<?php

namespace App\Http\Controllers\EmailSending;

use App\Http\Controllers\Controller;
use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use App\Services\Gmail\GmailException;
use App\Services\Gmail\GmailOAuth;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Email Sending" settings: connect / disconnect the user's own Gmail
 * (gmail.send only), suppression list and individual send history.
 */
class GmailConnectionController extends Controller
{
    private const SESSION_KEY = 'gmail_oauth';

    public function __construct(private readonly GmailOAuth $oauth, private readonly OutboundEmailService $emails)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();

        $request->validate(['status' => ['nullable', 'in:unknown,' . implode(',', array_keys(OutboundEmail::LABELS))]]);
        $status = $request->query('status');

        // One-to-one emails only (Gmail and individual Resend sends). Campaign recipients are
        // listed and cancelled on their campaign page; here they would flood the list.
        $history = OutboundEmail::ownedBy($owner)
            ->whereNull('campaign_id')
            ->with(['lead:id,name', 'sender:id,name'])
            ->when($status === 'unknown', fn ($q) => $q->where('status', OutboundEmail::STATUS_FAILED)->where('outcome_unknown', true))
            ->when($status === OutboundEmail::STATUS_FAILED, fn ($q) => $q->where('status', OutboundEmail::STATUS_FAILED)->where('outcome_unknown', false))
            ->when($status && !in_array($status, ['unknown', OutboundEmail::STATUS_FAILED], true), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('user.email-sending.index', [
            'account' => $this->emails->account($user),
            'configured' => $this->oauth->isConfigured(),
            'history' => $history,
            'suppressions' => EmailSuppression::where('owner_user_id', $owner->id)->latest('id')->limit(200)->get(),
            'sentToday' => $this->emails->sentToday($user),
            'dailyLimit' => $this->emails->dailyLimit($user),
            // Phase 4
            'trackingAvailable' => \App\Services\PlanFeatures::enabled($user, 'email_tracking_enabled'),
            'trackingSetting' => \App\Models\EmailTrackingSetting::for($user),
            'analyticsAvailable' => \App\Services\PlanFeatures::enabled($user, 'email_analytics_enabled'),
        ]);
    }

    /** Start the Gmail OAuth flow (state + PKCE kept in the session). */
    public function connect(Request $request)
    {
        if (!$this->oauth->isConfigured()) {
            return redirect()->route('user.email-sending.index')->with('error', 'Gmail sending is not configured on this server.');
        }

        $state = Str::random(48);
        $verifier = Str::random(96);
        $request->session()->put(self::SESSION_KEY, [
            'state' => $state,
            'verifier' => $verifier,
            'user_id' => $request->user()->id,
            'created_at' => now()->getTimestamp(),
        ]);

        return redirect()->away($this->oauth->authorizationUrl($state, $verifier, $this->emails->account($request->user())?->email));
    }

    public function callback(Request $request)
    {
        $user = $request->user();
        $saved = $request->session()->pull(self::SESSION_KEY); // single use
        $back = redirect()->route('user.email-sending.index');

        $valid = is_array($saved)
            && is_string($request->query('state'))
            && hash_equals((string) ($saved['state'] ?? ''), $request->query('state'))
            && (int) ($saved['user_id'] ?? 0) === (int) $user->id
            && now()->getTimestamp() - (int) ($saved['created_at'] ?? 0) <= (int) config('gmail.state_ttl', 600);

        if (!$valid) {
            EmailAuditLog::record($user->getAccountOwner()->id, $user->id, 'gmail_connect_rejected', null, ['reason' => 'invalid_state']);
            return $back->with('error', 'The Gmail connection request expired or was invalid. Please try again.');
        }

        if ($request->filled('error')) {
            return $back->with('error', $request->query('error') === 'access_denied'
                ? 'Gmail was not connected because access was not granted.'
                : 'Google did not complete the connection. Please try again.');
        }

        if (!is_string($request->query('code')) || $request->query('code') === '') {
            return $back->with('error', 'Google did not return an authorization code. Please try again.');
        }

        try {
            $result = $this->oauth->exchangeCode($request->query('code'), $saved['verifier']);
        } catch (GmailException $e) {
            return $back->with('error', $e->getMessage());
        }

        if (!in_array(GmailOAuth::SEND_SCOPE, $result['scopes'], true)) {
            // Google lets users untick individual permissions: without send we cannot work
            $this->oauth->revoke($result['access_token']);
            return $back->with('error', 'Permission to send email was not granted. Please connect again and allow "Send email on your behalf".');
        }

        // One Google account per CustomerNearMe user. Revoking a token revokes the whole
        // grant, so a mailbox shared by two users would break the other user's connection.
        // (Not revoked here for the same reason.)
        $takenElsewhere = ConnectedEmailAccount::where('google_sub', $result['sub'])
            ->where('user_id', '!=', $user->id)
            ->where('status', '!=', ConnectedEmailAccount::STATUS_DISCONNECTED)
            ->exists();
        if ($takenElsewhere) {
            EmailAuditLog::record($user->getAccountOwner()->id, $user->id, 'gmail_connect_rejected', null, ['reason' => 'account_in_use']);
            return $back->with('error', 'This Gmail account is already connected to another CustomerNearMe user. Disconnect it there first, or choose a different Gmail account.');
        }

        $existing = ConnectedEmailAccount::where('user_id', $user->id)->first();
        $sameMailbox = $existing && $existing->google_sub === $result['sub'];
        // Reuse a stored refresh token only if it is still healthy (never after a revoke)
        $refresh = $result['refresh_token']
            ?? ($sameMailbox && $existing->status === ConnectedEmailAccount::STATUS_ACTIVE ? $existing->refresh_token : null);
        if (!$refresh) {
            return $back->with('error', 'Google did not return a long-term token. Please remove CustomerNearMe from your Google account permissions and connect again.');
        }

        if ($existing && !$sameMailbox) {
            // Switched to a different Gmail: queued emails were confirmed for the old address
            OutboundEmail::where('connected_account_id', $existing->id)
                ->where('status', OutboundEmail::STATUS_PENDING)
                ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'draft_lock' => null, 'updated_at' => now()]);
        }

        ConnectedEmailAccount::updateOrCreate(['user_id' => $user->id], [
            'owner_user_id' => $user->getAccountOwner()->id,
            'provider' => 'gmail',
            'email' => $result['email'],
            'google_sub' => $result['sub'],
            'access_token' => $result['access_token'],
            'refresh_token' => $refresh,
            'token_expires_at' => now()->addSeconds(max(60, $result['expires_in'])),
            'scopes' => $result['scopes'],
            'status' => ConnectedEmailAccount::STATUS_ACTIVE,
            'last_error' => null,
            'connected_at' => now(),
            'disconnected_at' => null,
        ]);

        EmailAuditLog::record($user->getAccountOwner()->id, $user->id, 'gmail_connected', null, ['email' => $result['email']]);

        return $back->with('success', "Gmail connected: {$result['email']}");
    }

    public function disconnect(Request $request)
    {
        $user = $request->user();
        $account = $this->emails->account($user);

        if (!$account || $account->status === ConnectedEmailAccount::STATUS_DISCONNECTED) {
            return back()->with('error', 'No Gmail account is connected.');
        }

        $revoked = $account->refresh_token ? $this->oauth->revoke($account->refresh_token) : false;

        // Pending sends from this account cannot go out any more
        OutboundEmail::where('connected_account_id', $account->id)
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'draft_lock' => null, 'updated_at' => now()]);

        $account->forceFill([
            'status' => ConnectedEmailAccount::STATUS_DISCONNECTED,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'disconnected_at' => now(),
        ])->save();

        EmailAuditLog::record($account->owner_user_id, $user->id, 'gmail_disconnected', null, ['revoked_at_google' => $revoked]);

        return back()->with('success', $revoked
            ? 'Gmail disconnected and access revoked.'
            : 'Gmail disconnected. We could not confirm the revoke with Google; you can also remove access at myaccount.google.com/permissions.');
    }

    public function addSuppression(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'string', 'max:254']]);
        if (!OutboundEmailService::validRecipient($data['email'])) {
            return back()->withErrors(['email' => 'Enter a valid email address.']);
        }

        $user = $request->user();
        EmailSuppression::suppress($user->getAccountOwner()->id, $data['email'], EmailSuppression::REASON_MANUAL, null, $user->id);
        EmailAuditLog::record($user->getAccountOwner()->id, $user->id, 'suppression_added');

        return back()->with('success', 'Address added to your do-not-email list.');
    }

    /** Only manual entries can be removed; unsubscribes are permanent. */
    public function removeSuppression(Request $request, int $suppression)
    {
        $user = $request->user();
        $row = EmailSuppression::where('owner_user_id', $user->getAccountOwner()->id)->findOrFail($suppression);

        if ($row->reason !== EmailSuppression::REASON_MANUAL) {
            return back()->with('error', 'People who unsubscribed cannot be removed from the list.');
        }

        $row->delete();
        EmailAuditLog::record($user->getAccountOwner()->id, $user->id, 'suppression_removed');

        return back()->with('success', 'Address removed from your do-not-email list.');
    }
}
