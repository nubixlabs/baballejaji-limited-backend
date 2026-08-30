<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use App\Models\Trip;
use App\Models\TripLedger;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $activeTrucks = Truck::where('status', 'active')->count();
        $inProgressTrips = Trip::where('status', 'in-progress')->count();
        $completedTrips = Trip::where('status', 'completed')->count();
        
        $totalCredit = TripLedger::sum('credit');
        $totalDebit = TripLedger::sum('debit');
        $totalProfit = $totalCredit - $totalDebit;

        $recentTrips = Trip::with('truck')->orderBy('created_at', 'desc')->take(5)->get();

        return response()->json([
            'active_trucks' => $activeTrucks,
            'active_trips' => $inProgressTrips,
            'completed_trips' => $completedTrips,
            'total_profit' => $totalProfit,
            'recent_trips' => $recentTrips,
        ]);
    }
}
