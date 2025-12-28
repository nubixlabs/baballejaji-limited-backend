<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPayment::with(['customer', 'creator', 'approver']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('invoice_number')) {
            $query->where('invoice_number', $request->invoice_number);
        }

        if ($request->has('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }

        $payments = $query->orderByDesc('created_at')->get();
        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = CustomerPayment::with(['customer', 'creator'])->findOrFail($id);
        return response()->json($payment);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'invoice_number' => 'nullable|string',
            'payment_id' => 'nullable|exists:customer_payments,id',
            'paid_by' => 'nullable|string',
            'received_by' => 'nullable|string',
            'details' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('customer_payments', 'public');
            }

            $currentUserId = auth()->id();

            // Handle multiple payments if sent as logic from frontend, 
            // but for now CustomerPaymentController::store typically handles one.
            // If frontend sends array, we should loop in frontend or create a batch endpoint.
            // Based on frontend logic, we are calling API inside a loop, so single store is fine.

            $payment = CustomerPayment::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'payment_id' => $validated['payment_id'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'paid_by' => $validated['paid_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'details' => $validated['details'] ?? null,
                'attachment_path' => $attachmentPath,
                'created_by' => $currentUserId,
                'status' => 'pending'
            ]);

            // Update customer credit balance if customer exists
            // Usually payment reduces debt (credit balance).
            // Depends on business logic: if credit_balance is amount owed, payment decreases it.
            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    // Assuming credit_balance is "Amount Owed by Customer"
                    $customer->credit_balance = $customer->credit_balance - $validated['amount']; 
                    $customer->save();
                }
            }

            DB::commit();
            return response()->json($payment->load('customer'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating payment: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $payment = CustomerPayment::findOrFail($id);
        
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'paid_by' => 'nullable|string',
            'received_by' => 'nullable|string',
            'details' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,reversed',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // If amount changed, update customer credit balance
            if ($payment->amount != $validated['amount'] && $payment->customer_id) {
                $customer = Customer::find($payment->customer_id);
                if ($customer) {
                    // Revert old amount and apply new amount
                    $customer->credit_balance = $customer->credit_balance + $payment->amount - $validated['amount'];
                    $customer->save();
                }
            }

            // Update payment
            $updateData = [
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'paid_by' => $validated['paid_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'details' => $validated['details'] ?? null,
                'last_modified_by' => auth()->id(),
            ];

            // Explicitly handle status
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            // Explicitly handle approved_by
            if (isset($validated['approved_by'])) {
                $updateData['approved_by'] = $validated['approved_by'];
            }

            $payment->update($updateData);

            DB::commit();
            return response()->json($payment->load('customer', 'creator', 'approver', 'lastModifier'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating payment: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $payment = CustomerPayment::findOrFail($id);
        
        // Revert balance change if customer exists
        if ($payment->customer_id) {
            $customer = Customer::find($payment->customer_id);
            if ($customer) {
                $customer->credit_balance = $customer->credit_balance + $payment->amount;
                $customer->save();
            }
        }

        if ($payment->attachment_path) {
            Storage::disk('public')->delete($payment->attachment_path);
        }

        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }

    public function approve($id)
    {
        $payment = CustomerPayment::findOrFail($id);
        $payment->status = 'approved';
        $payment->approved_by = auth()->id();
        $payment->save();
        
        // If this is a deposit, check if all deposits for parent payment are approved
        if ($payment->payment_id) {
            $parentPayment = CustomerPayment::find($payment->payment_id);
            if ($parentPayment) {
                // Get all deposits for this parent payment
                $allDeposits = CustomerPayment::where('payment_id', $parentPayment->id)->get();
                
                // Check if all deposits are approved
                $allApproved = $allDeposits->every(function($deposit) {
                    return $deposit->status === 'approved';
                });
                
                // If all deposits are approved, auto-approve the parent payment
                if ($allApproved && $parentPayment->status !== 'approved') {
                    $parentPayment->status = 'approved';
                    $parentPayment->approved_by = auth()->id();
                    $parentPayment->save();
                }
            }
        }
        
        return response()->json($payment->load('approver'));
    }
}
