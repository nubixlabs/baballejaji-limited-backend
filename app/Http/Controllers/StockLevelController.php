<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    /**
     * Get all stock levels
     */
    public function index(Request $request)
    {
        $query = StockLevel::with(['shift', 'product']);

        // Filter by shift
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $stockLevels = $query->orderByDesc('date')->get();
        return response()->json($stockLevels);
    }

    /**
     * Store a new stock level
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $stockLevel = StockLevel::create($validated);
        return response()->json($stockLevel->load(['shift', 'product']), 201);
    }

    /**
     * Update a stock level
     */
    public function update(Request $request, int $id)
    {
        $stockLevel = StockLevel::findOrFail($id);

        $validated = $request->validate([
            'shift_id' => 'sometimes|required|exists:shifts,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $stockLevel->update($validated);
        return response()->json($stockLevel->load(['shift', 'product']));
    }

    /**
     * Delete a stock level
     */
    public function destroy(int $id)
    {
        $stockLevel = StockLevel::findOrFail($id);
        $stockLevel->delete();

        return response()->json(['message' => 'Stock level deleted successfully']);
    }
}



