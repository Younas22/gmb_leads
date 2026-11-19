<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\EmailReplyRecord;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\ReplyService;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Manually record (or correct) a reply. We never read the user's inbox. */
class EmailReplyController extends Controller
{
    public function __construct(private readonly ReplyService $replies)
    {
    }

    public function store(Request $request, int $email)
    {
        $user = $request->user();
        $model = OutboundEmail::ownedBy($user->getAccountOwner())->findOrFail($email);

        $data = $request->validate([
            'reply_status' => ['required', Rule::in(array_keys(EmailReplyRecord::STATUSES))],
            'reply_received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'follow_up_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'before:' . now()->addYears(2)->toDateString()],
        ]);

        $followUp = null;
        if (!empty($data['follow_up_date']) && $data['reply_status'] === 'needs_follow_up' && PlanFeatures::enabled($user, 'email_followups_enabled')) {
            $followUp = Carbon::createFromFormat('Y-m-d', $data['follow_up_date']);
        }

        $result = $this->replies->record($user, $model, $data['reply_status'], Carbon::parse($data['reply_received_at']), $data['notes'] ?? null, $followUp);

        if ($result['problem']) {
            return back()->withErrors(['reply' => $result['problem']])->withInput();
        }

        $message = 'Reply saved as "' . EmailReplyRecord::STATUSES[$data['reply_status']] . '".';
        if ($data['reply_status'] === 'not_interested') {
            $message .= ' This address was added to your do-not-email list and open follow-ups were cancelled.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, int $email)
    {
        $user = $request->user();
        $model = OutboundEmail::ownedBy($user->getAccountOwner())->findOrFail($email);

        return $this->replies->remove($user, $model)
            ? back()->with('success', 'Reply record removed.')
            : back()->with('error', 'There is no reply recorded for this email.');
    }
}
