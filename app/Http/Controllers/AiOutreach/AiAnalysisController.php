<?php

namespace App\Http\Controllers\AiOutreach;

use App\Http\Controllers\Controller;
use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\OutboundEmail;
use App\Models\UserOffer;
use App\Services\Gmail\OutboundEmailService;
use App\Services\PlanFeatures;
use App\Services\Ai\Outreach\AnalysisLauncher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** One lead's analysis: audit evidence, offer match, draft editor and history. */
class AiAnalysisController extends Controller
{
    public function show(Request $request, int $analysis)
    {
        $item = $this->find($request, $analysis);
        $item->load(['lead', 'audit', 'recommendedOffer', 'batch']);

        $drafts = AiEmailDraft::where('owner_user_id', $item->owner_user_id)
            ->where('saved_lead_id', $item->saved_lead_id)
            ->with(['selectedOffer:id,title', 'createdBy:id,name'])
            ->latest('id')
            ->limit(20)
            ->get();

        $supporting = UserOffer::where('owner_user_id', $item->owner_user_id)
            ->whereIn('id', $item->supporting_offer_ids ?? [])->get();

        $draft = $drafts->firstWhere('is_current', true);
        $draft?->setRelation('lead', $item->lead);

        // Phase 3: Gmail send review data (only for approved drafts; nothing is sent here).
        $user = $request->user();
        $sendPreview = null;
        $outbound = collect();
        if ($draft && PlanFeatures::enabled($user, 'gmail_sending_enabled')) {
            $outbound = OutboundEmail::where('owner_user_id', $item->owner_user_id)
                ->where('draft_id', $draft->id)->latest('id')->limit(5)->get();
            if ($draft->review_status === AiEmailDraft::REVIEW_APPROVED) {
                $sendPreview = app(OutboundEmailService::class)->preview($user, $draft);
            }
        }

        // Phase 5: Resend option (opted-in contacts only; checked again when sending)
        $resendPreview = null;
        $resendSenders = collect();
        if ($draft && PlanFeatures::enabled($user, 'resend_single_sending_enabled')) {
            if ($outbound->isEmpty()) {
                $outbound = OutboundEmail::where('owner_user_id', $item->owner_user_id)
                    ->where('draft_id', $draft->id)->latest('id')->limit(5)->get();
            }
            if ($draft->review_status === AiEmailDraft::REVIEW_APPROVED) {
                $resendPreview = app(\App\Services\Resend\ResendSendService::class)->preview($user, $draft);
                $resendSenders = \App\Services\Resend\ResendDomainService::usableSenders($user->getAccountOwner());
            }
        }

        return view('user.ai-outreach.show', [
            'analysis' => $item,
            'draft' => $draft,
            'history' => $drafts,
            'supportingOffers' => $supporting,
            'sendPreview' => $sendPreview,
            'outbound' => $outbound,
            'resendPreview' => $resendPreview,
            'resendSenders' => $resendSenders,
        ]);
    }

    /** Retry a failed / quota-exhausted item from the stage where it stopped. */
    public function retry(Request $request, int $analysis)
    {
        $item = $this->find($request, $analysis);

        if (!in_array($item->status, AiLeadAnalysis::RETRYABLE_STATUSES, true)) {
            return back()->with('error', 'Only failed analyses can be retried.');
        }
        if (!$item->reopen()) {
            return back()->with('error', 'This lead is already being analysed.');
        }

        AnalysisLauncher::dispatchFor($item);

        return back()->with('success', 'Analysis queued again.');
    }

    /** Queue a new draft for a completed analysis (uses one AI email draft). */
    public function regenerate(Request $request, int $analysis)
    {
        $item = $this->find($request, $analysis);

        if ($item->status !== AiLeadAnalysis::STATUS_COMPLETED) {
            return back()->with('error', 'A new draft can be generated once the analysis has completed.');
        }

        $previousStage = $item->stage;
        $item->forceFill(['stage' => AiLeadAnalysis::STAGE_DRAFT])->save();
        if (!$item->reopen()) {
            $item->forceFill(['stage' => $previousStage])->save();
            return back()->with('error', 'This lead is already being analysed.');
        }

        AnalysisLauncher::dispatchFor($item);

        return back()->with('success', 'A new draft is being generated. Refresh in a minute.');
    }

    private function find(Request $request, int $id): AiLeadAnalysis
    {
        $item = AiLeadAnalysis::ownedBy($request->user()->getAccountOwner())->findOrFail($id);
        Gate::authorize('view', $item);

        return $item;
    }
}
