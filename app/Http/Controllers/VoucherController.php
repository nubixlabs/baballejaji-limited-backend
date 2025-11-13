<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherLineItem;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voucher::with(['sourceAccount', 'creator', 'approver', 'lineItems.account']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('voucher_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('voucher_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('sourceAccount', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $vouchers = $query->orderBy('voucher_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $vouchers->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'voucher_number' => $voucher->voucher_number,
                    'voucher_date' => $voucher->voucher_date->format('Y-m-d'),
                    'source_account' => $voucher->sourceAccount->name,
                    'description' => $voucher->description,
                    'total_amount' => $voucher->total_amount,
                    'status' => $voucher->status,
                    'created_by' => $voucher->creator->name,
                    'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
                    'line_items_count' => $voucher->lineItems->count(),
                ];
            })
        ]);
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'voucher_number' => 'nullable|string|max:50|unique:vouchers,voucher_number',
            'voucher_date' => 'required|date',
            'source_account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'line_items' => 'required|array|min:1',
            'line_items.*.account_id' => 'required|exists:accounts,id',
            'line_items.*.description' => 'required|string',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.type' => 'required|in:debit,credit',
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('vouchers', 'public');
            }

            // Create voucher
            $voucher = Voucher::create([
                'voucher_number' => $validated['voucher_number'] ?? Voucher::generateVoucherNumber(),
                'voucher_date' => $validated['voucher_date'],
                'source_account_id' => $validated['source_account_id'],
                'description' => $validated['description'],
                'attachment_path' => $attachmentPath,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Create line items
            $totalAmount = 0;
            foreach ($validated['line_items'] as $index => $lineItem) {
                VoucherLineItem::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $lineItem['account_id'],
                    'description' => $lineItem['description'],
                    'amount' => $lineItem['amount'],
                    'type' => $lineItem['type'],
                    'line_order' => $index + 1,
                ]);
                $totalAmount += $lineItem['amount'];
            }

            // Update total amount
            $voucher->update(['total_amount' => $totalAmount]);

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('voucher_created', "Created voucher: {$voucher->voucher_number}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher created successfully',
                'data' => $voucher->load(['sourceAccount', 'lineItems.account'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified voucher.
     */
    public function show(Voucher $voucher): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $voucher->load(['sourceAccount', 'creator', 'approver', 'lineItems.account'])
        ]);
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be updated'
            ], 422);
        }

        $validated = $request->validate([
            'voucher_date' => 'required|date',
            'source_account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'line_items' => 'required|array|min:1',
            'line_items.*.account_id' => 'required|exists:accounts,id',
            'line_items.*.description' => 'required|string',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.type' => 'required|in:debit,credit',
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment
                if ($voucher->attachment_path) {
                    Storage::disk('public')->delete($voucher->attachment_path);
                }
                $attachmentPath = $request->file('attachment')->store('vouchers', 'public');
                $voucher->attachment_path = $attachmentPath;
            }

            // Update voucher
            $voucher->update([
                'voucher_date' => $validated['voucher_date'],
                'source_account_id' => $validated['source_account_id'],
                'description' => $validated['description'],
            ]);

            // Delete existing line items
            $voucher->lineItems()->delete();

            // Create new line items
            $totalAmount = 0;
            foreach ($validated['line_items'] as $index => $lineItem) {
                VoucherLineItem::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $lineItem['account_id'],
                    'description' => $lineItem['description'],
                    'amount' => $lineItem['amount'],
                    'type' => $lineItem['type'],
                    'line_order' => $index + 1,
                ]);
                $totalAmount += $lineItem['amount'];
            }

            // Update total amount
            $voucher->update(['total_amount' => $totalAmount]);

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('voucher_updated', "Updated voucher: {$voucher->voucher_number}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher updated successfully',
                'data' => $voucher->load(['sourceAccount', 'lineItems.account'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified voucher.
     */
    public function destroy(Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be deleted'
            ], 422);
        }

        $voucherNumber = $voucher->voucher_number;

        // Delete attachment file
        if ($voucher->attachment_path) {
            Storage::disk('public')->delete($voucher->attachment_path);
        }

        $voucher->delete();

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('voucher_deleted', "Deleted voucher: {$voucherNumber}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully'
        ]);
    }

    /**
     * Approve the specified voucher.
     */
    public function approve(Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== 'draft' && $voucher->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Voucher cannot be approved'
            ], 422);
        }

        try {
            $voucher->approve(auth()->user());

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('voucher_approved', "Approved voucher: {$voucher->voucher_number}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Voucher approved successfully',
                'data' => $voucher->load(['sourceAccount', 'lineItems.account', 'journalEntry'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit voucher for approval.
     */
    public function submit(Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be submitted'
            ], 422);
        }

        $voucher->update(['status' => 'pending']);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('voucher_submitted', "Submitted voucher for approval: {$voucher->voucher_number}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher submitted for approval'
        ]);
    }
}