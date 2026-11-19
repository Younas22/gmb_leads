<?php

namespace App\Http\Controllers\Resend;

use App\Http\Controllers\Controller;
use App\Models\MarketingConsentLog;
use App\Models\MarketingContact;
use App\Models\MarketingContactList;
use App\Services\Resend\ContactService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Opted-in contacts and lists (account-wide; team members may manage them). */
class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contacts)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'list' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['sendable', 'blocked'])],
        ]);

        $contacts = MarketingContact::ownedBy($owner)
            ->when($data['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('email', 'like', '%' . str_replace(['%', '_'], '', $s) . '%')
                ->orWhere('organization', 'like', '%' . str_replace(['%', '_'], '', $s) . '%')))
            ->when($data['list'] ?? null, fn ($q, $id) => $q->whereHas('lists', fn ($l) => $l->where('marketing_contact_lists.id', $id)->where('owner_user_id', $owner->id)))
            ->when(($data['status'] ?? null) === 'sendable', fn ($q) => $q->sendable())
            ->when(($data['status'] ?? null) === 'blocked', fn ($q) => $q->where(fn ($w) => $w->whereNotNull('unsubscribed_at')->orWhereNotNull('bounced_at')->orWhereNotNull('complained_at')->orWhere('consent_status', MarketingContact::WITHDRAWN)))
            ->with('lists:id,name')
            ->latest('id')->paginate(50)->withQueryString();

        return view('user.campaigns.contacts', [
            'contacts' => $contacts,
            'lists' => MarketingContactList::where('owner_user_id', $owner->id)->withCount('contacts')->orderBy('name')->get(),
            'total' => $this->contacts->count($owner),
            'limit' => $this->contacts->limit($user),
            'sources' => config('resend_user.consent_sources'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:150'],
            'consent_source' => ['required', Rule::in(array_keys(config('resend_user.consent_sources')))],
            'consent_date' => ['required', 'date', 'before_or_equal:today'],
            'consent_evidence' => ['required', 'string', 'min:10', 'max:2000'],
            'consent_purpose' => ['nullable', 'string', 'max:150'],
            'tags' => ['nullable', 'string', 'max:300'],
            'list_id' => ['nullable', 'integer'],
        ], ['consent_evidence.min' => 'Describe where and how this person opted in (at least 10 characters).']);

        [$clean, $problem] = $this->contacts->validateRow($data);
        if ($problem) {
            return back()->withErrors(['email' => 'Contact not added: ' . $problem])->withInput();
        }

        $result = $this->contacts->upsert($request->user(), $clean, 'manual', $this->list($request, $data['list_id'] ?? null));

        return match ($result['result']) {
            'blocked' => back()->withErrors(['email' => 'This address unsubscribed, bounced, complained or is on your do-not-email list. It cannot be added back.'])->withInput(),
            'limit' => back()->withErrors(['email' => 'You have reached your plan\'s contact limit.'])->withInput(),
            default => back()->with('success', 'Contact ' . ($result['result'] === 'created' ? 'added' : 'updated') . ' with consent recorded.'),
        };
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:' . (int) config('resend_user.import_max_kb', 2048)],
            'list_id' => ['nullable', 'integer'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Confirm that every person in the file opted in to receive your emails.']);

        $report = $this->contacts->import($request->user(), $request->file('file')->getRealPath(), $this->list($request, $request->input('list_id')));

        return back()->with('import_report', $report)->with('success', "Import finished: {$report['created']} added, {$report['updated']} updated, {$report['rejected']} rejected, {$report['blocked']} blocked (opted out / suppressed), {$report['limit']} over the plan limit.");
    }

    public function withdraw(Request $request, int $contact)
    {
        $request->validate(['note' => ['nullable', 'string', 'max:200']]);
        $model = MarketingContact::ownedBy($request->user()->getAccountOwner())->findOrFail($contact);
        $this->contacts->withdraw($request->user(), $model, (string) $request->input('note', ''));

        return back()->with('success', $model->email . ' will not receive any more emails.');
    }

    public function history(Request $request, int $contact)
    {
        $model = MarketingContact::ownedBy($request->user()->getAccountOwner())->findOrFail($contact);

        return response()->json(MarketingConsentLog::where('contact_id', $model->id)->latest('id')->limit(50)
            ->get(['action', 'from_status', 'to_status', 'details', 'created_at']));
    }

    public function storeList(Request $request)
    {
        $owner = $request->user()->getAccountOwner();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('marketing_contact_lists', 'name')->where('owner_user_id', $owner->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        MarketingContactList::create($data + ['owner_user_id' => $owner->id]);

        return back()->with('success', 'List created.');
    }

    public function addToList(Request $request)
    {
        $owner = $request->user()->getAccountOwner();
        $data = $request->validate(['list_id' => ['required', 'integer'], 'contact_ids' => ['required', 'array', 'max:500'], 'contact_ids.*' => ['integer']]);
        $list = $this->list($request, $data['list_id']) ?? abort(404);
        $ids = MarketingContact::ownedBy($owner)->whereIn('id', $data['contact_ids'])->pluck('id');
        $list->contacts()->syncWithoutDetaching($ids->mapWithKeys(fn ($id) => [$id => ['created_at' => now()]])->all());

        return back()->with('success', $ids->count() . ' contact(s) added to ' . $list->name . '.');
    }

    private function list(Request $request, $id): ?MarketingContactList
    {
        return $id ? MarketingContactList::where('owner_user_id', $request->user()->getAccountOwner()->id)->findOrFail((int) $id) : null;
    }
}
