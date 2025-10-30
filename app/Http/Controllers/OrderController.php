<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/orders",
     *   summary="Get all orders",
     *   tags={"Orders"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of orders")
     * )
     */
    public function index()
    {
        $orders = Order::with(['items.part'])->orderByDesc('id')->get();
        return response()->json($orders);
    }

    /**
     * @OA\Post(
     *   path="/api/orders",
     *   summary="Create new order",
     *   tags={"Orders"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"customerName","customerEmail","customerPhone","deliveryDate","items"},
     *       @OA\Property(property="customerName", type="string"),
     *       @OA\Property(property="customerEmail", type="string"),
     *       @OA\Property(property="customerPhone", type="string"),
     *       @OA\Property(property="deliveryDate", type="string", format="date"),
     *       @OA\Property(property="notes", type="string"),
     *       @OA\Property(
     *         property="items",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="partId", type="integer"),
     *           @OA\Property(property="quantity", type="integer")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Order created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerName' => 'required|string|max:255',
            'customerEmail' => 'required|email|max:255',
            'customerPhone' => 'required|string|max:50',
            'deliveryDate' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.partId' => 'required|exists:parts,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $order = Order::create([
                'customerName' => $validated['customerName'],
                'customerEmail' => $validated['customerEmail'],
                'customerPhone' => $validated['customerPhone'],
                'deliveryDate' => $validated['deliveryDate'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'total' => 0,
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $part = Part::findOrFail($item['partId']);
                $quantity = $item['quantity'];
                $unitPrice = $part->price;
                $subtotal = $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'part_id' => $part->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $part->decrement('stock', $quantity);
                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            return $order->load(['items.part']);
        });

        return response()->json($order, 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/orders/{id}/status",
     *   summary="Update order status",
     *   tags={"Orders"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="status", type="string", enum={"pending","processing","completed","cancelled"})
     *     )
     *   ),
     *   @OA\Response(response=200, description="Order status updated")
     * )
     */
    public function updateStatus(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);
        return response()->json($order);
    }
}
