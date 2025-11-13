<?php

namespace App\Http\Controllers;

use App\Models\InventoryReconciliation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReconciliationController extends Controller
{
    /**
     * Get all inventory reconciliations
     */
    public function index(Request $request)
    {
        $query = InventoryReconciliation::with('product');

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('reconciliation_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('reconciliation_date', '<=', $request->date_to);
        }

        $reconciliations = $query->orderByDesc('reconciliation_date')->get();
        return response()->json($reconciliations);
    }

    /**
     * Store a new inventory reconciliation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'physical_quantity' => 'required|numeric|min:0',
            'reconciliation_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($validated['product_id']);
            $systemQuantity = $product->quantity;
            $physicalQuantity = $validated['physical_quantity'];
            $variance = $physicalQuantity - $systemQuantity;

            // Create reconciliation
            $reconciliation = InventoryReconciliation::create([
                'product_id' => $product->id,
                'system_quantity' => $systemQuantity,
                'physical_quantity' => $physicalQuantity,
                'variance' => $variance,
                'reconciliation_date' => $validated['reconciliation_date'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Update product quantity to match physical quantity
            $product->quantity = $physicalQuantity;
            $product->save();

            DB::commit();
            return response()->json($reconciliation->load('product'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating inventory reconciliation: ' . $e->getMessage()], 500);
        }
    }
}

