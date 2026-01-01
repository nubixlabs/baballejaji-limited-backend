<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['role', 'userGroup']);

        // Apply filters
        if ($request->filled('group')) {
            $query->where('user_group_id', $request->group);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'username' => $user->username,
                    'last_login' => $user->last_login_at?->format('Y-m-d H:i:s'),
                    'group' => $user->userGroup?->name,
                    'group_id' => $user->user_group_id,
                    'role' => $user->role?->name,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'user_group_id' => 'nullable|exists:user_groups,id',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'exists:filling_stations,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_created', "Created user: {$user->name}");
        }

        if ($request->has('filling_station_ids')) {
            $user->fillingStations()->sync($request->filling_station_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load(['role', 'userGroup'])
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user->load(['role', 'userGroup'])
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'user_group_id' => 'nullable|exists:user_groups,id',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'exists:filling_stations,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_updated', "Updated user: {$user->name}");
        }

        if ($request->has('filling_station_ids')) {
            $user->fillingStations()->sync($request->filling_station_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load(['role', 'userGroup'])
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $userName = $user->name;
        $user->delete();

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_deleted', "Deleted user: {$userName}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_status_changed', "User {$user->name} {$status}");
        }

        return response()->json([
            'success' => true,
            'message' => "User {$status} successfully",
            'data' => $user
        ]);
    }
}