<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/suppliers",
     *   summary="Get all suppliers",
     *   tags={"Suppliers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of suppliers")
     * )
     */
    public function index()
    {
        $suppliers = Supplier::withCount('parts')->orderBy('name')->get();
        return response()->json($suppliers);
    }

    /**
     * @OA\Get(
     *   path="/api/suppliers/{id}",
     *   summary="Get supplier by ID",
     *   tags={"Suppliers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supplier details")
     * )
     */
    public function show(int $id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    /**
     * @OA\Post(
     *   path="/api/suppliers",
     *   summary="Create new supplier",
     *   tags={"Suppliers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="contactPerson", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="phone", type="string"),
     *       @OA\Property(property="address", type="string"),
     *       @OA\Property(property="city", type="string"),
     *       @OA\Property(property="state", type="string"),
     *       @OA\Property(property="country", type="string"),
     *       @OA\Property(property="paymentTerms", type="string"),
     *       @OA\Property(property="notes", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Supplier created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contactPerson' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'paymentTerms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create(array_merge($validated, [
            'rating' => 0,
            'totalOrders' => 0,
            'totalValue' => 0,
            'lastOrderDate' => null,
            'status' => 'active',
        ]));

        return response()->json($supplier, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/suppliers/{id}",
     *   summary="Update supplier",
     *   tags={"Suppliers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="contactPerson", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="phone", type="string"),
     *       @OA\Property(property="address", type="string"),
     *       @OA\Property(property="city", type="string"),
     *       @OA\Property(property="state", type="string"),
     *       @OA\Property(property="country", type="string"),
     *       @OA\Property(property="paymentTerms", type="string"),
     *       @OA\Property(property="notes", type="string"),
     *       @OA\Property(property="status", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Supplier updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'contactPerson' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'paymentTerms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $supplier->update($validated);
        return response()->json($supplier);
    }

    /**
     * @OA\Delete(
     *   path="/api/suppliers/{id}",
     *   summary="Delete supplier",
     *   tags={"Suppliers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supplier deleted")
     * )
     */
    public function destroy(int $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted']);
    }
}
