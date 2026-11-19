<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index()
    {
        $packages = Package::with('features')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.packages.index', compact('packages'));
    }

    /**
     * Store a newly created package.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'package_for' => 'required|in:user,company',
            'billing_type' => 'required|in:monthly,yearly,lifetime',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'max_users' => 'nullable|integer|min:1',
            'is_popular' => 'boolean',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'features' => 'nullable|array',
            'features.*.key' => 'nullable|string|max:100',
            'features.*.value' => 'nullable|string|max:255',
            'features.*.lock_message' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_popular'] = $request->boolean('is_popular');

        // Check for unique slug
        $slugCount = Package::where('slug', $validated['slug'])->count();
        if ($slugCount > 0) {
            $validated['slug'] = $validated['slug'] . '-' . ($slugCount + 1);
        }

        $package = Package::create($validated);

        $this->saveFeatures($package, (array) $request->input('features', []));

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    /**
     * Show the form for editing the specified package.
     */
    public function edit(Package $package)
    {
        $package->load('features');
        return response()->json($package);
    }

    /**
     * Update the specified package.
     */
    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'package_for' => 'required|in:user,company',
            'billing_type' => 'required|in:monthly,yearly,lifetime',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'max_users' => 'nullable|integer|min:1',
            'is_popular' => 'boolean',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'features' => 'nullable|array',
            'features.*.key' => 'nullable|string|max:100',
            'features.*.value' => 'nullable|string|max:255',
            'features.*.lock_message' => 'nullable|string|max:255',
        ]);

        $validated['is_popular'] = $request->boolean('is_popular');

        // Update slug only if name changed
        if ($package->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
            $slugCount = Package::where('slug', $validated['slug'])->where('id', '!=', $package->id)->count();
            if ($slugCount > 0) {
                $validated['slug'] = $validated['slug'] . '-' . ($slugCount + 1);
            }
        }

        $package->update($validated);

        // Update features - delete old and create new
        $package->features()->delete();

        $this->saveFeatures($package, (array) $request->input('features', []));

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    /**
     * Remove the specified package.
     */
    public function destroy(Package $package)
    {
        $package->features()->delete();
        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted successfully.');
    }

    /**
     * Save the feature rows of a package. A locked feature is not available to the package's
     * users; they see it in the sidebar with a lock and the lock message.
     */
    private function saveFeatures(Package $package, array $features): void
    {
        $locksReady = \App\Services\FeatureOverrides::installed();

        foreach ($features as $feature) {
            $key = trim((string) ($feature['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $row = [
                'package_id' => $package->id,
                'feature_key' => $key,
                'feature_value' => $feature['value'] ?? null,
                'is_unlimited' => !empty($feature['is_unlimited']),
            ];
            if ($locksReady) {
                $row['is_locked'] = !empty($feature['is_locked']);
                $row['lock_message'] = $row['is_locked'] ? (trim((string) ($feature['lock_message'] ?? '')) ?: null) : null;
            }

            PackageFeature::create($row);
        }
    }

    /**
     * Toggle package status.
     */
    public function toggleStatus(Package $package)
    {
        $package->status = $package->status === 'active' ? 'inactive' : 'active';
        $package->save();

        return response()->json([
            'success' => true,
            'status' => $package->status,
            'message' => 'Package status updated successfully.'
        ]);
    }
}
