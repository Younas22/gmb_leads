<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminApiKey;
use Illuminate\Http\Request;

class ApiUsageController extends Controller
{
    public function index()
    {
        $apiKeys = AdminApiKey::orderBy('created_at', 'desc')->get();

        $totalTextSearchCalls = $apiKeys->sum('text_search_count');
        $totalDetailsCalls = $apiKeys->sum('details_count');
        $totalTextSearchCost = $apiKeys->sum('text_search_total_cost');
        $totalDetailsCost = $apiKeys->sum('details_total_cost');
        $totalCost = $totalTextSearchCost + $totalDetailsCost;
        $totalCalls = $totalTextSearchCalls + $totalDetailsCalls;

        return view('admin.api-usage.index', compact(
            'apiKeys',
            'totalTextSearchCalls',
            'totalDetailsCalls',
            'totalTextSearchCost',
            'totalDetailsCost',
            'totalCost',
            'totalCalls'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key_name' => 'required|string|max:100',
            'api_key' => 'required|string',
            'text_search_price' => 'required|numeric|min:0',
            'details_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        AdminApiKey::create($validated);

        return redirect()->route('admin.api-usage.index')->with('success', 'API Key added successfully.');
    }

    public function edit(AdminApiKey $apiKey)
    {
        return response()->json([
            'id' => $apiKey->id,
            'key_name' => $apiKey->key_name,
            'api_key' => $apiKey->api_key,
            'text_search_price' => $apiKey->text_search_price,
            'details_price' => $apiKey->details_price,
            'text_search_count' => $apiKey->text_search_count,
            'details_count' => $apiKey->details_count,
            'status' => $apiKey->status,
        ]);
    }

    public function update(Request $request, AdminApiKey $apiKey)
    {
        $validated = $request->validate([
            'key_name' => 'required|string|max:100',
            'api_key' => 'nullable|string',
            'text_search_price' => 'required|numeric|min:0',
            'details_price' => 'required|numeric|min:0',
            'text_search_count' => 'required|integer|min:0',
            'details_count' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // Recalculate total costs
        $validated['text_search_total_cost'] = $validated['text_search_count'] * $validated['text_search_price'];
        $validated['details_total_cost'] = $validated['details_count'] * $validated['details_price'];

        // Only update api_key if provided
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $apiKey->update($validated);

        return redirect()->route('admin.api-usage.index')->with('success', 'API Key updated successfully.');
    }

    public function destroy(AdminApiKey $apiKey)
    {
        $apiKey->delete();
        return redirect()->route('admin.api-usage.index')->with('success', 'API Key deleted successfully.');
    }

    public function toggleStatus(AdminApiKey $apiKey)
    {
        $apiKey->status = $apiKey->status === 'active' ? 'inactive' : 'active';
        $apiKey->save();

        return response()->json([
            'success' => true,
            'status' => $apiKey->status,
            'message' => 'API Key status updated successfully.'
        ]);
    }

    public function resetCounts(AdminApiKey $apiKey)
    {
        $apiKey->update([
            'text_search_count' => 0,
            'details_count' => 0,
            'text_search_total_cost' => 0,
            'details_total_cost' => 0,
        ]);

        return redirect()->route('admin.api-usage.index')->with('success', 'API call counts reset successfully.');
    }
}
