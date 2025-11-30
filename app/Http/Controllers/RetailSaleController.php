<?php

namespace App\Http\Controllers;

use App\Models\RetailSale;
use App\Models\RetailSaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RetailSaleController extends Controller
{
    /**
     * Get all retail sales
     */
    public function index(Request $request)
    {
        $query = RetailSale::with(['customer', 'shift', 'items.product']);

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by shift
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $retailSales = $query->orderByDesc('sale_date')->get();
        return response()->json($retailSales);
    }

    /**
     * Get retail sale by ID
     */
    public function show(int $id)
    {
        $retailSale = RetailSale::with(['customer', 'shift', 'items.product'])->findOrFail($id);
        return response()->json($retailSale);
    }

    /**
     * Store a new retail sale
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,transfer,card',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate invoice number
            $invoiceNumber = 'RETAIL-' . strtoupper(Str::random(10));

            // Calculate totals
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $grandTotal = $totalAmount - $discount + $tax;

            // Create retail sale
            $retailSale = RetailSale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'shift_id' => $validated['shift_id'] ?? null,
                'sale_date' => $validated['sale_date'],
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Create retail sale items and update product quantities
            foreach ($validated['items'] as $item) {
                RetailSaleItem::create([
                    'retail_sale_id' => $retailSale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['quantity'] * $item['price'],
                ]);

                // Update product quantity
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->quantity -= $item['quantity'];
                    $product->save();
                }
            }

            DB::commit();
            return response()->json($retailSale->load(['customer', 'shift', 'items.product']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating retail sale: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a retail sale
     */
    public function update(Request $request, int $id)
    {
        $retailSale = RetailSale::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'sale_date' => 'sometimes|required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,transfer,card',
            'notes' => 'nullable|string',
        ]);

        // Recalculate grand total if discount or tax changed
        if (isset($validated['discount']) || isset($validated['tax'])) {
            $totalAmount = $retailSale->total_amount;
            $discount = $validated['discount'] ?? $retailSale->discount;
            $tax = $validated['tax'] ?? $retailSale->tax;
            $validated['grand_total'] = $totalAmount - $discount + $tax;
        }

        $retailSale->update($validated);
        return response()->json($retailSale->load(['customer', 'shift', 'items.product']));
    }

    /**
     * Delete a retail sale
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $retailSale = RetailSale::with('items')->findOrFail($id);

            // Restore product quantities
            foreach ($retailSale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $retailSale->delete();
            DB::commit();

            return response()->json(['message' => 'Retail sale deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting retail sale: ' . $e->getMessage()], 500);
        }
    }
}


