<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Supplier;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['supplier', 'purchase', 'creator', 'shift']);

        // Apply filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'supplier' => $payment->supplier->name,
                    'supplier_id' => $payment->supplier_id,
                    'purchase_id' => $payment->purchase_id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'shift_id' => $payment->shift_id,
                    'sales_revenue' => $payment->sales_revenue ?? $payment->shift?->sales_revenue ?? 0,
                    'paid_by' => $payment->paid_by,
                    'received_by' => $payment->received_by,
                    'details' => $payment->details,
                    'status' => $payment->status,
                    'created_by' => $payment->creator->name ?? 'System',
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,pos_transfer,expenses,credit_sale,other',
            'reference_number' => 'nullable|string|max:255',
            'shift_id' => 'nullable|string|max:50',
            'sales_revenue' => 'nullable|numeric|min:0',
            'paid_by' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('payments', 'public');
            }

            // Create payment
            $payment = Payment::create([
                'payment_number' => Payment::generatePaymentNumber(),
                'payment_date' => $validated['payment_date'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_id' => $validated['purchase_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'shift_id' => $validated['shift_id'] ?? null,
                'sales_revenue' => $validated['sales_revenue'] ?? null,
                'paid_by' => $validated['paid_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'details' => $validated['details'] ?? null,
                'attachment_path' => $attachmentPath,
                // New payments start as pending by default
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('payment_created', "Created payment: {$payment->payment_number}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment->load(['supplier', 'purchase', 'creator'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['supplier', 'purchase', 'creator', 'shift']);

        // Fallback to shift's sales_revenue if payment's is null
        if (is_null($payment->sales_revenue) && $payment->shift) {
            $payment->sales_revenue = $payment->shift->sales_revenue;
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update cancelled payment'
            ], 422);
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,pos_transfer,expenses,credit_sale,other',
            'reference_number' => 'nullable|string|max:255',
            'shift_id' => 'nullable|string|max:50',
            'sales_revenue' => 'nullable|numeric|min:0',
            'paid_by' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'nullable|in:pending,completed,cancelled',
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment
                if ($payment->attachment_path) {
                    Storage::disk('public')->delete($payment->attachment_path);
                }
                $attachmentPath = $request->file('attachment')->store('payments', 'public');
                $validated['attachment_path'] = $attachmentPath;
            }

            $payment->update($validated);

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('payment_updated', "Updated payment: {$payment->payment_number}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->load(['supplier', 'purchase', 'creator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $paymentNumber = $payment->payment_number;

        // Delete attachment file
        if ($payment->attachment_path) {
            Storage::disk('public')->delete($payment->attachment_path);
        }

        $payment->delete();

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('payment_deleted', "Deleted payment: {$paymentNumber}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully'
        ]);
    }
}