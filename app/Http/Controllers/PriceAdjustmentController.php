<?php

namespace App\Http\Controllers;

use App\Models\PriceAdjustment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceAdjustmentController extends Controller
{
    /**
     * Get all price adjustments
     */
    public function index(Request $request)
    {
        $query = PriceAdjustment::with('product');

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('adjustment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('adjustment_date', '<=', $request->date_to);
        }

        $adjustments = $query->orderByDesc('adjustment_date')->get();
        return response()->json($adjustments);
    }

    /**
     * Store a new price adjustment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'new_cost_price' => 'nullable|numeric|min:0',
            'new_retail_price' => 'nullable|numeric|min:0',
            'new_dealer_price' => 'nullable|numeric|min:0',
            'new_bulk_price' => 'nullable|numeric|min:0',
            'adjustment_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($validated['product_id']);

            // Store old prices
            $adjustment = PriceAdjustment::create([
                'product_id' => $product->id,
                'old_cost_price' => $product->cost_price,
                'new_cost_price' => $validated['new_cost_price'] ?? $product->cost_price,
                'old_retail_price' => $product->retail_price,
                'new_retail_price' => $validated['new_retail_price'] ?? $product->retail_price,
                'old_dealer_price' => $product->dealer_price,
                'new_dealer_price' => $validated['new_dealer_price'] ?? $product->dealer_price,
                'old_bulk_price' => $product->bulk_price,
                'new_bulk_price' => $validated['new_bulk_price'] ?? $product->bulk_price,
                'adjustment_date' => $validated['adjustment_date'],
                'reason' => $validated['reason'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Update product prices
            if (isset($validated['new_cost_price'])) {
                $product->cost_price = $validated['new_cost_price'];
            }
            if (isset($validated['new_retail_price'])) {
                $product->retail_price = $validated['new_retail_price'];
            }
            if (isset($validated['new_dealer_price'])) {
                $product->dealer_price = $validated['new_dealer_price'];
            }
            if (isset($validated['new_bulk_price'])) {
                $product->bulk_price = $validated['new_bulk_price'];
            }
            $product->save();

            DB::commit();
            return response()->json($adjustment->load('product'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating price adjustment: ' . $e->getMessage()], 500);
        }
    }
}

