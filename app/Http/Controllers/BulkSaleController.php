<?php

namespace App\Http\Controllers;

use App\Models\BulkSale;
use App\Models\BulkSaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BulkSaleController extends Controller
{
    /**
     * Get all bulk sales
     */
    public function index(Request $request)
    {
        $query = BulkSale::with(['customer', 'items.product']);

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $bulkSales = $query->orderByDesc('sale_date')->get();
        return response()->json($bulkSales);
    }

    /**
     * Get bulk sale by ID
     */
    public function show(int $id)
    {
        $bulkSale = BulkSale::with(['customer', 'items.product', 'creator'])->findOrFail($id);
        return response()->json($bulkSale);
    }

    /**
     * Store a new bulk sale
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|string|in:pending,partial,paid,approved',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'invoice_number' => 'nullable|string|unique:bulk_sales,invoice_number',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        DB::beginTransaction();
        try {
            // Generate invoice number if not provided
            $invoiceNumber = $validated['invoice_number'] ?? ('BULK-' . strtoupper(Str::random(10)));

            // Calculate totals
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $grandTotal = $totalAmount - $discount + $tax;

            // Create bulk sale
            $bulkSale = BulkSale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'],
                'shift_id' => $validated['shift_id'] ?? null,
                'sale_date' => $validated['sale_date'],
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'payment_status' => $validated['payment_status'] ?? 'pending',
                'payment_method' => $validated['payment_method'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Create bulk sale items and update product quantities
            foreach ($validated['items'] as $item) {
                BulkSaleItem::create([
                    'bulk_sale_id' => $bulkSale->id,
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
            return response()->json($bulkSale->load(['customer', 'items.product']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating bulk sale: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a bulk sale
     */
    public function update(Request $request, int $id)
    {
        $bulkSale = BulkSale::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'sale_date' => 'sometimes|required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|string|in:pending,partial,paid,approved',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Recalculate grand total if discount or tax changed
        if (isset($validated['discount']) || isset($validated['tax'])) {
            $totalAmount = $bulkSale->total_amount;
            $discount = $validated['discount'] ?? $bulkSale->discount;
            $tax = $validated['tax'] ?? $bulkSale->tax;
            $validated['grand_total'] = $totalAmount - $discount + $tax;
        }

        $bulkSale->update($validated);
        return response()->json($bulkSale->load(['customer', 'items.product']));
    }

    /**
     * Delete a bulk sale
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $bulkSale = BulkSale::with('items')->findOrFail($id);

            // Restore product quantities
            foreach ($bulkSale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $bulkSale->delete();
            DB::commit();

            return response()->json(['message' => 'Bulk sale deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting bulk sale: ' . $e->getMessage()], 500);
        }
    }
}


