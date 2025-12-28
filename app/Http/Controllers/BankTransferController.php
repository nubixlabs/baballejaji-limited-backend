<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BankTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\BankTransfer::with(['shift']);

        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->latest()->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'amount_transferred' => 'required|numeric|min:0',
            'bank' => 'required|string',
            'transaction_reference' => 'required|string',
            'sent_from' => 'required|string',
            'sender_name' => 'required|string',
            'details' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,approved,rejected',
        ]);

        $bankTransfer = \App\Models\BankTransfer::create($validated);

        return response()->json($bankTransfer, 201);
    }

    public function show($id)
    {
        return \App\Models\BankTransfer::with(['shift'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $bankTransfer = \App\Models\BankTransfer::findOrFail($id);
        
        $validated = $request->validate([
            'shift_id' => 'sometimes|exists:shifts,id',
            'amount_transferred' => 'sometimes|numeric|min:0',
            'bank' => 'sometimes|string',
            'transaction_reference' => 'sometimes|string',
            'sent_from' => 'sometimes|string',
            'sender_name' => 'sometimes|string',
            'details' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,approved,rejected',
        ]);

        $bankTransfer->update($validated);

        return response()->json($bankTransfer);
    }

    public function destroy($id)
    {
        $bankTransfer = \App\Models\BankTransfer::findOrFail($id);
        $bankTransfer->delete();

        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $bankTransfer = \App\Models\BankTransfer::findOrFail($id);
        $bankTransfer->update(['status' => 'Approved']);

        return response()->json($bankTransfer);
    }
}
