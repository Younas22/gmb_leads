<?php

namespace App\Http\Controllers\Resend;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessResendWebhook;
use App\Models\ResendWebhookConfig;
use App\Models\ResendWebhookReceipt;
use App\Services\Resend\ResendWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * POST /webhooks/resend/{token}
 *
 * The token only selects WHICH customer's configuration to use; the request is
 * accepted only with a valid signature made with that customer's secret, so one
 * customer can never write into another customer's data. Account IDs inside the
 * payload are never trusted. Work is queued; the response is immediate.
 */
class ResendWebhookController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $config = ResendWebhookConfig::where('endpoint_token', $token)->first();
        if (!$config) {
            return response()->json(['error' => 'unknown endpoint'], 404);
        }

        $raw = $request->getContent();
        $failure = ResendWebhookService::verify(
            (string) $config->signing_secret,
            $request->header('svix-id'),
            $request->header('svix-timestamp'),
            $request->header('svix-signature'),
            $raw
        );

        if ($failure !== null) {
            ResendWebhookConfig::whereKey($config->id)->increment('rejected_count', 1, [
                'last_failure_at' => now(), 'last_failure_reason' => $failure,
                'status' => $config->status === ResendWebhookConfig::ACTIVE && $failure === 'bad_signature' ? ResendWebhookConfig::FAILING : $config->status,
            ]);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || empty($payload['type']) || !is_string($payload['type'])) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        $minimal = ResendWebhookService::minimal($payload);
        $created = null;
        try {
            $created = isset($minimal['created_at']) ? Carbon::parse($minimal['created_at']) : null;
        } catch (\Throwable) {
        }

        // Replay / retry protection: one receipt per svix-id per endpoint
        $inserted = ResendWebhookReceipt::query()->insertOrIgnore([
            'webhook_config_id' => $config->id,
            'owner_user_id' => $config->owner_user_id,
            'svix_id' => mb_substr((string) $request->header('svix-id'), 0, 100),
            'event_type' => mb_substr($payload['type'], 0, 40),
            'event_created_at' => $created,
            'status' => 'received',
            'payload' => json_encode($minimal),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ResendWebhookConfig::whereKey($config->id)->increment('verified_count', 1, [
            'last_event_at' => now(), 'last_event_type' => mb_substr($payload['type'], 0, 40), 'status' => ResendWebhookConfig::ACTIVE,
        ]);

        if (!$inserted) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $receipt = ResendWebhookReceipt::where('webhook_config_id', $config->id)->where('svix_id', mb_substr((string) $request->header('svix-id'), 0, 100))->first();
        ProcessResendWebhook::dispatch($receipt->id)
            ->onConnection(config('resend_user.queue.connection'))
            ->onQueue(config('resend_user.queue.webhook'));

        return response()->json(['ok' => true]);
    }
}
