<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = AssetCategory::with(['creator', 'lastModifier'])->orderBy('name')->get();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name',
            'depreciation_account' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;

        $category = AssetCategory::create($validated);
        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $category = AssetCategory::with(['creator', 'lastModifier'])->findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $category = AssetCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:asset_categories,name,' . $id,
            'depreciation_account' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;

        $category->update($validated);
        $category->load(['creator', 'lastModifier']);
        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $category = AssetCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Asset category deleted successfully']);
    }
}
