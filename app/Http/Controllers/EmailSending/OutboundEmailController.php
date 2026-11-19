<?php

namespace App\Http\Controllers\EmailSending;

use App\Http\Controllers\Controller;
use App\Models\AiEmailDraft;
use App\Models\OutboundEmail;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** User-confirmed sending of one approved draft, plus cancel / retry. */
class OutboundEmailController extends Controller
{
    public function __construct(private readonly OutboundEmailService $emails)
    {
    }

    public function store(Request $request, int $draft)
    {
        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:254'],
            'confirm' => ['accepted'],
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9\-]{20,64}$/'],
            // What the review screen showed about link tracking (must match at send time)
            'tracking_shown' => ['nullable', 'in:0,1'],
        ], ['confirm.accepted' => 'Please confirm that you reviewed the email before sending.']);

        $model = AiEmailDraft::ownedBy($request->user()->getAccountOwner())->with('lead')->findOrFail($draft);
        Gate::authorize('update', $model);

        $trackingShown = isset($data['tracking_shown']) ? $data['tracking_shown'] === '1' : null;
        $result = $this->emails->queue($request->user(), $model, trim($data['recipient']), $data['idempotency_key'], $trackingShown);

        if ($result['problems'] !== []) {
            return back()->withErrors(['send' => implode(' ', $result['problems'])])->withInput();
        }
        if (!$result['email']) {
            abort(404);
        }

        return back()->with('success', 'Email queued for sending from your Gmail. It will go out within a minute or two.');
    }

    public function cancel(Request $request, int $email)
    {
        $model = $this->find($request, $email);

        return $this->emails->cancel($request->user(), $model)
            ? back()->with('success', 'Email cancelled.')
            : back()->with('error', 'Only emails that have not started sending can be cancelled.');
    }

    public function retry(Request $request, int $email)
    {
        $model = $this->find($request, $email);
        $problems = $this->emails->retry($request->user(), $model, $request->boolean('confirm_not_sent'));

        return $problems === []
            ? back()->with('success', 'Email queued again.')
            : back()->with('error', implode(' ', $problems));
    }

    private function find(Request $request, int $id): OutboundEmail
    {
        return OutboundEmail::ownedBy($request->user()->getAccountOwner())->findOrFail($id);
    }
}
