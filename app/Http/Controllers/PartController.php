<?php

namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\Http\Request;

class PartController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/parts",
     *   summary="Get all parts",
     *   tags={"Parts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of parts")
     * )
     */
    public function index()
    {
        // eager load supplier for performance
        $parts = Part::with('supplier')->orderByDesc('id')->get();
        return response()->json($parts);
    }

    /**
     * @OA\Post(
     *   path="/api/parts",
     *   summary="Create new part",
     *   tags={"Parts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","partNumber","price"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="partNumber", type="string"),
     *       @OA\Property(property="category", type="string"),
     *       @OA\Property(property="brand", type="string"),
     *       @OA\Property(property="vehicleType", type="string"),
     *       @OA\Property(property="costPrice", type="number", format="float"),
     *       @OA\Property(property="price", type="number", format="float"),
     *       @OA\Property(property="stock", type="integer"),
     *       @OA\Property(property="minStock", type="integer"),
     *       @OA\Property(property="maxStock", type="integer"),
     *       @OA\Property(property="supplier_id", type="integer"),
     *       @OA\Property(property="description", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Part created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'partNumber' => 'required|string|max:255|unique:parts,partNumber',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'vehicleType' => 'nullable|string|max:255',
            'costPrice' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minStock' => 'nullable|integer|min:0',
            'maxStock' => 'nullable|integer|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
        ]);

        $part = Part::create($validated);

        // include supplier name in response
        return response()->json($part->load('supplier'), 201);
    }

    /**
     * @OA\Put(
     *   path="/api/parts/{id}",
     *   summary="Update part",
     *   tags={"Parts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="partNumber", type="string"),
     *       @OA\Property(property="category", type="string"),
     *       @OA\Property(property="brand", type="string"),
     *       @OA\Property(property="vehicleType", type="string"),
     *       @OA\Property(property="costPrice", type="number", format="float"),
     *       @OA\Property(property="price", type="number", format="float"),
     *       @OA\Property(property="stock", type="integer"),
     *       @OA\Property(property="minStock", type="integer"),
     *       @OA\Property(property="maxStock", type="integer"),
     *       @OA\Property(property="supplier_id", type="integer"),
     *       @OA\Property(property="description", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Part updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $part = Part::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'partNumber' => 'sometimes|required|string|max:255|unique:parts,partNumber,' . $part->id,
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'vehicleType' => 'nullable|string|max:255',
            'costPrice' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minStock' => 'nullable|integer|min:0',
            'maxStock' => 'nullable|integer|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
        ]);

        $part->update($validated);

        return response()->json($part->load('supplier'));
    }

    /**
     * @OA\Delete(
     *   path="/api/parts/{id}",
     *   summary="Delete part",
     *   tags={"Parts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Part deleted")
     * )
     */
    public function destroy(int $id)
    {
        $part = Part::findOrFail($id);
        $part->delete();

        return response()->json(['message' => 'Part deleted successfully']);
    }
}
