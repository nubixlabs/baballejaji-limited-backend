<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Account::with('creator');

        // Apply filters
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|string|max:100',
            'balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $account = Account::create($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('account_created', "Created account: {$account->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'data' => $account->load('creator')
        ], 201);
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $account->load('creator')
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|string|max:100',
            'balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('account_updated', "Updated account: {$account->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully',
            'data' => $account->load('creator')
        ]);
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): JsonResponse
    {
        $accountName = $account->name;
        $account->delete();

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('account_deleted', "Deleted account: {$accountName}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get accounts by type for dropdowns.
     */
    public function getByType(string $type): JsonResponse
    {
        $accounts = Account::active()->byType($type)->orderBy('name')->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }
}