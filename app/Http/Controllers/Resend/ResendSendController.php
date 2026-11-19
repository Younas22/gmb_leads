<?php

namespace App\Http\Controllers\Resend;

use App\Http\Controllers\Controller;
use App\Models\AiEmailDraft;
use App\Services\Resend\ResendSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** "Send via Resend" for one approved draft, to one opted-in contact, after confirmation. */
class ResendSendController extends Controller
{
    public function store(Request $request, int $draft, ResendSendService $service)
    {
        $model = AiEmailDraft::ownedBy($request->user()->getAccountOwner())->with('lead')->findOrFail($draft);
        Gate::authorize('update', $model);

        // Opted-in contact: confirm consent. Saved lead (owner allowed lead outreach): confirm it is
        // a one-to-one business email to someone who has not opted in.
        $mode = $service->preview($request->user(), $model, (int) $request->input('sender_id'), trim((string) $request->input('recipient')))['mode'];
        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:254'],
            'sender_id' => ['required', 'integer'],
            'confirm' => ['accepted'],
            $mode === 'lead' ? 'confirm_lead_outreach' : 'confirm_consent' => ['accepted'],
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9\-]{20,64}$/'],
        ], [
            'confirm.accepted' => 'Please confirm that you reviewed the email before sending.',
            'confirm_consent.accepted' => 'Please confirm the recipient opted in to receive your emails.',
            'confirm_lead_outreach.accepted' => 'Please confirm this is a one-to-one business email to someone who has not opted in.',
        ]);

        $result = $service->queue($request->user(), $model, (int) $data['sender_id'], trim($data['recipient']), $data['idempotency_key']);

        if ($result['problems'] !== []) {
            return back()->withErrors(['resend_send' => implode(' ', $result['problems'])])->withInput();
        }
        abort_if(!$result['email'], 404);

        return back()->with('success', 'Email queued for sending through Resend. Delivery updates appear once Resend reports them.');
    }
}
