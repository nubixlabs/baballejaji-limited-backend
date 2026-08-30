<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLedger;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $query = Trip::with('truck.type')->orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('trip_number', 'like', "%{$search}%")
                  ->orWhereHas('truck', function($q) use ($search) {
                      $q->where('truck_number', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%");
                  });
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('truck_id') && $request->truck_id != '') {
            $query->where('truck_id', $request->truck_id);
        }

        $trips = $query->paginate(10);
        return response()->json($trips);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'from_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
        ]);

        // Auto-generate Trip Number (e.g. KAN-123-XYZ-001)
        $truck = Truck::findOrFail($validated['truck_id']);
        $tripCountForTruck = Trip::where('truck_id', $truck->id)->count();
        $nextCount = $tripCountForTruck + 1;
        $validated['trip_number'] = $truck->truck_number . '-' . str_pad($nextCount, 3, '0', STR_PAD_LEFT);
        
        $validated['status'] = 'in-progress';

        $trip = Trip::create($validated);

        return response()->json([
            'message' => 'Trip created successfully',
            'trip' => $trip->load('truck')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $validated = $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            'from_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'status' => 'required|in:pending,in-progress,completed,cancelled',
        ]);

        $trip->update($validated);

        return response()->json([
            'message' => 'Trip updated successfully',
            'trip' => $trip->load('truck')
        ]);
    }

    public function getLedger($id)
    {
        $trip = Trip::with('truck')->findOrFail($id);
        $ledgers = TripLedger::where('trip_id', $id)->orderBy('date', 'asc')->orderBy('id', 'asc')->get();

        return response()->json([
            'trip' => $trip,
            'ledgers' => $ledgers
        ]);
    }

    public function storeLedger(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'litres' => 'nullable|numeric|min:0',
            'price_per_litre' => 'nullable|numeric|min:0',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
        ]);

        $validated['trip_id'] = $trip->id;
        
        // Auto-calculate debit if litres and price are provided and debit is missing
        if (!empty($validated['litres']) && !empty($validated['price_per_litre']) && empty($validated['debit'])) {
            $validated['debit'] = $validated['litres'] * $validated['price_per_litre'];
        }

        $validated['debit'] = $validated['debit'] ?? 0;
        $validated['credit'] = $validated['credit'] ?? 0;

        $ledger = TripLedger::create($validated);

        return response()->json(['message' => 'Trip ledger entry added successfully.', 'ledger' => $ledger], 201);
    }

    public function approve(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        
        $trip->update([
            'approval_status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Trip approved successfully.',
            'trip' => $trip
        ]);
    }

    public function reject(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        
        $trip->update([
            'approval_status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Trip rejected successfully.',
            'trip' => $trip
        ]);
    }
}
