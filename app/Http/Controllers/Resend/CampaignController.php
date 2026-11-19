<?php

namespace App\Http\Controllers\Resend;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailTrackingEvent;
use App\Models\MarketingContactList;
use App\Models\OutboundEmail;
use App\Models\ResendSender;
use App\Models\UserEmailTemplate;
use App\Services\Resend\CampaignService;
use App\Services\Resend\ResendDomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Campaigns to opted-in contacts, sent as Resend Broadcasts after explicit approval. */
class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns)
    {
    }

    public function index(Request $request)
    {
        $owner = $request->user()->getAccountOwner();

        return view('user.campaigns.index', [
            'campaigns' => EmailCampaign::ownedBy($owner)->with('sender:id,from_email', 'list:id,name')->latest('id')->paginate(20),
            'templates' => UserEmailTemplate::where('owner_user_id', $owner->id)->latest('id')->get(),
            'folderSends' => \App\Models\LeadFolderSend::where('owner_user_id', $owner->id)->latest('id')->limit(10)->get(),
        ]);
    }

    public function create(Request $request)
    {
        return $this->form($request, new EmailCampaign(['segment_filters' => []]));
    }

    public function edit(Request $request, int $campaign)
    {
        $model = $this->find($request, $campaign);
        abort_unless($model->isEditable(), 403, 'Only draft campaigns can be edited.');

        return $this->form($request, $model);
    }

    public function store(Request $request)
    {
        $owner = $request->user()->getAccountOwner();
        $campaign = EmailCampaign::create($this->validated($request) + ['owner_user_id' => $owner->id, 'created_by_user_id' => $request->user()->id, 'status' => EmailCampaign::DRAFT]);

        return redirect()->route('user.campaigns.show', $campaign->id)->with('success', 'Campaign saved as a draft. Review it below; nothing is sent until you approve it.');
    }

    public function update(Request $request, int $campaign)
    {
        $model = $this->find($request, $campaign);
        abort_unless($model->isEditable(), 403, 'Only draft campaigns can be edited.');
        $model->update($this->validated($request));

        return redirect()->route('user.campaigns.show', $model->id)->with('success', 'Campaign updated.');
    }

    public function show(Request $request, int $campaign)
    {
        $model = $this->find($request, $campaign);
        $emails = OutboundEmail::where('campaign_id', $model->id);
        $ids = (clone $emails)->select('id');

        return view('user.campaigns.show', [
            'campaign' => $model,
            'review' => $this->campaigns->review($request->user(), $model),
            'progress' => [
                'recipients' => (clone $emails)->count(),
                'skipped' => (clone $emails)->where('status', OutboundEmail::STATUS_CANCELLED)->whereNotNull('error_code')->count(),
                'accepted' => (clone $emails)->where('status', OutboundEmail::STATUS_SENT)->count(),
                'delivered' => (clone $emails)->whereNotNull('delivered_at')->count(),
                'bounced' => (clone $emails)->whereNotNull('bounced_at')->count(),
                'complained' => (clone $emails)->whereNotNull('complained_at')->count(),
                'opened' => EmailTrackingEvent::whereIn('outbound_email_id', $ids)->where('event_type', EmailTrackingEvent::PROVIDER_OPENED)->distinct()->count('outbound_email_id'),
                'clicked' => EmailTrackingEvent::whereIn('outbound_email_id', $ids)->where('event_type', EmailTrackingEvent::PROVIDER_CLICKED)->distinct()->count('outbound_email_id'),
                'unsubscribed' => EmailTrackingEvent::where('campaign_id', $model->id)->where('event_type', EmailTrackingEvent::OPTED_OUT)->count(),
            ],
            'recipients' => OutboundEmail::where('campaign_id', $model->id)->with('reply')->orderBy('id')->paginate(50),
        ]);
    }

    public function test(Request $request, int $campaign)
    {
        $model = $this->find($request, $campaign);
        abort_unless($model->isEditable(), 403);
        $problem = $this->campaigns->sendTest($request->user(), $model);

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Test email sent to ' . $request->user()->email . ' only.');
    }

    public function approve(Request $request, int $campaign)
    {
        $model = $this->find($request, $campaign);
        $data = $request->validate([
            'confirm' => ['accepted'],
            'confirm_consent' => ['accepted'],
            'expected_count' => ['required', 'integer', 'min:1'],
            'send_mode' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_at' => ['nullable', 'required_if:send_mode,schedule', 'date'],
        ], [
            'confirm.accepted' => 'Confirm that you reviewed the campaign.',
            'confirm_consent.accepted' => 'Confirm that every recipient opted in to receive these emails.',
        ]);

        // The number the user approved must still be the number that will be sent
        $eligible = $this->campaigns->eligible($model)->count();
        if ($eligible !== (int) $data['expected_count']) {
            return back()->with('error', "The number of eligible recipients changed ({$data['expected_count']} -> {$eligible}). Please review again.");
        }

        $at = $data['send_mode'] === 'schedule' ? Carbon::parse($data['scheduled_at']) : null;
        $problem = $this->campaigns->approve($request->user(), $model, $at);

        return $problem ? back()->with('error', $problem) : back()->with('success', $at ? 'Campaign approved and being scheduled in Resend.' : 'Campaign approved. Contacts are being prepared in Resend; it will be sent within a few minutes.');
    }

    public function cancel(Request $request, int $campaign)
    {
        $problem = $this->campaigns->cancel($request->user(), $this->find($request, $campaign));

        return $problem ? back()->with('error', $problem) : back()->with('success', 'Campaign cancelled. Nothing more will be sent.');
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'subject' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:20000']]);
        UserEmailTemplate::create($data + ['owner_user_id' => $request->user()->getAccountOwner()->id, 'created_by_user_id' => $request->user()->id]);

        return back()->with('success', 'Template saved.');
    }

    public function destroyTemplate(Request $request, int $template)
    {
        UserEmailTemplate::where('owner_user_id', $request->user()->getAccountOwner()->id)->findOrFail($template)->delete();

        return back()->with('success', 'Template deleted.');
    }

    /** JSON: AI suggestion for subject + message from the AI Sales Profile (nothing is saved). */
    public function aiWrite(Request $request, \App\Services\Ai\CampaignWriter $writer)
    {
        $data = $request->validate(['brief' => ['nullable', 'string', 'max:600']]);
        try {
            return response()->json(['ok' => true] + $writer->write($request->user(), (string) ($data['brief'] ?? '')));
        } catch (\InvalidArgumentException|\App\Services\Ai\Exceptions\AiException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function form(Request $request, EmailCampaign $campaign)
    {
        $owner = $request->user()->getAccountOwner();

        return view('user.campaigns.form', [
            'campaign' => $campaign,
            'senders' => ResendDomainService::usableSenders($owner),
            'lists' => MarketingContactList::where('owner_user_id', $owner->id)->orderBy('name')->get(),
            'templates' => UserEmailTemplate::where('owner_user_id', $owner->id)->orderBy('name')->get(),
            // Lead folders: each lead gets its own AI email (separate flow, see FolderSendController)
            'leadFolders' => $campaign->exists ? collect() : app(\App\Services\Outreach\FolderSendService::class)->folders($request->user()),
        ]);
    }

    private function validated(Request $request): array
    {
        $owner = $request->user()->getAccountOwner();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sender_id' => ['required', 'integer', Rule::exists('resend_senders', 'id')->where('owner_user_id', $owner->id)],
            'list_id' => ['nullable', 'integer', Rule::exists('marketing_contact_lists', 'id')->where('owner_user_id', $owner->id)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'filter_tag' => ['nullable', 'string', 'max:40'],
            'filter_organization' => ['nullable', 'string', 'max:100'],
            'filter_consent_after' => ['nullable', 'date'],
        ]);

        return [
            'name' => $data['name'],
            'sender_id' => $data['sender_id'],
            'list_id' => $data['list_id'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'segment_filters' => array_filter([
                'tag' => isset($data['filter_tag']) ? mb_strtolower(trim($data['filter_tag'])) : null,
                'organization' => $data['filter_organization'] ?? null,
                'consent_after' => $data['filter_consent_after'] ?? null,
            ]),
        ];
    }

    private function find(Request $request, int $id): EmailCampaign
    {
        return EmailCampaign::ownedBy($request->user()->getAccountOwner())->findOrFail($id);
    }
}
