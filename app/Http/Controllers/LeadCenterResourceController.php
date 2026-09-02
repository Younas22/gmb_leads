<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\LeadCenterMessageTemplate;
use App\Models\LeadCenterPrompt;
use App\Models\LeadCenterTargetLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The outreach "playbook" behind Lead Center — saved target locations, prompts and
 * message templates so the person doing outreach always has this reference data handy.
 */
class LeadCenterResourceController extends Controller
{
    private function ownerUser()
    {
        $user = Auth::user();
        return $user->isTeamMember() ? $user->company : $user;
    }

    public function index()
    {
        $ownerId = $this->ownerUser()->id;

        $targetLocations = LeadCenterTargetLocation::where('user_id', $ownerId)
            ->with(['countryRelation', 'stateRelation', 'cityRelation'])
            ->latest()
            ->get();

        $prompts = LeadCenterPrompt::where('user_id', $ownerId)->latest()->get();
        $templates = LeadCenterMessageTemplate::where('user_id', $ownerId)->latest()->get();
        $countries = Country::orderBy('name')->get();

        return view('user.lead-center.resources', compact('targetLocations', 'prompts', 'templates', 'countries'));
    }

    // ===== Targeted Locations =====

    public function storeLocation(Request $request)
    {
        $request->validate([
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$request->country_id && !$request->state_id && !$request->city_id && !$request->notes) {
            return response()->json(['success' => false, 'message' => 'Please choose a location or add a note.'], 422);
        }

        $location = LeadCenterTargetLocation::create([
            'user_id' => $this->ownerUser()->id,
            'country_id' => $request->country_id ?: null,
            'state_id' => $request->state_id ?: null,
            'city_id' => $request->city_id ?: null,
            'notes' => $request->notes,
        ]);

        $location->load(['countryRelation', 'stateRelation', 'cityRelation']);

        return response()->json(['success' => true, 'location' => $location]);
    }

    public function destroyLocation($id)
    {
        $location = LeadCenterTargetLocation::where('user_id', $this->ownerUser()->id)->findOrFail($id);
        $location->delete();

        return response()->json(['success' => true]);
    }

    // ===== Prompts =====

    public function storePrompt(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string|max:5000',
        ]);

        $prompt = LeadCenterPrompt::create([
            'user_id' => $this->ownerUser()->id,
            'title' => trim($request->title),
            'content' => trim($request->content),
        ]);

        return response()->json(['success' => true, 'prompt' => $prompt]);
    }

    public function destroyPrompt($id)
    {
        $prompt = LeadCenterPrompt::where('user_id', $this->ownerUser()->id)->findOrFail($id);
        $prompt->delete();

        return response()->json(['success' => true]);
    }

    // ===== Message Templates =====

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string|max:5000',
        ]);

        $template = LeadCenterMessageTemplate::create([
            'user_id' => $this->ownerUser()->id,
            'title' => trim($request->title),
            'content' => trim($request->content),
        ]);

        return response()->json(['success' => true, 'template' => $template]);
    }

    public function destroyTemplate($id)
    {
        $template = LeadCenterMessageTemplate::where('user_id', $this->ownerUser()->id)->findOrFail($id);
        $template->delete();

        return response()->json(['success' => true]);
    }
}
