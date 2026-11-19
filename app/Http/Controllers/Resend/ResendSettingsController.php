<?php

namespace App\Http\Controllers\Resend;

use App\Http\Controllers\Controller;
use App\Models\ResendDomain;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\ResendWebhookReceipt;
use App\Services\Resend\ResendConnectionService;
use App\Services\Resend\ResendDomainService;
use App\Services\Resend\ResendQuota;
use App\Services\Resend\ResendWebhookService;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Email Sending -> Resend tab. Connecting keys, domains, senders and the webhook are
 * account settings: only the account owner (not team members) may change them.
 */
class ResendSettingsController extends Controller
{
    public function __construct(
        private readonly ResendConnectionService $connections,
        private readonly ResendDomainService $domains,
        private readonly ResendWebhookService $webhooks,
    ) {
    }

    public function show(Request $request, ResendQuota $quota)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $integration = ResendIntegration::forOwner($owner);
        $webhook = $integration && $integration->status !== ResendIntegration::DISCONNECTED ? $this->webhooks->config($integration) : null;

        return view('user.email-sending.resend', [
            'integration' => $integration,
            'domains' => $integration ? $integration->domains()->orderByDesc('present')->orderBy('name')->get() : collect(),
            'senders' => ResendSender::where('owner_user_id', $owner->id)->with('domain')->orderBy('from_email')->get(),
            'webhook' => $webhook,
            'receipts' => $webhook ? ResendWebhookReceipt::where('webhook_config_id', $webhook->id)->latest('id')->limit(10)->get() : collect(),
            'canManage' => !$user->isTeamMember(),
            'usage' => [
                'daily' => [$quota->used($owner, ResendQuota::DAILY), PlanFeatures::limit($user, ResendQuota::DAILY)],
                'monthly' => [$quota->used($owner, ResendQuota::MONTHLY), PlanFeatures::limit($user, ResendQuota::MONTHLY)],
            ],
            'trackingPlan' => PlanFeatures::enabled($user, 'email_tracking_enabled'),
        ]);
    }

    public function connect(Request $request)
    {
        $this->owner($request);
        $data = $request->validate(['api_key' => ['required', 'string', 'max:255']]);

        $result = $this->connections->connect($request->user(), $data['api_key']);
        if ($result['problem']) {
            return back()->withErrors(['api_key' => $result['problem']]);
        }

        return back()->with('success', $result['account_changed']
            ? 'Connected. This key belongs to a different Resend account: add your domains, senders and webhook again.'
            : 'Resend connected. Your API key is stored encrypted.');
    }

    public function check(Request $request)
    {
        $integration = $this->integration($request);
        $problem = $this->connections->check($integration);

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Resend connection is working.');
    }

    public function disconnect(Request $request)
    {
        $this->owner($request);
        $problem = $this->connections->disconnect($request->user(), $this->integration($request));

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Resend disconnected and the API key deleted.');
    }

    public function addDomain(Request $request)
    {
        $this->owner($request);
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'region' => ['required', Rule::in(ResendDomainService::REGIONS)]]);
        $problem = $this->domains->add($request->user(), $this->integration($request), $data['domain'], $data['region']);

        return $problem ? back()->withErrors(['domain' => $problem])->withInput() : back()->with('success', 'Domain added in Resend. Add the DNS records shown below at your DNS provider, then click "Verify".');
    }

    public function verifyDomain(Request $request, int $domain)
    {
        $model = $this->domain($request, $domain);
        $problem = $this->domains->verify($model);

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Verification requested. Status: ' . $model->fresh()->statusLabel() . '. DNS changes can take a while; refresh later.');
    }

    public function refreshDomain(Request $request, int $domain)
    {
        $problem = $this->domains->refresh($this->domain($request, $domain));

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Domain status refreshed from Resend.');
    }

    public function domainTracking(Request $request, int $domain)
    {
        $this->owner($request);
        $data = $request->validate(['open_tracking' => ['required', 'boolean'], 'click_tracking' => ['required', 'boolean'], 'confirm' => ['accepted']],
            ['confirm.accepted' => 'Confirm that you want to change tracking for every email sent from this domain.']);
        $problem = $this->domains->setTracking($request->user(), $this->domain($request, $domain), (bool) $data['open_tracking'], (bool) $data['click_tracking']);

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Resend tracking settings updated for this domain.');
    }

    public function addSender(Request $request)
    {
        $this->owner($request);
        $data = $request->validate([
            'domain_id' => ['required', 'integer'],
            'from_name' => ['required', 'string', 'max:100'],
            'local_part' => ['required', 'string', 'max:64'],
            'reply_to' => ['nullable', 'email', 'max:254'],
        ]);
        $result = $this->domains->addSender($this->domain($request, (int) $data['domain_id']), $data['from_name'], $data['local_part'], $data['reply_to'] ?? null);

        return $result['problem'] ? back()->withErrors(['sender' => $result['problem']])->withInput() : back()->with('success', 'Sender ' . $result['sender']->from_email . ' added.');
    }

    public function deleteSender(Request $request, int $sender)
    {
        $this->owner($request);
        ResendSender::where('owner_user_id', $request->user()->getAccountOwner()->id)->findOrFail($sender)->delete();

        return back()->with('success', 'Sender removed.');
    }

    public function createWebhook(Request $request)
    {
        $this->owner($request);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Confirm that CustomerNearMe may create a webhook in your Resend account.']);
        $problem = $this->webhooks->createRemote($request->user(), $this->integration($request));

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Webhook created in your Resend account. Delivery, bounce and complaint events will now be recorded.');
    }

    public function webhookSecret(Request $request)
    {
        $this->owner($request);
        $data = $request->validate(['signing_secret' => ['required', 'string', 'max:255']]);
        $problem = $this->webhooks->setSecret($request->user(), $this->integration($request), $data['signing_secret']);

        return $problem ? back()->withErrors(['signing_secret' => $problem]) : back()->with('success', 'Signing secret saved (encrypted).');
    }

    /** Owner-only switch for one-to-one Resend emails to saved leads (risk notice must be accepted). */
    public function leadOutreach(Request $request)
    {
        $this->owner($request);
        $integration = $this->integration($request);
        $enable = $request->boolean('enabled');
        if ($enable) {
            $request->validate(['acknowledge' => ['accepted']], [
                'acknowledge.accepted' => 'Confirm that you understand the risk before allowing Resend emails to leads.',
            ]);
        }

        $integration->forceFill([
            'lead_outreach_enabled_at' => $enable ? ($integration->lead_outreach_enabled_at ?? now()) : null,
            'lead_outreach_enabled_by' => $enable ? ($integration->lead_outreach_enabled_by ?? $request->user()->id) : null,
        ])->save();
        \App\Models\EmailAuditLog::record($integration->owner_user_id, $request->user()->id, $enable ? 'resend_lead_outreach_enabled' : 'resend_lead_outreach_disabled');

        return back()->with('success', $enable
            ? 'One-to-one Resend emails to saved leads are now allowed. Campaigns still go to opted-in contacts only.'
            : 'Resend emails to saved leads are switched off. Resend is used for opted-in contacts only.');
    }

    private function owner(Request $request): void
    {
        abort_if($request->user()->isTeamMember(), 403, 'Only the account owner can change Resend settings.');
    }

    private function integration(Request $request): ResendIntegration
    {
        $integration = ResendIntegration::forOwner($request->user()->getAccountOwner());
        abort_if(!$integration || !filled($integration->api_key), 404);

        return $integration;
    }

    private function domain(Request $request, int $id): ResendDomain
    {
        return ResendDomain::where('owner_user_id', $request->user()->getAccountOwner()->id)->with('integration')->findOrFail($id);
    }
}
