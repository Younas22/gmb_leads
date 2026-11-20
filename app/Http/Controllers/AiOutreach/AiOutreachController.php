<?php

namespace App\Http\Controllers\AiOutreach;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisBatch;
use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\AiUsageLog;
use App\Services\Ai\AiService;
use App\Services\Ai\Outreach\AnalysisLauncher;
use Illuminate\Http\Request;

/**
 * "AI Outreach": start analyses, follow progress, browse results and usage.
 * Everything is scoped to the account owner (owner + team members share).
 */
class AiOutreachController extends Controller
{
    public function __construct(private readonly AnalysisLauncher $launcher, private readonly AiService $ai)
    {
    }

    public const PER_PAGE_OPTIONS = ['10', '25', '50', '100', '200'];
    public const PER_PAGE_DEFAULT = '25';
    public const PER_PAGE_ALL_CAP = 1000;

    public function index(Request $request)
    {
        $owner = $request->user()->getAccountOwner();

        $request->validate([
            'status' => ['nullable', 'in:' . implode(',', array_keys(AiLeadAnalysis::STATUS_LABELS))],
            'review' => ['nullable', 'in:draft,approved,discarded,none'],
            'q' => ['nullable', 'string', 'max:100'],
            'folder' => ['nullable', 'integer'],
            'email' => ['nullable', 'in:has,missing'],
            'per_page' => ['nullable', 'in:' . implode(',', array_merge(self::PER_PAGE_OPTIONS, ['all']))],
        ]);
        // Rows per page (same idea as My Leads); "all" is capped so a huge account can't stall the page
        $perPage = $request->input('per_page', self::PER_PAGE_DEFAULT);
        $perPage = $perPage === 'all' ? self::PER_PAGE_ALL_CAP : (int) $perPage;
        $folders = \App\Models\LeadFolder::where('user_id', $owner->id)->orderBy('name')->get(['id', 'name', 'color']);

        $analyses = AiLeadAnalysis::ownedBy($owner)
            // Lead folders (same folders as My Leads)
            ->when($request->filled('folder'), fn ($q) => $q->whereIn('saved_lead_id', \Illuminate\Support\Facades\DB::table('lead_folder_items')->where('folder_id', $folders->firstWhere('id', (int) $request->folder)?->id ?? 0)->select('lead_id')))
            ->with(['lead:id,name,website,email', 'recommendedOffer:id,title', 'currentDraft'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            // Lead has an email / is missing one (email is stored on the lead, shared with My Leads)
            ->when($request->email === 'has', fn ($q) => $q->whereHas('lead', fn ($l) => $l->whereNotNull('email')->where('email', '!=', '')))
            ->when($request->email === 'missing', fn ($q) => $q->whereHas('lead', fn ($l) => $l->where(fn ($w) => $w->whereNull('email')->orWhere('email', ''))))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('lead', fn ($l) => $l->where('name', 'like', '%' . addcslashes($request->q, '%_\\') . '%')))
            ->when($request->filled('review'), function ($q) use ($request) {
                if ($request->review === 'none') {
                    $q->whereDoesntHave('drafts', fn ($d) => $d->where('is_current', true));
                } else {
                    $q->whereHas('drafts', fn ($d) => $d->where('is_current', true)->where('review_status', $request->review));
                }
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $batches = AiAnalysisBatch::where('owner_user_id', $owner->id)->latest('id')->limit(5)->get();

        return view('user.ai-outreach.index', [
            'analyses' => $analyses,
            'batches' => $batches,
            'usage' => $this->usageData($request),
            'aiAvailable' => $this->ai->isAvailable(),
            'folders' => $folders,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'perPageDefault' => self::PER_PAGE_DEFAULT,
        ]);
    }

    /** Put the selected analysed leads into a lead folder (existing, or a new one by name). */
    public function addToFolder(Request $request)
    {
        $owner = $request->user()->getAccountOwner();
        $data = $request->validate([
            'analysis_ids' => ['required', 'array', 'min:1', 'max:500'],
            'analysis_ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', 'required_without:new_folder'],
            'new_folder' => ['nullable', 'string', 'max:100', 'required_without:folder_id'],
        ], ['analysis_ids.required' => 'Select at least one lead.', 'folder_id.required_without' => 'Choose a folder or type a new folder name.']);

        $folder = filled($data['new_folder'] ?? null)
            ? \App\Models\LeadFolder::firstOrCreate(['user_id' => $owner->id, 'name' => trim($data['new_folder'])], ['color' => 'blue'])
            : \App\Models\LeadFolder::where('user_id', $owner->id)->findOrFail((int) $data['folder_id']);
        $leadIds = AiLeadAnalysis::ownedBy($owner)->whereIn('id', $data['analysis_ids'])->pluck('saved_lead_id')->filter()->unique();
        $folder->leads()->syncWithoutDetaching($leadIds->all());

        return back()->with('success', $leadIds->count() . ' lead(s) added to "' . $folder->name . '".');
    }

    /** JSON: how many leads would be analysed and the quota impact. */
    public function estimate(Request $request)
    {
        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:500'],
            'lead_ids.*' => ['integer'],
        ]);

        return response()->json($this->launcher->estimate($request->user(), $data['lead_ids']));
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:500'],
            'lead_ids.*' => ['integer'],
        ]);

        $result = $this->launcher->start($request->user(), $data['lead_ids']);

        if (!$result['batch']) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'problems' => $result['estimate']['problems']], 422)
                : back()->with('error', implode(' ', $result['estimate']['problems']));
        }

        $url = route('user.ai-outreach.batch', $result['batch']);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'batch_id' => $result['batch']->id, 'queued' => $result['batch']->total_items, 'redirect' => $url])
            : redirect($url)->with('success', "{$result['batch']->total_items} lead(s) queued for AI analysis.");
    }

    public function batch(Request $request, int $batch)
    {
        $model = $this->findBatch($request, $batch);

        $items = $model->items()
            ->with(['lead:id,name,website,email', 'recommendedOffer:id,title', 'currentDraft'])
            ->orderBy('id')
            ->paginate(50);

        return view('user.ai-outreach.batch', [
            'batch' => $model,
            'items' => $items,
            'progress' => $model->progress(),
        ]);
    }

    /** JSON progress for polling. */
    public function batchStatus(Request $request, int $batch)
    {
        $model = $this->findBatch($request, $batch);

        return response()->json([
            'progress' => $model->progress(),
            'items' => $model->items()->select('id', 'status', 'website_status', 'stage')->orderBy('id')->limit(200)->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'status' => $i->status,
                    'status_label' => $i->statusLabel(),
                    'website_label' => $i->websiteLabel(),
                ]),
        ]);
    }

    public function retryBatchFailed(Request $request, int $batch)
    {
        $model = $this->findBatch($request, $batch);
        $count = 0;

        foreach ($model->items()->whereIn('status', AiLeadAnalysis::RETRYABLE_STATUSES)->get() as $item) {
            if ($item->reopen()) {
                AnalysisLauncher::dispatchFor($item);
                $count++;
            }
        }

        return back()->with('success', "{$count} analysis item(s) queued again.");
    }

    /** JSON usage for this month (also shown on the index page). */
    public function usage(Request $request)
    {
        return response()->json($this->usageData($request));
    }

    private function usageData(Request $request): array
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $quota = $this->ai->quota();
        $since = now()->startOfMonth();

        $features = [];
        foreach (config('ai.quota_features') as $feature => $key) {
            $logs = AiUsageLog::where('owner_user_id', $owner->id)->where('feature', $feature)->where('created_at', '>=', $since);
            $tokens = (clone $logs)->selectRaw('COALESCE(SUM(input_tokens),0) as i, COALESCE(SUM(output_tokens),0) as o, COALESCE(SUM(cache_read_tokens),0) as cr, COALESCE(SUM(cache_write_tokens),0) as cw')->first();

            $features[$feature] = [
                'used' => $quota->used($owner, $feature),
                'limit' => $quota->limit($user, $feature),
                'failed' => (clone $logs)->whereIn('status', [AiUsageLog::STATUS_FAILED, AiUsageLog::STATUS_REFUSED])->count(),
                'input_tokens' => (int) $tokens->i,
                'output_tokens' => (int) $tokens->o,
                'cache_read_tokens' => (int) $tokens->cr,
                'cache_write_tokens' => (int) $tokens->cw,
            ];
        }

        // Model of the active provider; prices are only configured for some models (else no estimate)
        $model = (fn ($st) => $st->providerConfig($st->activeProvider())['model'] ?? null)(app(\App\Services\Ai\AiSettings::class));
        $price = config("ai.pricing.{$model}");
        $cost = null;
        if ($price) {
            $in = array_sum(array_column($features, 'input_tokens'));
            $out = array_sum(array_column($features, 'output_tokens'));
            $cr = array_sum(array_column($features, 'cache_read_tokens'));
            $cw = array_sum(array_column($features, 'cache_write_tokens'));
            // Estimate at the configured model's list price. Requests served by a refusal
            // fallback model are billed at that model's rates, so treat this as approximate.
            $cost = round(($in * $price['input'] + $out * $price['output'] + $cr * ($price['cache_read'] ?? 0) + $cw * ($price['cache_write'] ?? 0)) / 1_000_000, 4);
        }

        return [
            'month' => $since->format('F Y'),
            'features' => $features,
            'estimated_cost_usd' => $cost,
            'drafts' => [
                'draft' => AiEmailDraft::ownedBy($owner)->where('is_current', true)->where('review_status', AiEmailDraft::REVIEW_DRAFT)->count(),
                'approved' => AiEmailDraft::ownedBy($owner)->where('is_current', true)->where('review_status', AiEmailDraft::REVIEW_APPROVED)->count(),
            ],
        ];
    }

    private function findBatch(Request $request, int $id): AiAnalysisBatch
    {
        return AiAnalysisBatch::where('owner_user_id', $request->user()->getAccountOwner()->id)->findOrFail($id);
    }
}
