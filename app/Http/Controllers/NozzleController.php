<?php

namespace App\Http\Controllers;

use App\Models\Nozzle;
use App\Models\Tank;
use Illuminate\Http\Request;

class NozzleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Nozzle::with('tank.product');

        if ($request->has('tank_id')) {
            $query->where('tank_id', $request->tank_id);
        }

        // Scoped to filling station via BelongsToFillingStation trait global scope (X-Filling-Station-Id header)

        $nozzles = $query->orderBy('name')->get();
        return response()->json($nozzles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tank_id' => 'required|exists:tanks,id',
            'filling_station_id' => 'nullable|integer|exists:filling_stations,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Active,Inactive',
            'reading' => 'nullable|numeric|min:0',
            'type' => 'nullable|string|max:255',
            'dispenser_type' => 'nullable|string|max:255',
            'is_online' => 'nullable|boolean',
        ]);

        // Bypass Tank global scope to read the tank's actual filling_station_id
        $tank = Tank::withoutGlobalScope('filling_station')->find($validated['tank_id']);
        $tankStationId = $tank?->filling_station_id;

        // Fallback to header or request body
        $headerStationId = $request->header('X-Filling-Station-Id');
        $bodyStationId = $validated['filling_station_id'] ?? null;
        $fillingStationId = $tankStationId
            ?? (is_numeric($headerStationId) ? (int) $headerStationId : null)
            ?? $bodyStationId;

        
        // Reject 0 or null — it means no valid station was resolved
        if (!$fillingStationId) {
            return response()->json([
                'message' => 'No valid filling station could be determined. Please select a filling station in the top bar and ensure your tank belongs to a station.',
                'debug' => [
                    'tank_id' => $validated['tank_id'],
                    'tank_found' => !!$tank,
                    'tank_station_id' => $tankStationId,
                    'header_station_id' => $headerStationId,
                    'body_station_id' => $bodyStationId,
                ]
            ], 422);
        }

        // Ensure the selected tank belongs to the resolved filling station
        if ($tank && $tank->filling_station_id != $fillingStationId) {
            return response()->json(['message' => 'The selected tank does not belong to your filling station.'], 422);
        }

        $validated['filling_station_id'] = $fillingStationId;
        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();
        $validated['status'] = $validated['status'] ?? 'Active';
        $validated['reading'] = $validated['reading'] ?? 0;
        $validated['is_online'] = $validated['is_online'] ?? false;

        $nozzle = Nozzle::create($validated);
        return response()->json($nozzle->load('tank.product'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $nozzle = Nozzle::with('tank.product')->findOrFail($id);
        return response()->json($nozzle);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $nozzle = Nozzle::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'tank_id' => 'sometimes|required|exists:tanks,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Active,Inactive',
            'reading' => 'nullable|numeric|min:0',
            'type' => 'nullable|string|max:255',
            'dispenser_type' => 'nullable|string|max:255',
            'is_online' => 'nullable|boolean',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['modified_at'] = now();

        $nozzle->update($validated);
        return response()->json($nozzle->load('tank.product'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $nozzle = Nozzle::findOrFail($id);
        $nozzle->delete();

        return response()->json(['message' => 'Nozzle deleted successfully']);
    }
}
