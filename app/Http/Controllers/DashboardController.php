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
}


