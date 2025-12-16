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
    
    /**
     * Shift Sales Summary Report
     */
    /**
     * Shift Sales Summary Report
     */
    public function shiftSales(Request $request)
    {
        $query = \App\Models\ShiftSalesSummary::query()
            ->join('shifts', 'shift_sales_summaries.shift_id', '=', 'shifts.id')
            ->join('products', 'shift_sales_summaries.product_id', '=', 'products.id')
            ->select(
                'shift_sales_summaries.*',
                'shifts.shift_id as shift_code',
                'shifts.name as shift_name',
                'products.name as product_name'
            );

        // Filter by Date From
        if ($request->filled('date_from')) {
            $query->where('shift_sales_summaries.date', '>=', $request->date_from);
        }

        // Filter by Date To
        if ($request->filled('date_to')) {
            $query->where('shift_sales_summaries.date', '<=', $request->date_to);
        }

        // Filter by Shift ID
        if ($request->filled('shift_id')) {
            $query->where('shifts.shift_id', $request->shift_id);
        }
        
        $records = $query->orderByDesc('shift_sales_summaries.date')
                         ->orderByDesc('shifts.id')
                         ->get();

        $reportData = $records->map(function ($record) {
            return [
                'date' => $record->date,
                'shift_id' => $record->shift_code,
                'name' => $record->shift_name,
                'product_name' => $record->product_name,
                'cost_price' => number_format((float)$record->cost_price, 2, '.', ''),
                'pump_price' => number_format((float)$record->pump_price, 2, '.', ''),
                'shift_vol' => number_format((float)$record->shift_vol, 2, '.', ''),
                'shift_amount' => number_format((float)$record->shift_amount, 2, '.', ''),
                'bulk_sales' => number_format((float)$record->bulk_sales, 2, '.', ''),
                'retail_sales' => number_format((float)$record->retail_sales, 2, '.', ''),
                'total_revenue' => number_format((float)$record->total_revenue, 2, '.', ''),
            ];
        });
    
        return response()->json($reportData);
    }

    /**
     * Backfill Shift Sales Summary for existing shifts
     */
    public function backfill()
    {
        $shifts = \App\Models\Shift::all();
        $products = \App\Models\Product::all();
        $count = 0;

        foreach ($shifts as $shift) {
            foreach ($products as $product) {
                // Check if already exists
                $exists = \App\Models\ShiftSalesSummary::where('shift_id', $shift->id)
                    ->where('product_id', $product->id)
                    ->exists();
                
                if (!$exists) {
                    \App\Models\ShiftSalesSummary::create([
                        'shift_id' => $shift->id,
                        'product_id' => $product->id,
                        'cost_price' => $product->cost_price ?? 0,
                        'pump_price' => $product->retail_price,
                        'shift_vol' => 0,
                        'shift_amount' => 0,
                        'bulk_sales' => 0,
                        'retail_sales' => 0,
                        'total_revenue' => 0,
                        'date' => $shift->date,
                    ]);
                    $count++;
                }
            }
        }

        return response()->json(['message' => "Backfilled {$count} records."]);
    }

    /**
     * Meter Reading Report
     */
    public function meterReadings(Request $request)
    {
        $query = \App\Models\Shift::query();

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        $shifts = $query->orderByDesc('date')->orderByDesc('id')->get();

        $reportData = [];
        foreach ($shifts as $shift) {
            $readings = $shift->nozzle_readings ?? [];
            if (is_string($readings)) $readings = json_decode($readings, true);
            if (!is_array($readings)) $readings = [];

            if (empty($readings)) continue;

            foreach ($readings as $reading) {
                // Filter by product if needed (client side filtering might be enough for now or complex backend filter)
                
                $reportData[] = [
                    'date' => $shift->date,
                    'shift_id' => $shift->shift_id,
                    'nozzle_name' => $reading['nozzle_name'] ?? 'N/A',
                    'tank_name' => $reading['tank_name'] ?? 'N/A',
                    'product_name' => $reading['product_name'] ?? 'N/A',
                    'opening_reading' => $reading['opening_reading'] ?? 0,
                    'closing_reading' => $reading['closing_reading'] ?? 0,
                    'qty_sold' => $reading['qty_sold'] ?? 0,
                    'rtt' => $reading['rtt'] ?? 0,
                    'revenue' => $reading['revenue'] ?? 0,
                    'retail_sales' => $reading['retail_sales'] ?? 0,
                    'retail_revenue' => $reading['retail_revenue'] ?? 0,
                    'total_revenue' => $reading['total_revenue'] ?? 0,
                    'shortage' => $reading['shortage'] ?? 0,
                    'overage' => $reading['overage'] ?? 0,
                ];
            }
        }

        return response()->json($reportData);
    }
}
