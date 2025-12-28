<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\Product;
use Illuminate\Http\Request;

class TankController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/tanks",
     *   summary="Get all tanks",
     *   tags={"Filling Station - Tanks"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of tanks")
     * )
     */
    public function index(Request $request)
    {
        $tanks = Tank::with('product')->orderBy('name')->get();
        return response()->json($tanks);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/tanks/{id}",
     *   summary="Get tank by ID",
     *   tags={"Filling Station - Tanks"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank details")
     * )
     */
    public function show(int $id)
    {
        $tank = Tank::with('product')->findOrFail($id);
        return response()->json($tank);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/tanks",
     *   summary="Create new tank",
     *   tags={"Filling Station - Tanks"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","product_id","capacity"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="product_id", type="integer"),
     *       @OA\Property(property="capacity", type="number"),
     *       @OA\Property(property="content", type="number"),
     *       @OA\Property(property="level", type="number"),
     *       @OA\Property(property="atg_status", type="string"),
     *       @OA\Property(property="group", type="string"),
     *       @OA\Property(property="fillup_id", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Tank created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'capacity' => 'required|numeric|min:0',
            'content' => 'nullable|numeric|min:0',
            'level' => 'nullable|numeric|min:0|max:100',
            'atg_status' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'fillup_id' => 'nullable|string|max:255',
        ]);

        // Calculate level if content and capacity are provided
        if (isset($validated['content']) && isset($validated['capacity']) && $validated['capacity'] > 0) {
            $validated['level'] = ($validated['content'] / $validated['capacity']) * 100;
        }

        $tank = Tank::create($validated);
        return response()->json($tank->load('product'), 201);
    }

    /**
     * @OA\Put(
     *   path="/api/filling/tanks/{id}",
     *   summary="Update tank",
     *   tags={"Filling Station - Tanks"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="product_id", type="integer"),
     *       @OA\Property(property="capacity", type="number"),
     *       @OA\Property(property="content", type="number"),
     *       @OA\Property(property="level", type="number"),
     *       @OA\Property(property="atg_status", type="string"),
     *       @OA\Property(property="group", type="string"),
     *       @OA\Property(property="fillup_id", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Tank updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $tank = Tank::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'product_id' => 'sometimes|required|exists:products,id',
            'capacity' => 'sometimes|required|numeric|min:0',
            'content' => 'nullable|numeric|min:0',
            'level' => 'nullable|numeric|min:0|max:100',
            'atg_status' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'fillup_id' => 'nullable|string|max:255',
        ]);

        // Recalculate level if content or capacity changed
        if (isset($validated['content']) || isset($validated['capacity'])) {
            $content = $validated['content'] ?? $tank->content;
            $capacity = $validated['capacity'] ?? $tank->capacity;
            if ($capacity > 0) {
                $validated['level'] = ($content / $capacity) * 100;
            }
        }

        $tank->update($validated);
        return response()->json($tank->load('product'));
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/tanks/{id}",
     *   summary="Delete tank",
     *   tags={"Filling Station - Tanks"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tank deleted")
     * )
     */
    public function destroy(int $id)
    {
        $tank = Tank::findOrFail($id);
        $tank->delete();

        return response()->json(['message' => 'Tank deleted successfully']);
    }
}



