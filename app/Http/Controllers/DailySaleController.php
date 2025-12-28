<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use Illuminate\Http\Request;

class DailySaleController extends Controller
{
    /**
     * Get all daily sales
     */
    public function index(Request $request)
    {
        $query = DailySale::with(['shift', 'product']);

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

        $dailySales = $query->orderByDesc('date')->get();
        return response()->json($dailySales);
    }

    /**
     * Store a new daily sale
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Calculate amount if not provided
        if (!isset($validated['amount'])) {
            $validated['amount'] = $validated['quantity'] * $validated['price'];
        }

        $dailySale = DailySale::create($validated);
        return response()->json($dailySale->load(['shift', 'product']), 201);
    }

    /**
     * Update a daily sale
     */
    public function update(Request $request, int $id)
    {
        $dailySale = DailySale::findOrFail($id);

        $validated = $request->validate([
            'shift_id' => 'sometimes|required|exists:shifts,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'price' => 'sometimes|required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        // Recalculate amount if quantity or price changed
        if (isset($validated['quantity']) || isset($validated['price'])) {
            $quantity = $validated['quantity'] ?? $dailySale->quantity;
            $price = $validated['price'] ?? $dailySale->price;
            $validated['amount'] = $quantity * $price;
        }

        $dailySale->update($validated);
        return response()->json($dailySale->load(['shift', 'product']));
    }

    /**
     * Delete a daily sale
     */
    public function destroy(int $id)
    {
        $dailySale = DailySale::findOrFail($id);
        $dailySale->delete();

        return response()->json(['message' => 'Daily sale deleted successfully']);
    }
}



