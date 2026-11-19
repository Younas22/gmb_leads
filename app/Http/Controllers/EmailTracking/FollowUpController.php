<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\EmailFollowUp;
use App\Models\OutboundEmail;
use App\Models\SavedLead;
use App\Services\EmailTracking\FollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Follow-up reminders: overdue, upcoming, completed. Nothing is sent from here. */
class FollowUpController extends Controller
{
    public function __construct(private readonly FollowUpService $followUps)
    {
    }

    public function index(Request $request)
    {
        $owner = $request->user()->getAccountOwner();
        $today = today()->toDateString();
        $base = EmailFollowUp::ownedBy($owner)->with(['lead:id,name,email', 'email:id,subject', 'createdBy:id,name']);

        return view('user.email-follow-ups.index', [
            'overdue' => (clone $base)->open()->where('due_date', '<', $today)->orderBy('due_date')->limit(100)->get(),
            'upcoming' => (clone $base)->open()->where('due_date', '>=', $today)->orderBy('due_date')->paginate(25, ['*'], 'upcoming_page')->withQueryString(),
            'recent' => (clone $base)->whereIn('status', [EmailFollowUp::COMPLETED, EmailFollowUp::CANCELLED])->latest('updated_at')->limit(20)->with('completedBy:id,name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'saved_lead_id' => ['required_without:marketing_contact_id', 'nullable', 'integer'],
            'marketing_contact_id' => ['nullable', 'integer'],
            'outbound_email_id' => ['nullable', 'integer'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:' . now()->addYears(2)->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $due = Carbon::createFromFormat('Y-m-d', $data['due_date']);

        if (empty($data['saved_lead_id'])) {
            // Phase 5: an opted-in contact (Resend) that is not a saved lead
            $contact = \App\Models\MarketingContact::ownedBy($user->getAccountOwner())->findOrFail($data['marketing_contact_id']);
            $email = !empty($data['outbound_email_id'])
                ? OutboundEmail::ownedBy($user->getAccountOwner())->where('marketing_contact_id', $contact->id)->findOrFail($data['outbound_email_id'])
                : null;
            $result = $this->followUps->scheduleForContact($user, $contact, $due, $data['notes'] ?? null, $email);
        } else {
            $lead = SavedLead::whereIn('user_id', $user->getQuotaUserIds())->findOrFail($data['saved_lead_id']);
            $email = null;
            if (!empty($data['outbound_email_id'])) {
                $email = OutboundEmail::ownedBy($user->getAccountOwner())->where('saved_lead_id', $lead->id)->findOrFail($data['outbound_email_id']);
            }
            $result = $this->followUps->schedule($user, $lead, $due, $data['notes'] ?? null, $email);
        }

        return $result['problem']
            ? back()->withErrors(['follow_up' => $result['problem']])->withInput()
            : back()->with('success', 'Follow-up reminder saved for ' . $result['follow_up']->due_date->format('M j, Y') . '. Nothing will be sent automatically.');
    }

    public function complete(Request $request, int $followUp)
    {
        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $model = EmailFollowUp::ownedBy($request->user()->getAccountOwner())->findOrFail($followUp);

        return $this->followUps->complete($request->user(), $model, $request->input('notes'))
            ? back()->with('success', 'Follow-up marked as done.')
            : back()->with('error', 'This follow-up is already closed.');
    }

    public function cancel(Request $request, int $followUp)
    {
        $model = EmailFollowUp::ownedBy($request->user()->getAccountOwner())->findOrFail($followUp);

        return $this->followUps->cancel($request->user(), $model)
            ? back()->with('success', 'Follow-up cancelled.')
            : back()->with('error', 'This follow-up is already closed.');
    }
}
