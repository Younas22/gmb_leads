<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\LeadCenterLead;
use App\Models\LeadCenterMessageTemplate;
use App\Models\LeadCenterPrompt;
use App\Models\LeadCenterTargetLocation;
use App\Models\State;
use App\Http\Controllers\Concerns\ResolvesLeadCenterOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The outreach "playbook" behind Lead Center — saved target locations, prompts and
 * message templates so the person doing outreach always has this reference data handy.
 */
class LeadCenterResourceController extends Controller
{
    use ResolvesLeadCenterOwner;

    public function index()
    {
        $ownerId = $this->leadCenterOwner()->id;

        $targetLocations = LeadCenterTargetLocation::where('user_id', $ownerId)
            ->with(['countryRelation', 'stateRelation', 'cityRelation'])
            ->latest()
            ->get();

        $prompts = LeadCenterPrompt::where('user_id', $ownerId)->latest()->get();
        $templates = LeadCenterMessageTemplate::where('user_id', $ownerId)->latest()->get();
        $countries = Country::orderBy('name')->get();

        return view('user.lead-center.resources', array_merge(
            compact('targetLocations', 'prompts', 'templates', 'countries'),
            $this->leadCenterAccessContext()
        ));
    }

    /**
     * Feed for the full-screen coverage map: targeted locations (blue), lead locations
     * still pending outreach (orange), and lead locations with any progress — connected,
     * responded, follow-up, converted or closed (green).
     */
    public function mapData()
    {
        $ownerId = $this->leadCenterOwner()->id;

        $targeted = LeadCenterTargetLocation::where('user_id', $ownerId)
            ->with(['countryRelation', 'stateRelation', 'cityRelation'])
            ->get()
            ->map(fn ($loc) => $this->buildMapMarker($loc->cityRelation, $loc->stateRelation, $loc->countryRelation, $loc->notes, null))
            ->filter()
            ->values();

        $nonPendingStatuses = array_values(array_diff(
            array_keys(LeadCenterLead::statusLabels()),
            [LeadCenterLead::STATUS_PENDING]
        ));

        return response()->json([
            'targeted' => $targeted,
            'pending' => $this->groupedLeadMarkers($ownerId, [LeadCenterLead::STATUS_PENDING]),
            'active' => $this->groupedLeadMarkers($ownerId, $nonPendingStatuses),
        ]);
    }

    private function groupedLeadMarkers(int $ownerId, array $statuses)
    {
        return LeadCenterLead::where('user_id', $ownerId)
            ->whereIn('status', $statuses)
            ->whereNotNull('country_id')
            ->select('country_id', 'state_id', 'city_id')
            ->selectRaw('count(*) as cnt')
            ->groupBy('country_id', 'state_id', 'city_id')
            ->with(['countryRelation', 'stateRelation', 'cityRelation'])
            ->get()
            ->map(fn ($row) => $this->buildMapMarker($row->cityRelation, $row->stateRelation, $row->countryRelation, null, (int) $row->cnt))
            ->filter()
            ->values();
    }

    private function buildMapMarker($city, $state, $country, ?string $notes, ?int $count): ?array
    {
        $point = $city ?: ($state ?: $country);
        if (!$point || $point->latitude === null || $point->longitude === null) {
            return null;
        }

        $label = collect([$city->name ?? null, $state->name ?? null, $country->name ?? null])->filter()->implode(', ');

        return [
            'lat' => (float) $point->latitude,
            'lng' => (float) $point->longitude,
            'label' => $label !== '' ? $label : 'Unknown location',
            'notes' => $notes,
            'count' => $count,
            // So the frontend can link the pin straight into a filtered Lead Center view.
            'country_id' => $country->id ?? null,
            'state_id' => $state->id ?? null,
            'city_id' => $city->id ?? null,
        ];
    }

    /**
     * Look up a Country/State/City's administrative boundary as GeoJSON, so the map can
     * draw an outline instead of just a pin. We only store point coordinates ourselves,
     * so this proxies OpenStreetMap's Nominatim search (server-side, with a proper
     * User-Agent and long caching — boundaries never change — to stay well within its
     * usage policy) rather than bundling a multi-GB world boundary dataset.
     */
    public function boundary(Request $request)
    {
        $request->validate([
            'type' => 'required|in:country,state,city',
            'id' => 'required|integer',
        ]);

        $cacheKey = "lc_boundary_{$request->type}_{$request->id}";

        $geojson = Cache::remember($cacheKey, now()->addDays(30), function () use ($request) {
            return $this->fetchBoundaryFromNominatim($request->type, (int) $request->id);
        });

        if (!$geojson) {
            return response()->json(['success' => false, 'message' => 'No boundary outline found for that location.'], 404);
        }

        return response()->json(['success' => true, 'geojson' => $geojson]);
    }

    private function fetchBoundaryFromNominatim(string $type, int $id): ?array
    {
        $query = null;
        $countryCode = null;

        if ($type === 'country') {
            $country = Country::find($id);
            if (!$country) return null;
            $query = $country->name;
            $countryCode = strtolower($country->iso2 ?? '');
        } elseif ($type === 'state') {
            $state = State::with('country')->find($id);
            if (!$state) return null;
            $query = collect([$state->name, $state->country->name ?? null])->filter()->implode(', ');
            $countryCode = strtolower($state->country->iso2 ?? '');
        } else {
            $city = City::with(['state', 'country'])->find($id);
            if (!$city) return null;
            $query = collect([$city->name, $city->state->name ?? null, $city->country->name ?? null])->filter()->implode(', ');
            $countryCode = strtolower($city->country->iso2 ?? '');
        }

        if (!$query) {
            return null;
        }

        // Large countries' full-detail coastline polygons can be several MB (and slow to both
        // fetch and render) — simplify more aggressively the bigger/coarser the boundary is.
        $polygonThreshold = match ($type) {
            'country' => 0.01,
            'state' => 0.005,
            default => 0.001,
        };

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'GMBLeadsApp/1.0 (Lead Center coverage map; ' . config('app.url') . ')',
            ])->timeout(20)->get('https://nominatim.openstreetmap.org/search', array_filter([
                'q' => $query,
                'format' => 'json',
                'polygon_geojson' => 1,
                'polygon_threshold' => $polygonThreshold,
                'limit' => 1,
                'countrycodes' => $countryCode ?: null,
            ]));

            if (!$response->ok()) {
                return null;
            }

            $results = $response->json();

            return $results[0]['geojson'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
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
            'user_id' => $this->leadCenterOwner()->id,
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
        $location = LeadCenterTargetLocation::where('user_id', $this->leadCenterOwner()->id)->findOrFail($id);
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
            'user_id' => $this->leadCenterOwner()->id,
            'title' => trim($request->title),
            'content' => trim($request->content),
        ]);

        return response()->json(['success' => true, 'prompt' => $prompt]);
    }

    public function destroyPrompt($id)
    {
        $prompt = LeadCenterPrompt::where('user_id', $this->leadCenterOwner()->id)->findOrFail($id);
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
            'user_id' => $this->leadCenterOwner()->id,
            'title' => trim($request->title),
            'content' => trim($request->content),
        ]);

        return response()->json(['success' => true, 'template' => $template]);
    }

    public function destroyTemplate($id)
    {
        $template = LeadCenterMessageTemplate::where('user_id', $this->leadCenterOwner()->id)->findOrFail($id);
        $template->delete();

        return response()->json(['success' => true]);
    }
}
