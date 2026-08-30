<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function index(Request $request)
    {
        $query = Truck::with('type');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('truck_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('engine_number', 'like', "%{$search}%");
            });
        }

        $trucks = $query->latest()->paginate(5);
        return response()->json($trucks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_type_id' => 'required|exists:truck_types,id',
            'engine_number' => 'nullable|string|max:255',
            'truck_number'  => 'required|string|unique:trucks,truck_number|max:255',
            'driver_name'   => 'nullable|string|max:255',
            'driver_phone'  => 'nullable|string|max:255',
            'driver_address'=> 'nullable|string',
        ]);

        $truck = Truck::create($validated);
        
        return response()->json([
            'message' => 'Truck added successfully',
            'truck'   => $truck->load('type')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $truck = Truck::findOrFail($id);

        $validated = $request->validate([
            'truck_type_id' => 'required|exists:truck_types,id',
            'engine_number' => 'nullable|string|max:255',
            'truck_number'  => 'required|string|max:255|unique:trucks,truck_number,' . $truck->id,
            'driver_name'   => 'nullable|string|max:255',
            'driver_phone'  => 'nullable|string|max:255',
            'driver_address'=> 'nullable|string',
            'status'        => 'required|in:active,inactive,maintenance',
        ]);

        $truck->update($validated);
        
        return response()->json([
            'message' => 'Truck updated successfully',
            'truck'   => $truck->load('type')
        ]);
    }

    public function getTypes()
    {
        $types = TruckType::orderBy('name')->get();
        return response()->json($types);
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:truck_types,name|max:255',
        ]);

        $type = TruckType::create($validated);
        
        return response()->json([
            'message' => 'Truck type added successfully',
            'type'    => $type
        ], 201);
    }
}
