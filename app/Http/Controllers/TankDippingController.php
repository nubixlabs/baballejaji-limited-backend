<?php

namespace App\Http\Controllers;

use App\Models\TankDipping;
use App\Models\Tank;
use Illuminate\Http\Request;

class TankDippingController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/tank-dippings",
     *   summary="Get all tank dippings",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tank_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="List of tank dippings")
     * )
     */
    public function index(Request $request)
    {
        $query = TankDipping::with(['tank.product']);

        if ($request->has('tank_id')) {
            $query->where('tank_id', $request->tank_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('dipping_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('dipping_date', '<=', $request->date_to);
        }

        $dippings = $query->orderBy('dipping_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json($dippings);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/tank-dippings/{id}",
     *   summary="Get tank dipping by ID",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank dipping details")
     * )
     */
    public function show(int $id)
    {
        $dipping = TankDipping::with(['tank.product'])->findOrFail($id);
        return response()->json($dipping);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/tank-dippings",
     *   summary="Create new tank dipping",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"tank_id","dipping_date"},
     *       @OA\Property(property="tank_id", type="integer"),
     *       @OA\Property(property="dipped_quantity", type="number"),
     *       @OA\Property(property="atg_quantity", type="number"),
     *       @OA\Property(property="dipping_date", type="string", format="date"),
     *       @OA\Property(property="notes", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Tank dipping created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'dipped_quantity' => 'nullable|numeric|min:0',
            'atg_quantity' => 'nullable|numeric|min:0',
            'dipping_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Get tank to calculate variance
        $tank = Tank::findOrFail($validated['tank_id']);
        
        // Calculate variance if both quantities are provided
        $variance = null;
        if (isset($validated['dipped_quantity']) && isset($validated['atg_quantity'])) {
            $variance = $validated['dipped_quantity'] - $validated['atg_quantity'];
        }

        $dipping = TankDipping::create([
            ...$validated,
            'variance' => $variance,
            'created_by' => $request->user()->id ?? null,
        ]);

        // Load relationships
        $dipping->load(['tank.product']);

        return response()->json($dipping, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/filling/tank-dippings/{id}",
     *   summary="Update tank dipping",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="tank_id", type="integer"),
     *       @OA\Property(property="dipped_quantity", type="number"),
     *       @OA\Property(property="atg_quantity", type="number"),
     *       @OA\Property(property="dipping_date", type="string", format="date"),
     *       @OA\Property(property="notes", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Tank dipping updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $dipping = TankDipping::findOrFail($id);

        $validated = $request->validate([
            'tank_id' => 'sometimes|exists:tanks,id',
            'dipped_quantity' => 'nullable|numeric|min:0',
            'atg_quantity' => 'nullable|numeric|min:0',
            'dipping_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        // Calculate variance if both quantities are provided
        $dippedQuantity = $validated['dipped_quantity'] ?? $dipping->dipped_quantity;
        $atgQuantity = $validated['atg_quantity'] ?? $dipping->atg_quantity;
        
        if ($dippedQuantity !== null && $atgQuantity !== null) {
            $validated['variance'] = $dippedQuantity - $atgQuantity;
        }

        $dipping->update($validated);
        $dipping->load(['tank.product']);

        return response()->json($dipping);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/tank-dippings/{id}",
     *   summary="Delete tank dipping",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank dipping deleted")
     * )
     */
    public function destroy(int $id)
    {
        $dipping = TankDipping::findOrFail($id);
        $dipping->delete();

        return response()->json(['message' => 'Tank dipping deleted successfully']);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/tank-dippings/variance/report",
     *   summary="Get tank dipping variance report",
     *   tags={"Filling Station - Tank Dippings"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tank_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="Variance report")
     * )
     */
    public function varianceReport(Request $request)
    {
        $query = TankDipping::with(['tank.product'])
            ->whereNotNull('dipped_quantity')
            ->whereNotNull('atg_quantity');

        if ($request->has('tank_id')) {
            $query->where('tank_id', $request->tank_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('dipping_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('dipping_date', '<=', $request->date_to);
        }

        $dippings = $query->orderBy('dipping_date', 'desc')->orderBy('id', 'desc')->get();

        $report = $dippings->map(function ($dipping) {
            $expectedLevel = $dipping->atg_quantity ?? 0;
            $actualLevel = $dipping->dipped_quantity ?? 0;
            $variance = $dipping->variance ?? 0;
            $variancePercent = $expectedLevel > 0 ? ($variance / $expectedLevel) * 100 : 0;

            return [
                'id' => $dipping->id,
                'date' => $dipping->dipping_date,
                'tank' => $dipping->tank->name ?? 'N/A',
                'product' => $dipping->tank->product->name ?? 'N/A',
                'expected_level' => (float) $expectedLevel,
                'actual_level' => (float) $actualLevel,
                'variance' => (float) $variance,
                'variance_percent' => (float) $variancePercent,
                'status' => abs($variancePercent) <= 1 ? 'Normal' : 'Variance',
            ];
        });

        return response()->json($report);
    }
}

