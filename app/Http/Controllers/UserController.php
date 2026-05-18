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
        $query = User::with(['role', 'userGroup', 'fillingStation']);

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
                    'user_group' => $user->userGroup ? [
                        'id' => $user->userGroup->id,
                        'name' => $user->userGroup->name,
                    ] : null,
                    'group_id' => $user->user_group_id,
                    'filling_station' => $user->fillingStation ? [
                        'id' => $user->fillingStation->id,
                        'name' => $user->fillingStation->name,
                        'code' => $user->fillingStation->code,
                    ] : null,
                    'filling_station_id' => $user->filling_station_id,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'display_name' => $user->role->display_name ?? $user->role->name,
                    ] : null,
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
            'user_group_id' => 'nullable|string',
            'filling_station_id' => 'nullable|exists:filling_stations,id',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'exists:filling_stations,id',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        // Sync filling station assignments
        if ($request->has('filling_station_ids') && is_array($request->filling_station_ids)) {
            $stationIds = array_map('intval', $request->filling_station_ids);
            $user->fillingStations()->sync($stationIds);
        }

        if (auth()->user()) {
            auth()->user()->logActivity('user_created', "Created user: {$user->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load(['role', 'userGroup', 'fillingStation', 'fillingStations'])
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user->load(['role', 'userGroup', 'fillingStation', 'fillingStations'])
        ]);
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'user_group_id' => 'nullable|string',
            'filling_station_id' => 'nullable|exists:filling_stations,id',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'exists:filling_stations,id',
            'role_id' => 'sometimes|required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        // Sync filling station assignments
        if ($request->has('filling_station_ids') && is_array($request->filling_station_ids)) {
            $stationIds = array_map('intval', $request->filling_station_ids);
            $user->fillingStations()->sync($stationIds);
        } elseif ($request->has('filling_station_ids')) {
            $user->fillingStations()->sync([]);
        }

        if (auth()->user()) {
            auth()->user()->logActivity('user_updated', "Updated user: {$user->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load(['role', 'userGroup', 'fillingStation'])
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $userName = $user->name;
        $user->delete();

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