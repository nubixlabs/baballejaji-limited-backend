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
        $roles = Role::all();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }
}
