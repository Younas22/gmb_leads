<?php

namespace App\Services;

use App\Models\User;
use App\Models\AffiliateClick;
use App\Models\AffiliateConversion;
use App\Models\AffiliateEarning;
use App\Models\WithdrawalRequest;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    // ── Referral code generation ───────────────────

    public static function generateReferralCode(User $user): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    public static function ensureReferralCode(User $user): string
    {
        if (!$user->referral_code) {
            return self::generateReferralCode($user);
        }
        return $user->referral_code;
    }

    // ── Click tracking ──────────────────────────────

    public static function trackClick(string $referralCode, Request $request): ?AffiliateClick
    {
        $referrer = User::where('referral_code', $referralCode)
            ->where('affiliate_active', true)
            ->first();

        if (!$referrer) {
            return null;
        }

        // Prevent click flood: same IP + same code within 10 minutes = skip
        $recentClick = AffiliateClick::where('referral_code', $referralCode)
            ->where('ip', $request->ip())
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if ($recentClick) {
            return null;
        }

        return AffiliateClick::create([
            'referral_code' => $referralCode,
            'ip'            => $request->ip(),
            'user_agent'    => substr($request->userAgent() ?? '', 0, 500),
            'utm_source'    => $request->query('utm_source') ?? $request->query('utm'),
            'utm_campaign'  => $request->query('utm_campaign'),
            'landing_page'  => substr($request->fullUrl(), 0, 500),
        ]);
    }

    // ── Attribution: set referral cookie ───────────

    public static function setCookie(string $referralCode): \Symfony\Component\HttpFoundation\Cookie
    {
        $days = (int) Setting::get('affiliate_cookie_days', 30);

        return cookie('ref_code', $referralCode, $days * 60 * 24); // minutes
    }

    public static function getReferralCodeFromRequest(Request $request): ?string
    {
        return $request->query('ref') ?? $request->cookie('ref_code');
    }

    // ── Signup tracking ─────────────────────────────

    public static function handleSignup(User $newUser, ?string $referralCode): void
    {
        if (!$referralCode) {
            return;
        }

        $referrer = User::where('referral_code', $referralCode)
            ->where('affiliate_active', true)
            ->first();

        if (!$referrer || $referrer->id === $newUser->id) {
            return; // Prevent self-referral
        }

        $newUser->update(['referred_by' => $referralCode]);

        // Mark the most recent click as converted
        AffiliateClick::where('referral_code', $referralCode)
            ->whereNull('converted_at')
            ->orderByDesc('created_at')
            ->first()
            ?->update(['converted' => true, 'converted_at' => now()]);

        self::sendNotification($referrer, 'new_signup', [
            'referred_name' => $newUser->name,
        ]);
    }

    // ── Commission processing ───────────────────────

    public static function processConversion(Payment $payment): ?AffiliateConversion
    {
        $user = $payment->user;
        if (!$user || !$user->referred_by) {
            return null;
        }

        $referrer = User::where('referral_code', $user->referred_by)
            ->where('affiliate_active', true)
            ->first();

        if (!$referrer) {
            return null;
        }

        // Prevent duplicate conversion for same payment
        if (AffiliateConversion::where('payment_id', $payment->id)->exists()) {
            return null;
        }

        [$type, $rate, $commission] = self::calculateCommission($referrer, $payment->amount);

        $delayDays  = (int) Setting::get('affiliate_approval_delay_days', 7);
        $availableAt = now()->addDays($delayDays);

        DB::beginTransaction();
        try {
            $conversion = AffiliateConversion::create([
                'user_id'         => $user->id,
                'referrer_id'     => $referrer->id,
                'payment_id'      => $payment->id,
                'referral_code'   => $user->referred_by,
                'sale_amount'     => $payment->amount,
                'commission_type' => $type,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'status'          => 'pending',
                'available_at'    => $availableAt,
            ]);

            // Update earnings ledger
            $earning = $referrer->getOrCreateEarning();
            $earning->increment('total_earned', $commission);
            $earning->increment('pending', $commission);

            DB::commit();

            self::sendNotification($referrer, 'new_commission', [
                'amount'       => number_format($commission, 2),
                'sale_amount'  => number_format($payment->amount, 2),
                'available_at' => $availableAt->format('Y-m-d'),
            ]);

            // Dispatch delayed approval job
            \App\Jobs\ProcessAffiliateCommission::dispatch($conversion)
                ->delay($availableAt);

            return $conversion;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Affiliate commission failed: ' . $e->getMessage(), ['payment_id' => $payment->id]);
            return null;
        }
    }

    public static function calculateCommission(User $referrer, float $amount): array
    {
        // User-specific override takes priority
        if ($referrer->custom_commission_type && $referrer->custom_commission_value) {
            $type = $referrer->custom_commission_type;
            $rate = (float) $referrer->custom_commission_value;
        } else {
            $type = Setting::get('affiliate_commission_type', 'percent');
            $rate = (float) Setting::get('affiliate_commission_value', 10);
        }

        $commission = $type === 'percent'
            ? round($amount * $rate / 100, 2)
            : $rate;

        return [$type, $rate, $commission];
    }

    // ── Approve a conversion ────────────────────────

    public static function approveConversion(AffiliateConversion $conversion): void
    {
        if (!$conversion->isPending()) {
            return;
        }

        DB::beginTransaction();
        try {
            $conversion->update(['status' => 'approved', 'approved_at' => now()]);

            $earning = $conversion->referrer->getOrCreateEarning();
            $earning->decrement('pending', $conversion->commission_amount);
            $earning->increment('approved', $conversion->commission_amount);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Approve conversion failed: ' . $e->getMessage());
        }
    }

    // ── Withdrawal logic ────────────────────────────

    public static function requestWithdrawal(User $user, float $amount, string $method, array $details): array
    {
        $minAmount = (float) Setting::get('affiliate_min_withdrawal', 20);
        $earning   = $user->getOrCreateEarning();
        $available = $earning->available;

        if ($amount < $minAmount) {
            return ['success' => false, 'message' => "Minimum withdrawal is $" . number_format($minAmount, 2)];
        }

        if ($amount > $available) {
            return ['success' => false, 'message' => 'Insufficient balance. Available: $' . number_format($available, 2)];
        }

        // Prevent multiple pending requests
        if ($user->withdrawalRequests()->pending()->exists()) {
            return ['success' => false, 'message' => 'You already have a pending withdrawal request.'];
        }

        DB::beginTransaction();
        try {
            $wr = WithdrawalRequest::create([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'method'         => $method,
                'payment_details'=> $details,
                'status'         => 'pending',
            ]);

            // Reserve the amount
            $earning->decrement('approved', $amount);

            DB::commit();

            self::sendNotification($user, 'withdrawal_submitted', ['amount' => number_format($amount, 2)]);

            return ['success' => true, 'message' => 'Withdrawal request submitted successfully.', 'request' => $wr];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Withdrawal request failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit request. Please try again.'];
        }
    }

    public static function processWithdrawal(WithdrawalRequest $wr, string $action, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $earning = $wr->user->getOrCreateEarning();

            if ($action === 'approve') {
                $wr->update(['status' => 'approved', 'admin_notes' => $notes, 'processed_at' => now()]);
                self::sendNotification($wr->user, 'withdrawal_approved', ['amount' => number_format($wr->amount, 2)]);

            } elseif ($action === 'pay') {
                $wr->update(['status' => 'paid', 'admin_notes' => $notes, 'processed_at' => now()]);
                $earning->increment('withdrawn', $wr->amount);
                self::sendNotification($wr->user, 'withdrawal_paid', ['amount' => number_format($wr->amount, 2)]);

            } elseif ($action === 'reject') {
                $wr->update(['status' => 'rejected', 'admin_notes' => $notes, 'processed_at' => now()]);
                // Restore the reserved amount
                $earning->increment('approved', $wr->amount);
                self::sendNotification($wr->user, 'withdrawal_rejected', [
                    'amount' => number_format($wr->amount, 2),
                    'reason' => $notes,
                ]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Process withdrawal failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Fraud detection ─────────────────────────────

    public static function detectFraud(string $referralCode, string $ip): array
    {
        $flags = [];

        // Multiple signups from same IP with same referral code
        $ipSignups = User::where('referred_by', $referralCode)
            ->whereHas('affiliateClicks', fn($q) => $q->where('ip', $ip))
            ->count();

        if ($ipSignups > 2) {
            $flags[] = "Multiple signups ({$ipSignups}) from IP {$ip}";
        }

        // Many clicks from same IP
        $ipClicks = AffiliateClick::where('referral_code', $referralCode)
            ->where('ip', $ip)
            ->count();

        if ($ipClicks > 20) {
            $flags[] = "High click volume ({$ipClicks}) from single IP {$ip}";
        }

        return $flags;
    }

    // ── Notifications ───────────────────────────────

    private static function sendNotification(User $user, string $event, array $data = []): void
    {
        try {
            $templates = [
                'new_signup'            => 'affiliate-new-signup',
                'new_commission'        => 'affiliate-new-commission',
                'withdrawal_submitted'  => 'affiliate-withdrawal-submitted',
                'withdrawal_approved'   => 'affiliate-withdrawal-approved',
                'withdrawal_paid'       => 'affiliate-withdrawal-paid',
                'withdrawal_rejected'   => 'affiliate-withdrawal-rejected',
            ];

            $slug = $templates[$event] ?? null;
            if (!$slug) return;

            EmailService::sendWithTemplate($user->email, $slug, array_merge([
                'user_name' => $user->name,
            ], $data));
        } catch (\Throwable $e) {
            Log::warning("Affiliate notification failed ({$event}): " . $e->getMessage());
        }
    }

    // ── Admin stats ─────────────────────────────────

    public static function getAdminStats(): array
    {
        return [
            'total_affiliates'  => User::where('affiliate_active', true)->whereNotNull('referral_code')->count(),
            'total_clicks'      => AffiliateClick::count(),
            'total_conversions' => AffiliateConversion::where('status', 'approved')->count(),
            'total_revenue'     => AffiliateConversion::where('status', 'approved')->sum('sale_amount'),
            'total_commissions' => AffiliateConversion::where('status', 'approved')->sum('commission_amount'),
            'pending_payouts'   => WithdrawalRequest::pending()->sum('amount'),
        ];
    }
}
