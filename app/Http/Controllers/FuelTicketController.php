<?php

namespace App\Http\Controllers;

use App\Models\FuelTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FuelTicketController extends Controller
{
    /**
     * Display a listing of fuel tickets.
     */
    public function index(Request $request)
    {
        $query = FuelTicket::with(['product', 'creator', 'approver']);

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Filter by truck number
        if ($request->has('truck_number')) {
            $query->where('truck_number', 'like', '%' . $request->truck_number . '%');
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $fuelTickets = $query->orderByDesc('created_at')->get();
        
        return response()->json($fuelTickets);
    }

    /**
     * Store a newly created fuel ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuel_ticket_number' => 'required|string|unique:fuel_tickets',
            'date' => 'required|date',
            'product_id' => 'required|exists:products,id',
            'rate' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'trip_allowance' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'truck_capacity' => 'nullable|string',
            'truck_number' => 'nullable|string',
            'loading_point' => 'nullable|string',
            'destination' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'driver_phone' => 'nullable|string',
            'truck_provider' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:10240',
            'details' => 'nullable|string',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('fuel_tickets', 'public');
            $validated['attachment_path'] = $path;
        }

        $validated['created_by'] = Auth::id();

        $fuelTicket = FuelTicket::create($validated);
        
        return response()->json($fuelTicket->load(['product', 'creator']), 201);
    }

    /**
     * Display the specified fuel ticket.
     */
    public function show($id)
    {
        $fuelTicket = FuelTicket::with(['product', 'creator', 'approver'])->findOrFail($id);
        return response()->json($fuelTicket);
    }

    /**
     * Update the specified fuel ticket.
     */
    public function update(Request $request, $id)
    {
        $fuelTicket = FuelTicket::findOrFail($id);

        $validated = $request->validate([
            'fuel_ticket_number' => 'sometimes|string|unique:fuel_tickets,fuel_ticket_number,' . $id,
            'date' => 'sometimes|date',
            'product_id' => 'sometimes|exists:products,id',
            'rate' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|numeric|min:0',
            'trip_allowance' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'truck_capacity' => 'nullable|string',
            'truck_number' => 'nullable|string',
            'loading_point' => 'nullable|string',
            'destination' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'driver_phone' => 'nullable|string',
            'truck_provider' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:10240',
            'details' => 'nullable|string',
            'status' => 'sometimes|in:Pending,Approved,Active,Used,Expired,Returned',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($fuelTicket->attachment_path) {
                Storage::disk('public')->delete($fuelTicket->attachment_path);
            }
            $path = $request->file('attachment')->store('fuel_tickets', 'public');
            $validated['attachment_path'] = $path;
        }

        // If status is being changed to Approved, record who approved and when
        if (isset($validated['status']) && $validated['status'] === 'Approved' && $fuelTicket->status !== 'Approved') {
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        }

        $fuelTicket->update($validated);
        
        return response()->json($fuelTicket->load(['product', 'creator', 'approver']));
    }

    /**
     * Remove the specified fuel ticket.
     */
    public function destroy($id)
    {
        $fuelTicket = FuelTicket::findOrFail($id);
        
        // Delete attachment if exists
        if ($fuelTicket->attachment_path) {
            Storage::disk('public')->delete($fuelTicket->attachment_path);
        }
        
        $fuelTicket->delete();
        
        return response()->json(['message' => 'Fuel ticket deleted successfully']);
    }
}
