<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LeadFolder;
use App\Models\SavedLead;

class FolderController extends Controller
{
    private function getAllowedUserIds($user)
    {
        $accountOwner = $user->isTeamMember() ? $user->company : $user;
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $userIds = array_merge($userIds, $accountOwner->teamMembers()->pluck('id')->toArray());
        }
        return $userIds;
    }

    private function ownerUser()
    {
        $user = Auth::user();
        return $user->isTeamMember() ? $user->company : $user;
    }

    public function index()
    {
        $folders = LeadFolder::where('user_id', $this->ownerUser()->id)
            ->withCount('leads')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'sometimes|string|max:30',
        ]);

        $folder = LeadFolder::create([
            'user_id' => $this->ownerUser()->id,
            'name'    => trim($request->name),
            'color'   => $request->input('color', 'blue'),
        ]);

        $folder->loadCount('leads');

        return response()->json($folder);
    }

    public function addLeads(Request $request, $id)
    {
        $request->validate(['lead_ids' => 'required|array', 'lead_ids.*' => 'integer']);

        $folder = LeadFolder::where('id', $id)
            ->where('user_id', $this->ownerUser()->id)
            ->firstOrFail();

        $allowedIds = SavedLead::whereIn('user_id', $this->getAllowedUserIds(Auth::user()))
            ->whereIn('id', $request->lead_ids)
            ->pluck('id');

        $folder->leads()->syncWithoutDetaching($allowedIds);

        return response()->json(['success' => true, 'count' => $allowedIds->count()]);
    }

    public function removeLeads(Request $request, $id)
    {
        $request->validate(['lead_ids' => 'required|array', 'lead_ids.*' => 'integer']);

        $folder = LeadFolder::where('id', $id)
            ->where('user_id', $this->ownerUser()->id)
            ->firstOrFail();

        $folder->leads()->detach($request->lead_ids);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $folder = LeadFolder::where('id', $id)
            ->where('user_id', $this->ownerUser()->id)
            ->firstOrFail();

        $folder->delete();

        return response()->json(['success' => true]);
    }
}
