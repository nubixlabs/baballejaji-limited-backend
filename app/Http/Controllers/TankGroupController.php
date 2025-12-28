<?php

namespace App\Http\Controllers;

use App\Models\TankGroup;
use Illuminate\Http\Request;

class TankGroupController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/tank-groups",
     *   summary="Get all tank groups",
     *   tags={"Filling Station - Tank Groups"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of tank groups")
     * )
     */
    public function index()
    {
        $groups = TankGroup::orderBy('name')->get();
        return response()->json($groups);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/tank-groups/{id}",
     *   summary="Get tank group by ID",
     *   tags={"Filling Station - Tank Groups"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank group details")
     * )
     */
    public function show(int $id)
    {
        $group = TankGroup::findOrFail($id);
        return response()->json($group);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/tank-groups",
     *   summary="Create new tank group",
     *   tags={"Filling Station - Tank Groups"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="description", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Tank group created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tank_groups,name',
            'description' => 'nullable|string',
        ]);

        $group = TankGroup::create($validated);
        return response()->json($group, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/filling/tank-groups/{id}",
     *   summary="Update tank group",
     *   tags={"Filling Station - Tank Groups"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="description", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Tank group updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $group = TankGroup::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:tank_groups,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $group->update($validated);
        return response()->json($group);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/tank-groups/{id}",
     *   summary="Delete tank group",
     *   tags={"Filling Station - Tank Groups"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank group deleted")
     * )
     */
    public function destroy(int $id)
    {
        $group = TankGroup::findOrFail($id);
        $group->delete();

        return response()->json(['message' => 'Tank group deleted successfully']);
    }
}



