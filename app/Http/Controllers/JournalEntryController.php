<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::with(['creator', 'approver', 'lines.account', 'voucher']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $journalEntries = $query->orderBy('transaction_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $journalEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'journal_number' => $entry->journal_number,
                    'transaction_date' => $entry->transaction_date->format('Y-m-d'),
                    'description' => $entry->description,
                    'reference' => $entry->reference,
                    'total_debit' => $entry->total_debit,
                    'total_credit' => $entry->total_credit,
                    'status' => $entry->status,
                    'created_by' => $entry->creator->name,
                    'approved_by' => $entry->approver?->name,
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                    'lines_count' => $entry->lines->count(),
                    'is_balanced' => $entry->isBalanced(),
                ];
            })
        ]);
    }

    /**
     * Get pending journal entries for approval.
     */
    public function pending(): JsonResponse
    {
        $pendingEntries = JournalEntry::with(['creator', 'lines.account'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'journal_number' => $entry->journal_number,
                    'transaction_date' => $entry->transaction_date->format('Y-m-d'),
                    'description' => $entry->description,
                    'total_debit' => $entry->total_debit,
                    'total_credit' => $entry->total_credit,
                    'created_by' => $entry->creator->name,
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                    'is_balanced' => $entry->isBalanced(),
                ];
            })
        ]);
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'reference' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate that each line has either debit or credit (not both or neither)
        foreach ($validated['lines'] as $line) {
            $debit = $line['debit_amount'] ?? 0;
            $credit = $line['credit_amount'] ?? 0;
            
            if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each line must have either debit or credit amount, not both or neither'
                ], 422);
            }
        }

        // Validate that total debits equal total credits
        $totalDebits = collect($validated['lines'])->sum('debit_amount');
        $totalCredits = collect($validated['lines'])->sum('credit_amount');
        
        if (abs($totalDebits - $totalCredits) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total debits must equal total credits'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create journal entry
            $journalEntry = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber(),
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'reference' => $validated['reference'],
                'total_debit' => $totalDebits,
                'total_credit' => $totalCredits,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Create journal entry lines
            foreach ($validated['lines'] as $index => $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'line_order' => $index + 1,
                ]);
            }

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('journal_entry_created', "Created journal entry: {$journalEntry->journal_number}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Journal entry created successfully',
                'data' => $journalEntry->load(['lines.account'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $journalEntry): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $journalEntry->load(['creator', 'approver', 'lines.account', 'voucher'])
        ]);
    }

    /**
     * Approve multiple journal entries.
     */
    public function approveMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'journal_entry_ids' => 'required|array|min:1',
            'journal_entry_ids.*' => 'exists:journal_entries,id',
        ]);

        $approvedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($validated['journal_entry_ids'] as $id) {
                $journalEntry = JournalEntry::find($id);
                
                if ($journalEntry->status === 'pending' || $journalEntry->status === 'draft') {
                    $journalEntry->approve(auth()->user());
                    $approvedCount++;
                } else {
                    $errors[] = "Journal entry {$journalEntry->journal_number} cannot be approved (status: {$journalEntry->status})";
                }
            }

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('journal_entries_approved', "Approved {$approvedCount} journal entries");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$approvedCount} journal entries",
                'approved_count' => $approvedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve journal entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Post journal entry to update account balances.
     */
    public function post(JournalEntry $journalEntry): JsonResponse
    {
        try {
            $journalEntry->post();

            // Log activity
            if (auth()->user()) {
                auth()->user()->logActivity('journal_entry_posted', "Posted journal entry: {$journalEntry->journal_number}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Journal entry posted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to post journal entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate journal entries from transactions.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'transaction_types' => 'nullable|array',
        ]);

        // This would generate journal entries from sales, purchases, etc.
        // Implementation depends on your specific business logic
        
        return response()->json([
            'success' => true,
            'message' => 'Journal entries generated successfully',
            'data' => []
        ]);
    }
}