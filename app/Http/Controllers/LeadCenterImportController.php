<?php

namespace App\Http\Controllers;

use App\Models\SavedLead;
use App\Services\LeadCenterImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadCenterImportController extends Controller
{
    private LeadCenterImportService $service;

    public function __construct(LeadCenterImportService $service)
    {
        $this->service = $service;
    }

    private function ownerUser()
    {
        $user = Auth::user();
        return $user->isTeamMember() ? $user->company : $user;
    }

    /**
     * Get allowed user IDs for reading "My Leads" — mirrors LeadsController's team-wide read scope.
     */
    private function getAllowedUserIds($user)
    {
        $accountOwner = $user->isTeamMember() ? $user->company : $user;
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $userIds = array_merge($userIds, $accountOwner->teamMembers()->pluck('id')->toArray());
        }
        return $userIds;
    }

    /**
     * Parse + validate a CSV file or pasted text and return a preview — nothing is saved yet.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'source' => 'required|in:csv,paste',
            'csv_file' => 'required_if:source,csv|file|mimes:csv,txt|max:10240',
            'pasted_text' => 'required_if:source,paste|string',
        ]);

        try {
            if ($request->source === 'csv') {
                $raw = file_get_contents($request->file('csv_file')->getRealPath());
                if ($raw === false) {
                    return response()->json(['success' => false, 'message' => 'Could not read the uploaded file.'], 422);
                }
            } else {
                $raw = (string) $request->input('pasted_text');
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'The file could not be processed. Please check it is a valid CSV.'], 422);
        }

        $rows = $this->service->parseRawText($raw);

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No leads were found. Make sure each line has "Company Name,Website".'], 422);
        }

        $result = $this->service->analyze($this->ownerUser()->id, $rows);

        return response()->json([
            'success' => true,
            'valid' => $result['valid'],
            'valid_count' => count($result['valid']),
            'duplicates' => $result['duplicates'],
            'duplicate_count' => count($result['duplicates']),
            'invalid' => $result['invalid'],
            'invalid_count' => count($result['invalid']),
            'total' => $result['total'],
        ]);
    }

    /**
     * Persist a previously-previewed batch of rows, tagging every one with the chosen location.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.company_name' => 'required|string|max:255',
            'rows.*.website' => 'nullable|string|max:500',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        if (count($request->rows) > LeadCenterImportService::MAX_ROWS) {
            return response()->json(['success' => false, 'message' => 'Too many rows in a single import (max ' . LeadCenterImportService::MAX_ROWS . ').'], 422);
        }

        $result = $this->service->import(
            $this->ownerUser()->id,
            $request->rows,
            $request->country_id ?: null,
            $request->state_id ?: null,
            $request->city_id ?: null
        );

        return response()->json([
            'success' => true,
            'message' => "{$result['imported']} lead(s) imported.",
            'imported' => $result['imported'],
            'skipped_duplicate' => $result['skipped_duplicate'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * Move/copy selected "My Leads" records into Lead Center. Called from the /user/leads bulk bar
     * and also usable directly from the Lead Center page.
     */
    public function addFromLeads(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer',
        ]);

        $user = Auth::user();
        $allowedUserIds = $this->getAllowedUserIds($user);

        $savedLeads = SavedLead::whereIn('user_id', $allowedUserIds)
            ->whereIn('id', $request->lead_ids)
            ->get();

        if ($savedLeads->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching leads found'], 404);
        }

        $result = $this->service->importFromSavedLeads($this->ownerUser()->id, $savedLeads);

        $message = "{$result['imported']} lead(s) added to Lead Center.";
        if ($result['skipped_duplicate'] > 0) {
            $message .= " {$result['skipped_duplicate']} skipped because they already exist in Lead Center.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'imported' => $result['imported'],
            'skipped_duplicate' => $result['skipped_duplicate'],
        ]);
    }
}
