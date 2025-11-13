<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserGroupController extends Controller
{
    /**
     * Display a listing of user groups.
     */
    public function index(): JsonResponse
    {
        $userGroups = UserGroup::withCount('users')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $userGroups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'users_count' => $group->users_count,
                    'permissions' => $group->permissions,
                    'created_at' => $group->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    /**
     * Store a newly created user group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_groups',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $userGroup = UserGroup::create($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_group_created', "Created user group: {$userGroup->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User group created successfully',
            'data' => $userGroup
        ], 201);
    }

    /**
     * Display the specified user group.
     */
    public function show(UserGroup $userGroup): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $userGroup->load('users')
        ]);
    }

    /**
     * Update the specified user group.
     */
    public function update(Request $request, UserGroup $userGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_groups,name,' . $userGroup->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $userGroup->update($validated);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_group_updated', "Updated user group: {$userGroup->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User group updated successfully',
            'data' => $userGroup
        ]);
    }

    /**
     * Remove the specified user group.
     */
    public function destroy(UserGroup $userGroup): JsonResponse
    {
        // Check if group has users
        if ($userGroup->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user group that has users assigned to it'
            ], 422);
        }

        $groupName = $userGroup->name;
        $userGroup->delete();

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('user_group_deleted', "Deleted user group: {$groupName}");
        }

        return response()->json([
            'success' => true,
            'message' => 'User group deleted successfully'
        ]);
    }
}