<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/roles",
     *   summary="Get all roles",
     *   tags={"Roles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of roles")
     * )
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($validated);

        return response()->json([
            'success' => true,
            'data' => $role
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role->update($validated);

        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
