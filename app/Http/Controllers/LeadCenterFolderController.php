<?php

namespace App\Http\Controllers;

use App\Models\LeadCenterFolder;
use App\Http\Controllers\Concerns\ResolvesLeadCenterOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadCenterFolderController extends Controller
{
    use ResolvesLeadCenterOwner;

    public function index()
    {
        $folders = LeadCenterFolder::where('user_id', $this->leadCenterOwner()->id)
            ->withCount('leads')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'sometimes|string|max:30',
        ]);

        $ownerId = $this->leadCenterOwner()->id;
        $name = trim($request->name);

        $existing = LeadCenterFolder::where('user_id', $ownerId)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            $existing->loadCount('leads');
            return response()->json($existing);
        }

        $folder = LeadCenterFolder::create([
            'user_id' => $ownerId,
            'name' => $name,
            'color' => $request->input('color', 'blue'),
        ]);

        $folder->loadCount('leads');

        return response()->json($folder);
    }

    public function destroy($id)
    {
        $folder = LeadCenterFolder::where('id', $id)
            ->where('user_id', $this->leadCenterOwner()->id)
            ->firstOrFail();

        // Leads in the folder are NOT deleted — they simply become unfiled.
        $folder->leads()->update(['folder_id' => null]);
        $folder->delete();

        return response()->json(['success' => true]);
    }
}
