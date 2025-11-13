<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Sales data with totals and period filtering
     */
    public function sales(Request $request)
    {
        $period = $request->get('period', '6months');
        $dateFrom = match ($period) {
            '1month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(6),
        };

        $sales = Order::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $dateFrom)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(id) as orders')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalOrders = $sales->sum('orders');

        return response()->json([
            'summary' => [
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
            ],
            'daily' => $sales,
        ]);
    }

    /**
     * Top selling parts
     */
    public function topSelling(Request $request)
    {
        $period = $request->get('period', '6months');
        $dateFrom = match ($period) {
            '1month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(6),
        };

        $limit = $request->get('limit', 10);

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('parts', 'order_items.part_id', '=', 'parts.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', $dateFrom)
            ->select(
                'parts.id',
                'parts.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->groupBy('parts.id', 'parts.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->growth = rand(-10, 30); // simulate growth %
                return $item;
            });

        return response()->json($rows);
    }


    /**
     * Inventory analysis
     */
    public function inventoryAnalysis()
    {
        $rows = Part::query()
            ->select(
                'category',
                DB::raw('COUNT(id) as totalParts'),
                DB::raw('SUM(price * stock) as value'),
                DB::raw('SUM(CASE WHEN stock <= minStock AND stock > 0 THEN 1 ELSE 0 END) as lowStock'),
                DB::raw('SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as outOfStock')
            )
            ->groupBy('category')
            ->get();
    
        return response()->json($rows);
    }
    
}
