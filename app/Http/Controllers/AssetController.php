<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'location', 'creator', 'lastModifier']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }

        // Filter by location
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $assets = $query->orderBy('created_at', 'desc')->get();
        return response()->json($assets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_tag' => 'nullable|string|max:255|unique:assets',
            'serial_number' => 'nullable|string|max:255',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'condition' => 'nullable|string|in:Excellent,Good,Fair,Poor',
            'status' => 'nullable|string|in:Active,Inactive,Disposed,Under Maintenance',
            'warranty_expiry' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();
        $validated['status'] = $validated['status'] ?? 'Active';
        $validated['condition'] = $validated['condition'] ?? 'Good';

        $asset = Asset::create($validated);
        return response()->json($asset->load(['category', 'location']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $asset = Asset::with(['category', 'location', 'creator', 'lastModifier'])->findOrFail($id);
        return response()->json($asset);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'asset_tag' => 'nullable|string|max:255|unique:assets,asset_tag,' . $id,
            'serial_number' => 'nullable|string|max:255',
            'asset_category_id' => 'sometimes|required|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'condition' => 'nullable|string|in:Excellent,Good,Fair,Poor',
            'status' => 'nullable|string|in:Active,Inactive,Disposed,Under Maintenance',
            'warranty_expiry' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();

        $asset->update($validated);
        return response()->json($asset->load(['category', 'location']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted successfully']);
    }

    /**
     * Get asset statistics
     */
    public function statistics()
    {
        $stats = [
            'total_assets' => Asset::count(),
            'active_assets' => Asset::where('status', 'Active')->count(),
            'inactive_assets' => Asset::where('status', 'Inactive')->count(),
            'under_maintenance' => Asset::where('status', 'Under Maintenance')->count(),
            'total_value' => Asset::sum('current_value'),
            'by_category' => Asset::selectRaw('asset_category_id, COUNT(*) as count')
                ->with('category:id,name')
                ->groupBy('asset_category_id')
                ->get(),
            'by_condition' => Asset::selectRaw('condition, COUNT(*) as count')
                ->groupBy('condition')
                ->get(),
        ];

        return response()->json($stats);
    }
}