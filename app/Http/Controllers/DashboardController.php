<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Part;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/dashboard/stats",
     *   summary="Get dashboard statistics",
     *   tags={"Dashboard"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Stats data")
     * )
     */
    public function stats()
    {
        $totalParts = Part::count();
        $totalSuppliers = Supplier::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'completed')->sum('total');

        $recentOrders = Order::orderByDesc('id')->limit(5)->get();

        return response()->json([
            'totals' => [
                'parts' => $totalParts,
                'suppliers' => $totalSuppliers,
                'orders' => $totalOrders,
                'revenue' => (float) $totalRevenue,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/super-admin/dashboard/stats",
     *   summary="Get super admin dashboard statistics",
     *   tags={"Super Admin"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Stats data")
     * )
     */
    public function superAdminStats()
    {
        $totalUsers = \App\Models\User::count();
        $totalStations = \App\Models\FillingStation::count();
        $totalTransportUnits = \App\Models\Asset::count(); 
        $totalSpareParts = Part::count();

        return response()->json([
            'users' => $totalUsers,
            'filling_stations' => $totalStations,
            'transport_units' => $totalTransportUnits,
            'spare_parts' => $totalSpareParts,
        ]);
    }

    public function pendingActions(\Illuminate\Http\Request $request)
    {
        $stationId = $request->header('X-Filling-Station-Id');

        $getCount = function ($modelClass, $statusColumn, $statusValues) use ($stationId) {
            try {
                if (!class_exists($modelClass)) return 0;
                $query = $modelClass::query();
                if ($stationId) {
                    // Check if model has filling_station_id
                    $hasStationId = \Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), 'filling_station_id');
                    if ($hasStationId) {
                        $query->where('filling_station_id', $stationId);
                    }
                }
                if (is_array($statusValues)) {
                    $query->whereIn($statusColumn, $statusValues);
                } else {
                    $query->where($statusColumn, $statusValues);
                }
                return $query->count();
            } catch (\Exception $e) {
                return 0; // Fallback to 0 if table/column missing
            }
        };

        $lowStockTanks = 0;
        try {
            $tanksQuery = \App\Models\Tank::query();
            if ($stationId) $tanksQuery->where('filling_station_id', $stationId);
            $lowStockTanks = $tanksQuery->get()->filter(function ($tank) {
                $cap = (float)$tank->capacity;
                return $cap > 0 && ((float)$tank->content / $cap) < 0.2;
            })->count();
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStockTanks,
                'purchases' => $getCount(\App\Models\Purchase::class, 'status', 'pending'),
                'receptions' => $getCount(\App\Models\Purchase::class, 'status', 'partial'),
                'sales' => $getCount(\App\Models\Shift::class, 'status', ['open', 'pending']),
                'distributions' => $getCount(\App\Models\Distribution::class, 'status', 'pending'),
                'vouchers' => $getCount(\App\Models\Voucher::class, 'status', 'pending'),
                'transactions' => $getCount(\App\Models\JournalEntry::class, 'status', 'pending'),
            ]
        ]);
    }
}


