<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Get all purchases
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.product', 'tank']);

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        // Filter by product via purchase items
        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter by truck number (stored in notes field in this implementation)
        if ($request->filled('truck_no')) {
            $truckNo = $request->truck_no;
            $query->where('notes', 'like', "%{$truckNo}%");
        }

        // Filter by amount range using grand_total
        if ($request->filled('amount_from')) {
            $query->where('grand_total', '>=', $request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('grand_total', '<=', $request->amount_to);
        }

        $purchases = $query->orderByDesc('purchase_date')->get();
        return response()->json($purchases);
    }

    /**
     * Get purchase by ID
     */
    public function show(int $id)
    {
        $purchase = Purchase::with(['supplier', 'items.product', 'tank'])->findOrFail($id);
        return response()->json($purchase);
    }

    /**
     * Store a new purchase
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,approved,received,partial,completed',
            'notes' => 'nullable|string',
            'cost_breakdown' => 'nullable|array',
            'cost_breakdown.*.item' => 'required|string',
            'cost_breakdown.*.amount' => 'required|numeric|min:0',
            'truck_number' => 'nullable|string',
            'waybill_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate purchase number
            $purchaseNumber = 'PUR-' . strtoupper(Str::random(10));

            // Calculate totals
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $grandTotal = $totalAmount - $discount + $tax;

            // Create purchase
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'cost_breakdown' => $validated['cost_breakdown'] ?? null,
                'truck_number' => $validated['truck_number'] ?? null,
                'waybill_number' => $validated['waybill_number'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Create purchase items
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'price' => $item['price'],
                    'amount' => $item['quantity'] * $item['price'],
                ]);
            }

            DB::commit();
            return response()->json($purchase->load(['supplier', 'items.product']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Receive purchase items
     */
    public function receive(Request $request, int $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'tank_id' => 'nullable|exists:tanks,id',
            'driver_name' => 'nullable|string',
            'driver_phone' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $allReceived = true;
            $totalReceivedDelta = 0;
            foreach ($validated['items'] as $itemData) {
                $purchaseItem = PurchaseItem::where('purchase_id', $id)
                    ->where('id', $itemData['id'])
                    ->firstOrFail();

                $previousReceived = $purchaseItem->received_quantity;
                $receivedQty = $itemData['received_quantity'];
                $delta = max(0, $receivedQty - $previousReceived);
                $totalReceivedDelta += $delta;

                $purchaseItem->received_quantity = $receivedQty;
                $purchaseItem->save();

                // Update product quantity if received
                if ($delta > 0) {
                    $product = Product::find($purchaseItem->product_id);
                    if ($product) {
                        $product->quantity += $delta;
                        $product->save();
                    }
                }

                // Check if all items are received
                if ($purchaseItem->received_quantity < $purchaseItem->quantity) {
                    $allReceived = false;
                }
            }

            // Update tank content if provided
            if (!empty($validated['tank_id']) && $totalReceivedDelta > 0) {
                $tank = Tank::find($validated['tank_id']);
                if ($tank) {
                    $tank->content = ($tank->content ?? 0) + $totalReceivedDelta;
                    $tank->save();
                }

                // Persist selected tank on purchase
                $purchase->tank_id = $validated['tank_id'];
            }

            // Store driver information if provided
            if (array_key_exists('driver_name', $validated)) {
                $purchase->driver_name = $validated['driver_name'];
            }
            if (array_key_exists('driver_phone', $validated)) {
                $purchase->driver_phone = $validated['driver_phone'];
            }

            // Update purchase status
            if ($allReceived) {
                $purchase->status = 'received';
            } else {
                $purchase->status = 'partial';
            }
            $purchase->save();

            DB::commit();
            return response()->json($purchase->load(['supplier', 'items.product']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error receiving purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a purchase
     */
    public function update(Request $request, int $id)
    {
        $purchase = Purchase::findOrFail($id);

        $validated = $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'purchase_date' => 'sometimes|required|date',
            'expected_delivery_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,approved,received,partial,completed',
            'notes' => 'nullable|string',
            'cost_breakdown' => 'nullable|array',
            'cost_breakdown.*.item' => 'required|string',
            'cost_breakdown.*.amount' => 'required|numeric|min:0',
            'truck_number' => 'nullable|string',
            'waybill_number' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:purchase_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.received_quantity' => 'nullable|numeric|min:0',
            'items.*.amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Handle items update if provided
            if ($request->has('items') && is_array($request->items)) {
                // Get existing item IDs
                $existingItemIds = $purchase->items()->pluck('id')->toArray();
                $newItemIds = [];
                
                foreach ($request->items as $item) {
                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Update existing item
                        $purchaseItem = PurchaseItem::find($item['id']);
                        if ($purchaseItem) {
                            $purchaseItem->update([
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                                'received_quantity' => $item['received_quantity'] ?? $purchaseItem->received_quantity,
                                'price' => $item['price'],
                                'amount' => $item['amount'] ?? ($item['quantity'] * $item['price']),
                            ]);
                            $newItemIds[] = $item['id'];
                        }
                    } else {
                        // Create new item
                        $newItem = PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'received_quantity' => $item['received_quantity'] ?? 0,
                            'price' => $item['price'],
                            'amount' => $item['amount'] ?? ($item['quantity'] * $item['price']),
                        ]);
                        $newItemIds[] = $newItem->id;
                    }
                }
                
                // Delete items that are no longer in the request
                $itemsToDelete = array_diff($existingItemIds, $newItemIds);
                if (!empty($itemsToDelete)) {
                    PurchaseItem::whereIn('id', $itemsToDelete)->delete();
                }
            }

            // Recalculate grand total if discount or tax changed
            if (isset($validated['discount']) || isset($validated['tax'])) {
                $totalAmount = $validated['total_amount'] ?? $purchase->total_amount;
                $discount = $validated['discount'] ?? $purchase->discount;
                $tax = $validated['tax'] ?? $purchase->tax;
                $validated['grand_total'] = $totalAmount - $discount + $tax;
            }

            $purchase->update($validated);
            
            DB::commit();
            return response()->json($purchase->load(['supplier', 'items.product']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a purchase
     */
    public function destroy(int $id)
    {
        $purchase = Purchase::findOrFail($id);
        
        if ($purchase->status === 'completed' || $purchase->status === 'partial') {
            return response()->json(['message' => 'Cannot delete purchase that has been received'], 400);
        }

        $purchase->delete();
        return response()->json(['message' => 'Purchase deleted successfully']);
    }
}


