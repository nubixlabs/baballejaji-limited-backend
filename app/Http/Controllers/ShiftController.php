<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\StockLevel;
use App\Models\DailySale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShiftController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/shifts",
     *   summary="Get all shifts",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of shifts")
     * )
     */
    public function index(Request $request)
    {
        $query = Shift::query();

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by shift ID
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        $shifts = $query->orderByDesc('date')->orderByDesc('id')->get();
        return response()->json($shifts);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/shifts/{id}",
     *   summary="Get shift by ID",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Shift details")
     * )
     */
    public function show(int $id)
    {
        $shift = Shift::with(['stockLevels.product', 'dailySales.product'])->findOrFail($id);
        return response()->json($shift);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/shifts",
     *   summary="Create new shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","date"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="date", type="string", format="date")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Shift created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        // Get the last shift record
        $lastShift = Shift::orderBy('id', 'desc')->first();

        // Generate next shift id
        $nextId = $lastShift ? intval($lastShift->shift_id) + 1 : 1;

        // Format as 3 digits with leading zeros (001, 002, 010, 120)
        $validated['shift_id'] = str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $validated['status'] = 'open';

        $shift = Shift::create($validated);

        return response()->json($shift, 201);
    }


    /**
     * @OA\Put(
     *   path="/api/filling/shifts/{id}",
     *   summary="Update shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="date", type="string", format="date"),
     *       @OA\Property(property="cash_sales", type="number"),
     *       @OA\Property(property="credit_sales", type="number"),
     *       @OA\Property(property="status", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Shift updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'cash_sales' => 'nullable|numeric|min:0',
            'credit_sales' => 'nullable|numeric|min:0',
            'status' => 'sometimes|required|string|in:open,closed,approved',
        ]);

        // Calculate sales revenue
        if (isset($validated['cash_sales']) || isset($validated['credit_sales'])) {
            $cashSales = $validated['cash_sales'] ?? $shift->cash_sales;
            $creditSales = $validated['credit_sales'] ?? $shift->credit_sales;
            $validated['sales_revenue'] = $cashSales + $creditSales;
        }

        // Handle status changes
        if (isset($validated['status'])) {
            if ($validated['status'] === 'closed' && $shift->status === 'open') {
                $validated['closed_at'] = now();
                $validated['closed_by'] = $request->user()->id;
            }
            if ($validated['status'] === 'approved' && $shift->status !== 'approved') {
                $validated['approved_at'] = now();
                $validated['approved_by'] = $request->user()->id;
            }
        }

        $shift->update($validated);
        return response()->json($shift);
    }

    /**
     * Close a shift
     */
    public function close(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        if ($shift->status !== 'open') {
            return response()->json(['message' => 'Shift is not open'], 400);
        }

        $shift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        return response()->json($shift);
    }

    /**
     * Approve a shift
     */
    public function approve(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        if ($shift->status === 'approved') {
            return response()->json(['message' => 'Shift is already approved'], 400);
        }

        $shift->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return response()->json($shift);
    }

    /**
     * Save shift values (nozzle readings and credit sales)
     */
    public function saveValues(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        $validated = $request->validate([
            'nozzle_readings' => 'nullable|array',
            'nozzle_readings.*.nozzle_id' => 'required|integer|exists:nozzles,id',
            'nozzle_readings.*.closing_reading' => 'required|numeric|min:0',
            'nozzle_readings.*.rtt' => 'nullable|numeric|min:0',
            'nozzle_readings.*.cash_over' => 'nullable|numeric|min:0',
            'nozzle_readings.*.cash_shortage' => 'nullable|numeric|min:0',
            'credit_sales' => 'nullable|array',
            'credit_sales.*.customer_id' => 'required|integer|exists:customers,id',
            'credit_sales.*.product_id' => 'required|integer|exists:products,id',
            'credit_sales.*.quantity' => 'required|numeric|min:0',
            'credit_sales.*.discount' => 'nullable|numeric|min:0',
            'credit_sales.*.narration' => 'nullable|string',
            'credit_sales.*.ledger_notes' => 'nullable|string',
        ]);

        // Store nozzle readings in shift's metadata or separate table
        // For now, we'll store in a JSON column
        if (isset($validated['nozzle_readings'])) {
            $shift->nozzle_readings = $validated['nozzle_readings'];
        }

        // Store credit sales data
        if (isset($validated['credit_sales'])) {
            $shift->credit_sales_data = $validated['credit_sales'];
        }

        $shift->save();

        return response()->json([
            'message' => 'Shift values saved successfully',
            'shift' => $shift
        ]);
    }

    /**
     * Delete saved shift values
     */
    public function deleteValues(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        // Clear nozzle readings and credit sales data
        $shift->nozzle_readings = null;
        $shift->credit_sales_data = null;
        $shift->save();

        return response()->json([
            'message' => 'Shift values deleted successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/shifts/{id}",
     *   summary="Delete shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Shift deleted")
     * )
     */
    public function destroy(int $id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully']);
    }
}


