<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLeadCenterOwner;
use App\Models\Country;
use App\Models\LeadCenterFolder;
use App\Models\LeadCenterLead;
use Illuminate\Http\Request;

class LeadCenterController extends Controller
{
    use ResolvesLeadCenterOwner;

    public function index(Request $request)
    {
        $ownerId = $this->leadCenterOwner()->id;

        $search = trim((string) $request->get('search', ''));
        $status = $request->get('status');
        $channel = $request->get('channel');
        $folderId = $request->get('folder_id');
        $countryId = $request->get('country_id');
        $stateId = $request->get('state_id');
        $cityId = $request->get('city_id');
        $perPage = $request->get('per_page', 20);
        $perPage = $perPage === 'all' ? PHP_INT_MAX : max(1, (int) $perPage);

        $query = LeadCenterLead::where('user_id', $ownerId);

        if ($search !== '') {
            // Also matches inside the saved Contact Channel links (Facebook, WhatsApp, Instagram,
            // LinkedIn, Contact Form — not just the dedicated Email column), so a search for a
            // handle or link finds the lead regardless of which channel it was saved under.
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhereRaw('contact_links LIKE ?', ["%{$search}%"]);
            });
        }

        if ($channel && in_array($channel, LeadCenterLead::CONTACT_CHANNELS, true)) {
            $query->where(function ($q) use ($channel) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(contact_links, ?)) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(contact_links, ?)) != ''", ['$.' . $channel, '$.' . $channel]);
                if ($channel === 'email') {
                    $q->orWhere(function ($q2) {
                        $q2->whereNotNull('email')->where('email', '!=', '');
                    });
                }
            });
        }

        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        if ($stateId) {
            $query->where('state_id', $stateId);
        }
        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        // Status summary cards — computed from the search/location-filtered set (not the status filter itself),
        // via a single grouped query so counts never require loading rows into memory.
        $statusCounts = (clone $query)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $stats = ['total' => (int) $statusCounts->sum()];
        foreach (LeadCenterLead::statusLabels() as $key => $label) {
            $stats[$key] = (int) ($statusCounts[$key] ?? 0);
        }

        if ($status) {
            $query->where('status', $status);
        }
        if ($folderId === 'unfiled') {
            $query->whereNull('folder_id');
        } elseif ($folderId) {
            $query->where('folder_id', $folderId);
        }

        $leads = $query->with(['folder', 'countryRelation', 'stateRelation', 'cityRelation'])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $folders = LeadCenterFolder::where('user_id', $ownerId)
            ->withCount('leads')
            ->orderBy('name')
            ->get();

        $activeFolder = $folderId && $folderId !== 'unfiled' ? $folders->firstWhere('id', (int) $folderId) : null;
        $unfiledCount = LeadCenterLead::where('user_id', $ownerId)->whereNull('folder_id')->count();

        $countries = Country::orderBy('name')->get();

        return view('user.lead-center.index', array_merge(compact(
            'leads', 'folders', 'activeFolder', 'stats', 'countries', 'unfiledCount',
            'search', 'status', 'channel', 'folderId', 'countryId', 'stateId', 'cityId'
        ), $this->leadCenterAccessContext()));
    }

    public function updateStatus(Request $request, $id)
    {
        $ownerId = $this->leadCenterOwner()->id;
        $lead = LeadCenterLead::where('user_id', $ownerId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(LeadCenterLead::statusLabels())),
        ]);

        $lead->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    /**
     * Set/fix a lead's Country/State/City — mainly for leads that came from "My Leads" records
     * saved via the browser extension, which don't capture structured location data.
     */
    public function updateLocation(Request $request, $id)
    {
        $ownerId = $this->leadCenterOwner()->id;
        $lead = LeadCenterLead::where('user_id', $ownerId)->findOrFail($id);

        $request->validate([
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $lead->update([
            'country_id' => $request->country_id ?: null,
            'state_id' => $request->state_id ?: null,
            'city_id' => $request->city_id ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'Location updated successfully']);
    }

    public function destroy($id)
    {
        $ownerId = $this->leadCenterOwner()->id;
        $lead = LeadCenterLead::where('user_id', $ownerId)->findOrFail($id);
        $lead->delete();

        return response()->json(['success' => true, 'message' => 'Lead removed from Lead Center']);
    }

    public function bulkAction(Request $request)
    {
        $ownerId = $this->leadCenterOwner()->id;

        $request->validate([
            'action' => 'required|in:delete,update_status,move_to_folder',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer',
            'status' => 'required_if:action,update_status|in:' . implode(',', array_keys(LeadCenterLead::statusLabels())),
            'folder_id' => 'nullable|integer',
        ]);

        $leadIds = $request->lead_ids;
        $action = $request->action;

        // Scope strictly to leads this account owns — the where() below silently drops any id that doesn't belong.
        $ownedQuery = LeadCenterLead::where('user_id', $ownerId)->whereIn('id', $leadIds);
        $ownedCount = (clone $ownedQuery)->count();

        if ($ownedCount === 0) {
            return response()->json(['success' => false, 'message' => 'No matching leads found'], 404);
        }

        if ($action === 'delete') {
            $ownedQuery->delete();
            $message = $ownedCount . ' lead(s) removed from Lead Center';
        } elseif ($action === 'update_status') {
            $ownedQuery->update(['status' => $request->status]);
            $message = $ownedCount . ' lead(s) updated to "' . LeadCenterLead::statusLabels()[$request->status] . '"';
        } else { // move_to_folder
            $folderId = $request->folder_id ?: null;
            if ($folderId) {
                // Ensure the target folder actually belongs to this account
                $folder = LeadCenterFolder::where('user_id', $ownerId)->find($folderId);
                if (!$folder) {
                    return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
                }
            }
            $ownedQuery->update(['folder_id' => $folderId]);
            $message = $ownedCount . ' lead(s) moved';
        }

        return response()->json(['success' => true, 'message' => $message, 'count' => $ownedCount]);
    }
}
