<?php

namespace App\Http\Controllers\AiOutreach;

use App\Http\Controllers\Controller;
use App\Models\AiEmailDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Review actions on drafts. Phase 2 never sends email: "approve" only marks
 * the draft as ready for the user to copy (and for Phase 3 to send).
 */
class AiDraftController extends Controller
{
    public function update(Request $request, int $draft)
    {
        $model = $this->find($request, $draft);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        if ($model->review_status === AiEmailDraft::REVIEW_DISCARDED) {
            return back()->with('error', 'Restore the draft before editing it.');
        }

        $changed = $data['subject'] !== $model->subject || str_replace("\r\n", "\n", $data['body']) !== $model->body;
        $model->fill(['subject' => $data['subject'], 'body' => str_replace("\r\n", "\n", $data['body'])]);
        if ($changed) {
            $model->user_edited = true;
            // An edited draft needs a fresh approval
            if ($model->review_status === AiEmailDraft::REVIEW_APPROVED) {
                $model->review_status = AiEmailDraft::REVIEW_DRAFT;
                $model->approved_at = null;
                $model->approved_by_user_id = null;
            }
        }
        $model->save();

        return $this->respond($request, $model, 'Draft saved.');
    }

    public function approve(Request $request, int $draft)
    {
        $model = $this->find($request, $draft);
        $model->forceFill([
            'review_status' => AiEmailDraft::REVIEW_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $request->user()->id,
        ])->save();

        return $this->respond($request, $model, 'Draft approved. Nothing is sent automatically.');
    }

    /**
     * Approve the current draft of every selected analysis at once (only drafts waiting
     * for review; discarded and already approved ones are left alone). Nothing is sent.
     */
    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'analysis_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'analysis_ids.*' => ['integer'],
        ], ['analysis_ids.required' => 'Select at least one lead.']);

        $owner = $request->user()->getAccountOwner();
        $drafts = AiEmailDraft::ownedBy($owner)
            ->where('is_current', true)
            ->where('review_status', AiEmailDraft::REVIEW_DRAFT)
            ->whereIn('analysis_id', \App\Models\AiLeadAnalysis::ownedBy($owner)->whereIn('id', $data['analysis_ids'])->select('id'))
            ->get();

        foreach ($drafts as $model) {
            $model->forceFill([
                'review_status' => AiEmailDraft::REVIEW_APPROVED,
                'approved_at' => now(),
                'approved_by_user_id' => $request->user()->id,
            ])->save();
        }

        $selected = count(array_unique($data['analysis_ids']));
        $skipped = $selected - $drafts->count();
        $message = $drafts->isEmpty()
            ? 'Nothing to approve: the selected leads have no email draft waiting for review.'
            : $drafts->count() . ' email draft(s) approved. Nothing is sent automatically.'
                . ($skipped > 0 ? " {$skipped} skipped (no draft yet, already approved or discarded)." : '');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'approved' => $drafts->count(), 'skipped' => $skipped, 'message' => $message]);
        }

        return back()->with($drafts->isEmpty() ? 'error' : 'success', $message);
    }

    public function discard(Request $request, int $draft)
    {
        $model = $this->find($request, $draft);
        $model->forceFill([
            'review_status' => AiEmailDraft::REVIEW_DISCARDED,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ])->save();

        return $this->respond($request, $model, 'Draft discarded.');
    }

    public function restore(Request $request, int $draft)
    {
        $model = $this->find($request, $draft);
        $model->forceFill(['review_status' => AiEmailDraft::REVIEW_DRAFT])->save();

        return $this->respond($request, $model, 'Draft restored.');
    }

    private function respond(Request $request, AiEmailDraft $draft, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'review_status' => $draft->review_status]);
        }

        return back()->with('success', $message);
    }

    private function find(Request $request, int $id): AiEmailDraft
    {
        $draft = AiEmailDraft::ownedBy($request->user()->getAccountOwner())->findOrFail($id);
        Gate::authorize('update', $draft);

        return $draft;
    }
}
