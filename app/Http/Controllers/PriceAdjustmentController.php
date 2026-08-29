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

        $fillingStationId = $request->header('X-Filling-Station-Id');
        $fillingStationId = ($fillingStationId && is_numeric($fillingStationId)) ? (int) $fillingStationId : null;

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($validated['product_id']);

            // Get old prices from pivot (station-level) or product table (global)
            $oldCostPrice = $fillingStationId
                ? $product->priceForStation($fillingStationId, 'cost_price')
                : $product->cost_price;
            $oldRetailPrice = $fillingStationId
                ? $product->priceForStation($fillingStationId, 'retail_price')
                : $product->retail_price;
            $oldDealerPrice = $fillingStationId
                ? $product->priceForStation($fillingStationId, 'dealer_price')
                : $product->dealer_price;
            $oldBulkPrice = $fillingStationId
                ? $product->priceForStation($fillingStationId, 'bulk_price')
                : $product->bulk_price;

            // Store old prices
            $adjustment = PriceAdjustment::create([
                'product_id' => $product->id,
                'old_cost_price' => $oldCostPrice,
                'new_cost_price' => $validated['new_cost_price'] ?? $oldCostPrice,
                'old_retail_price' => $oldRetailPrice,
                'new_retail_price' => $validated['new_retail_price'] ?? $oldRetailPrice,
                'old_dealer_price' => $oldDealerPrice,
                'new_dealer_price' => $validated['new_dealer_price'] ?? $oldDealerPrice,
                'old_bulk_price' => $oldBulkPrice,
                'new_bulk_price' => $validated['new_bulk_price'] ?? $oldBulkPrice,
                'adjustment_date' => $validated['adjustment_date'],
                'reason' => $validated['reason'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Update prices — pivot for station-level, product table for global
            $priceFields = [
                'cost_price' => 'new_cost_price',
                'retail_price' => 'new_retail_price',
                'dealer_price' => 'new_dealer_price',
                'bulk_price' => 'new_bulk_price',
            ];

            $pivotUpdates = [];
            foreach ($priceFields as $pivotField => $requestField) {
                if (isset($validated[$requestField])) {
                    $pivotUpdates[$pivotField] = $validated[$requestField];
                }
            }

            if ($fillingStationId && !empty($pivotUpdates)) {
                // Update pivot for the station
                $product->fillingStations()->updateExistingPivot($fillingStationId, $pivotUpdates);
            } elseif (empty($fillingStationId) && !empty($pivotUpdates)) {
                // Fallback: update product table directly (super-admin path)
                foreach ($priceFields as $pivotField => $requestField) {
                    if (isset($validated[$requestField])) {
                        $product->{$pivotField} = $validated[$requestField];
                    }
                }
                $product->save();
            }

            DB::commit();
            return response()->json($adjustment->load('product'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating price adjustment: ' . $e->getMessage()], 500);
        }
    }
}



