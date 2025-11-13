<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Location::with(['creator', 'lastModifier']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $locations = $query->orderBy('name')->get();
        return response()->json($locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Active,Inactive',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();
        $validated['status'] = $validated['status'] ?? 'Active';

        $location = Location::create($validated);
        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $location = Location::with(['assets', 'creator', 'lastModifier'])->findOrFail($id);
        return response()->json($location);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations,code,' . $id,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Active,Inactive',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();

        $location->update($validated);
        return response()->json($location);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $location = Location::findOrFail($id);
        
        // Check if location has assets
        if ($location->assets()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location. It has associated assets.'
            ], 422);
        }

        $location->delete();
        return response()->json(['message' => 'Location deleted successfully']);
    }
}