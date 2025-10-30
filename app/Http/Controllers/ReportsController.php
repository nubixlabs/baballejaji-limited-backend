<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/reports/sales",
     *   summary="Get sales data",
     *   tags={"Reports"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="Sales report")
     * )
     */
    public function sales(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = Order::query()->where('status', 'completed');

        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $sales = $query
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json($sales);
    }

    /**
     * @OA\Get(
     *   path="/api/reports/top-selling",
     *   summary="Get top selling parts",
     *   tags={"Reports"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Top selling parts")
     * )
     */
    public function topSelling(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 10;

        $rows = DB::table('order_items')
            ->join('parts', 'order_items.part_id', '=', 'parts.id')
            ->select(
                'parts.id',
                'parts.name',
                'parts.partNumber',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->groupBy('parts.id', 'parts.name', 'parts.partNumber')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return response()->json($rows);
    }
}
