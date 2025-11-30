<?php

namespace App\Http\Controllers;

use App\Models\TankTransfer;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TankTransferController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/tank-transfers",
     *   summary="Get all tank transfers",
     *   tags={"Filling Station - Tank Transfers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tank_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="List of tank transfers")
     * )
     */
    public function index(Request $request)
    {
        $query = TankTransfer::with(['fromTank.product', 'toTank.product']);

        if ($request->has('tank_id')) {
            $query->where(function($q) use ($request) {
                $q->where('from_tank_id', $request->tank_id)
                  ->orWhere('to_tank_id', $request->tank_id);
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        $transfers = $query->orderBy('transfer_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json($transfers);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/tank-transfers/{id}",
     *   summary="Get tank transfer by ID",
     *   tags={"Filling Station - Tank Transfers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank transfer details")
     * )
     */
    public function show(int $id)
    {
        $transfer = TankTransfer::with(['fromTank.product', 'toTank.product'])->findOrFail($id);
        return response()->json($transfer);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/tank-transfers",
     *   summary="Create new tank transfer",
     *   tags={"Filling Station - Tank Transfers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"from_tank_id","to_tank_id","quantity","transfer_date"},
     *       @OA\Property(property="from_tank_id", type="integer"),
     *       @OA\Property(property="to_tank_id", type="integer"),
     *       @OA\Property(property="quantity", type="number"),
     *       @OA\Property(property="transfer_date", type="string", format="date"),
     *       @OA\Property(property="notes", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Tank transfer created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_tank_id' => 'required|exists:tanks,id',
            'to_tank_id' => 'required|exists:tanks,id|different:from_tank_id',
            'quantity' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Check if from tank has enough content
        $fromTank = Tank::findOrFail($validated['from_tank_id']);
        if ($fromTank->content < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient quantity in source tank',
                'errors' => ['quantity' => ['The source tank does not have enough content.']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $transfer = TankTransfer::create([
                ...$validated,
                'created_by' => $request->user()->id ?? null,
            ]);

            // Update tank contents
            $fromTank->decrement('content', $validated['quantity']);
            $toTank = Tank::findOrFail($validated['to_tank_id']);
            $toTank->increment('content', $validated['quantity']);

            // Recalculate levels
            $fromTank->level = $fromTank->capacity > 0 ? ($fromTank->content / $fromTank->capacity) * 100 : 0;
            $fromTank->save();
            
            $toTank->level = $toTank->capacity > 0 ? ($toTank->content / $toTank->capacity) * 100 : 0;
            $toTank->save();

            DB::commit();

            $transfer->load(['fromTank.product', 'toTank.product']);

            return response()->json($transfer, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *   path="/api/filling/tank-transfers/{id}",
     *   summary="Update tank transfer",
     *   tags={"Filling Station - Tank Transfers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="from_tank_id", type="integer"),
     *       @OA\Property(property="to_tank_id", type="integer"),
     *       @OA\Property(property="quantity", type="number"),
     *       @OA\Property(property="transfer_date", type="string", format="date"),
     *       @OA\Property(property="notes", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Tank transfer updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $transfer = TankTransfer::findOrFail($id);

        $validated = $request->validate([
            'from_tank_id' => 'sometimes|exists:tanks,id',
            'to_tank_id' => 'sometimes|exists:tanks,id',
            'quantity' => 'sometimes|numeric|min:0.01',
            'transfer_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Reverse old transfer if quantity or tanks changed
            if (isset($validated['quantity']) || isset($validated['from_tank_id']) || isset($validated['to_tank_id'])) {
                $oldFromTank = Tank::find($transfer->from_tank_id);
                $oldToTank = Tank::find($transfer->to_tank_id);
                
                if ($oldFromTank && $oldToTank) {
                    // Reverse old transfer
                    $oldFromTank->increment('content', $transfer->quantity);
                    $oldToTank->decrement('content', $transfer->quantity);
                    
                    // Recalculate levels
                    $oldFromTank->level = $oldFromTank->capacity > 0 ? ($oldFromTank->content / $oldFromTank->capacity) * 100 : 0;
                    $oldFromTank->save();
                    $oldToTank->level = $oldToTank->capacity > 0 ? ($oldToTank->content / $oldToTank->capacity) * 100 : 0;
                    $oldToTank->save();
                }

                // Apply new transfer
                $fromTankId = $validated['from_tank_id'] ?? $transfer->from_tank_id;
                $toTankId = $validated['to_tank_id'] ?? $transfer->to_tank_id;
                $quantity = $validated['quantity'] ?? $transfer->quantity;

                $fromTank = Tank::findOrFail($fromTankId);
                if ($fromTank->content < $quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Insufficient quantity in source tank',
                        'errors' => ['quantity' => ['The source tank does not have enough content.']]
                    ], 422);
                }

                $fromTank->decrement('content', $quantity);
                $toTank = Tank::findOrFail($toTankId);
                $toTank->increment('content', $quantity);

                // Recalculate levels
                $fromTank->level = $fromTank->capacity > 0 ? ($fromTank->content / $fromTank->capacity) * 100 : 0;
                $fromTank->save();
                $toTank->level = $toTank->capacity > 0 ? ($toTank->content / $toTank->capacity) * 100 : 0;
                $toTank->save();
            }

            $transfer->update($validated);
            $transfer->load(['fromTank.product', 'toTank.product']);

            DB::commit();

            return response()->json($transfer);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/tank-transfers/{id}",
     *   summary="Delete tank transfer",
     *   tags={"Filling Station - Tank Transfers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank transfer deleted")
     * )
     */
    public function destroy(int $id)
    {
        $transfer = TankTransfer::findOrFail($id);

        DB::beginTransaction();
        try {
            // Reverse the transfer
            $fromTank = Tank::find($transfer->from_tank_id);
            $toTank = Tank::find($transfer->to_tank_id);
            
            if ($fromTank && $toTank) {
                $fromTank->increment('content', $transfer->quantity);
                $toTank->decrement('content', $transfer->quantity);
                
                // Recalculate levels
                $fromTank->level = $fromTank->capacity > 0 ? ($fromTank->content / $fromTank->capacity) * 100 : 0;
                $fromTank->save();
                $toTank->level = $toTank->capacity > 0 ? ($toTank->content / $toTank->capacity) * 100 : 0;
                $toTank->save();
            }

            $transfer->delete();

            DB::commit();

            return response()->json(['message' => 'Tank transfer deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


