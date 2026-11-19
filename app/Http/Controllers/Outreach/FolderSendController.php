<?php

namespace App\Http\Controllers\Outreach;

use App\Http\Controllers\Controller;
use App\Models\LeadFolderSend;
use App\Models\LeadFolderSendItem;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Services\Outreach\FolderSendService;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Campaigns → "Send to a lead folder": review every lead's own AI email, choose Gmail or
 * Resend and the pause between emails, confirm once, then watch the progress.
 */
class FolderSendController extends Controller
{
    public function __construct(private readonly FolderSendService $service)
    {
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $this->authorizeSending($request);
        $data = $request->validate([
            'folder_id' => ['nullable', 'integer'],
            'provider' => ['nullable', Rule::in(array_keys(LeadFolderSend::PROVIDERS))],
            'sender_id' => ['nullable', 'integer'],
        ]);
        $providers = $this->providers($request);
        $provider = $data['provider'] ?? array_key_first($providers) ?? 'gmail';
        $senders = ResendSender::where('owner_user_id', $user->getAccountOwner()->id)->with('domain')->get()->filter(fn ($s) => $s->canSend())->values();
        $senderId = (int) ($data['sender_id'] ?? $senders->first()?->id);
        $folder = !empty($data['folder_id']) ? $this->service->folder($user, (int) $data['folder_id']) : null;
        $rows = $folder ? $this->service->plan($user, $folder, $provider) : [];

        return view('user.campaigns.folder-send', [
            'folders' => $this->service->folders($user),
            'folder' => $folder,
            'rows' => $rows,
            'provider' => $provider,
            'providers' => $providers,
            'senders' => $senders,
            'senderId' => $senderId,
            'problems' => $folder ? $this->service->providerProblems($user, $provider, $senderId) : [],
            'intervals' => FolderSendService::INTERVALS,
            'leadOutreach' => (bool) ResendIntegration::forOwner($user->getAccountOwner())?->allowsLeadOutreach(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeSending($request);
        $data = $request->validate([
            'folder_id' => ['required', 'integer'],
            'provider' => ['required', Rule::in(array_keys($this->providers($request)))],
            'sender_id' => ['nullable', 'integer'],
            'interval' => ['required', 'integer', Rule::in(FolderSendService::INTERVALS)],
            'expected_ready' => ['required', 'integer', 'min:1'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Confirm that you reviewed these emails and want them approved and sent.',
            'provider.in' => 'This sending method is not available for your account.',
        ]);
        $folder = $this->service->folder($user, (int) $data['folder_id']);
        $back = redirect()->route('user.folder-sends.create', ['folder_id' => $folder->id, 'provider' => $data['provider'], 'sender_id' => $data['sender_id'] ?? null]);

        if ($problems = $this->service->providerProblems($user, $data['provider'], $data['sender_id'] ?? null)) {
            return $back->withErrors(['provider' => implode(' ', $problems)]);
        }
        $ready = count(array_filter($this->service->plan($user, $folder, $data['provider']), fn ($r) => $r['ready']));
        if ($ready !== (int) $data['expected_ready']) {
            return $back->withErrors(['folder_id' => "The folder changed since you reviewed it ({$data['expected_ready']} → {$ready} ready). Please review again."]);
        }

        $send = $this->service->start($user, $folder, $data['provider'], isset($data['sender_id']) ? (int) $data['sender_id'] : null, (int) $data['interval']);

        return redirect()->route('user.folder-sends.show', $send->id)
            ->with('success', "{$ready} email(s) approved and scheduled: one every {$data['interval']} minute(s) through " . LeadFolderSend::PROVIDERS[$data['provider']] . '.');
    }

    public function show(Request $request, int $send)
    {
        $model = $this->find($request, $send);

        return view('user.campaigns.folder-send-show', [
            'send' => $model,
            'items' => $model->items()->with('email:id,status,delivery_status,error_message')->orderByRaw('due_at IS NULL')->orderBy('due_at')->orderBy('id')->paginate(50),
            'counts' => $model->counts(),
        ]);
    }

    public function cancel(Request $request, int $send)
    {
        $model = $this->find($request, $send);
        $n = $this->service->cancel($request->user(), $model);

        return back()->with('success', "Stopped. {$n} email(s) that had not been sent yet were cancelled.");
    }

    /** gmail / resend → label, only what this account can use. */
    private function providers(Request $request): array
    {
        $user = $request->user();
        $out = [];
        if (PlanFeatures::enabled($user, 'gmail_sending_enabled')) {
            $out['gmail'] = 'Gmail';
        }
        if (PlanFeatures::enabled($user, 'resend_single_sending_enabled') && ResendIntegration::forOwner($user->getAccountOwner())?->isUsable()) {
            $out['resend'] = 'Resend';
        }

        return $out;
    }

    private function authorizeSending(Request $request): void
    {
        abort_if($this->providers($request) === [] && !PlanFeatures::enabled($request->user(), 'gmail_sending_enabled'), 403, 'Your plan does not include email sending.');
    }

    private function find(Request $request, int $id): LeadFolderSend
    {
        return LeadFolderSend::where('owner_user_id', $request->user()->getAccountOwner()->id)->findOrFail($id);
    }
}
