<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\TripPayment;
use Illuminate\Http\Request;

class TripPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = TripPayment::with('trip.truck');

        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'pending';

        $payment = TripPayment::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ], 201);
    }

    public function approve($id)
    {
        $payment = TripPayment::findOrFail($id);
        $payment->status = 'approved';
        $payment->save();

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    public function reject($id)
    {
        $payment = TripPayment::findOrFail($id);
        $payment->status = 'rejected';
        $payment->save();

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }
}
