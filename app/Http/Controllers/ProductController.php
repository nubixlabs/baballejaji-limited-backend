<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function getFillingStationId(Request $request): ?int
    {
        $id = $request->header('X-Filling-Station-Id');
        return ($id && is_numeric($id)) ? (int) $id : null;
    }

    private function applyFillingStationScope($query, ?int $fillingStationId): void
    {
        if ($fillingStationId) {
            $query->whereHas('fillingStations', function($q) use ($fillingStationId) {
                $q->where('filling_station_id', $fillingStationId);
            });
        }
    }

    private function injectPivotPricing(Product $product, int $fillingStationId): Product
    {
        $pivot = $product->fillingStations
            ->where('id', $fillingStationId)
            ->first()
            ?->pivot;

        if ($pivot) {
            $product->cost_price = $pivot->cost_price;
            $product->retail_price = $pivot->retail_price;
            $product->dealer_price = $pivot->dealer_price;
            $product->bulk_price = $pivot->bulk_price;
        } else {
            $product->cost_price = null;
            $product->retail_price = null;
            $product->dealer_price = null;
            $product->bulk_price = null;
        }
        return $product;
    }

    private function updatePivotPricing(Product $product, int $fillingStationId, array $prices): void
    {
        $product->fillingStations()->updateExistingPivot($fillingStationId, $prices);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/products",
     *   summary="Get all products",
     *   tags={"Filling Station - Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of products")
     * )
     */
    public function index(Request $request)
    {
        $query = Product::query()->with('fillingStations');
        $fillingStationId = $this->getFillingStationId($request);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('exclude_category')) {
            $query->where(function($q) use ($request) {
                $q->where('category', '!=', $request->exclude_category)
                  ->orWhereNull('category');
            });
        }

        $this->applyFillingStationScope($query, $fillingStationId);

        $products = $query->orderBy('code')->get();

        if ($fillingStationId) {
            $products->each(fn($p) => $this->injectPivotPricing($p, $fillingStationId));
        }

        return response()->json($products);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/products/{id}",
     *   summary="Get product by ID",
     *   tags={"Filling Station - Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Product details")
     * )
     */
    public function show(Request $request, int $id)
    {
        $query = Product::with(['creator', 'lastModifier', 'fillingStations']);
        $fillingStationId = $this->getFillingStationId($request);
        $this->applyFillingStationScope($query, $fillingStationId);
        $product = $query->findOrFail($id);

        if ($fillingStationId) {
            $this->injectPivotPricing($product, $fillingStationId);
        }

        return response()->json($product);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/products",
     *   summary="Create new product",
     *   tags={"Filling Station - Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"code","name"},
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="si_unit", type="string"),
     *       @OA\Property(property="quantity", type="number"),
     *       @OA\Property(property="cost_price", type="number"),
     *       @OA\Property(property="retail_price", type="number"),
     *       @OA\Property(property="dealer_price", type="number"),
     *       @OA\Property(property="bulk_price", type="number"),
     *       @OA\Property(property="re_order_level", type="number"),
     *       @OA\Property(property="iot_product", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Product created")
     * )
     */
    public function store(Request $request)
    {
        $fillingStationId = $this->getFillingStationId($request);

        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:products,code',
            'name' => 'required|string|max:255',
            'si_unit' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            're_order_level' => 'nullable|numeric|min:0',
            'iot_product' => 'nullable|string|max:255',
            'based_on' => 'nullable|string|max:255',
            'based_on_rate' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'filling_station_id' => 'nullable|integer|exists:filling_stations,id',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'integer|exists:filling_stations,id',
            // Pricing fields go to pivot
            'cost_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'bulk_price' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['last_modified_by'] = $request->user()->id;

        // Extract pricing for pivot
        $pivotPrices = array_filter([
            'cost_price' => $request->cost_price,
            'retail_price' => $request->retail_price,
            'dealer_price' => $request->dealer_price,
            'bulk_price' => $request->bulk_price,
        ], fn($v) => $v !== null);

        // Determine which stations to assign
        if ($fillingStationId && !$request->has('filling_station_ids')) {
            $validated['filling_station_id'] = $fillingStationId;
            $stationIds = [$fillingStationId];
        } else {
            $stationIds = $request->filling_station_ids ?? [];
        }

        if (!$validated['filling_station_id'] && !empty($stationIds)) {
            $validated['filling_station_id'] = $stationIds[0];
        }

        unset($validated['cost_price'], $validated['retail_price'], $validated['dealer_price'], $validated['bulk_price']);

        $product = Product::create($validated);

        // Sync pivot with stations and pricing
        if (!empty($stationIds)) {
            $syncData = [];
            foreach ($stationIds as $stationId) {
                $syncData[$stationId] = $pivotPrices;
            }
            $product->fillingStations()->sync($syncData);
        } elseif ($validated['filling_station_id']) {
            $product->fillingStations()->sync([$validated['filling_station_id'] => $pivotPrices]);
        }

        $product->load('fillingStations');

        if ($fillingStationId) {
            $this->injectPivotPricing($product, $fillingStationId);
        }

        return response()->json($product, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/filling/products/{id}",
     *   summary="Update product",
     *   tags={"Filling Station - Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="code", type="string"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="si_unit", type="string"),
     *       @OA\Property(property="quantity", type="number"),
     *       @OA\Property(property="cost_price", type="number"),
     *       @OA\Property(property="retail_price", type="number"),
     *       @OA\Property(property="dealer_price", type="number"),
     *       @OA\Property(property="bulk_price", type="number"),
     *       @OA\Property(property="re_order_level", type="number"),
     *       @OA\Property(property="iot_product", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Product updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $fillingStationId = $this->getFillingStationId($request);

        $query = Product::query();
        $this->applyFillingStationScope($query, $fillingStationId);
        $product = $query->findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255|unique:products,code,' . $product->id,
            'name' => 'sometimes|required|string|max:255',
            'si_unit' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            're_order_level' => 'nullable|numeric|min:0',
            'iot_product' => 'nullable|string|max:255',
            'based_on' => 'nullable|string|max:255',
            'based_on_rate' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'filling_station_id' => 'nullable|integer|exists:filling_stations,id',
            'filling_station_ids' => 'nullable|array',
            'filling_station_ids.*' => 'integer|exists:filling_stations,id',
            'cost_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'bulk_price' => 'nullable|numeric|min:0',
        ]);

        $validated['last_modified_by'] = $request->user()->id;

        // At filling-station level, prevent changing core product fields
        if ($fillingStationId) {
            unset($validated['code'], $validated['name'], $validated['si_unit'], $validated['category']);
        }

        // Extract pricing fields — these go to pivot, not product
        $pivotPrices = [];
        foreach (['cost_price', 'retail_price', 'dealer_price', 'bulk_price'] as $field) {
            if ($request->has($field)) {
                $pivotPrices[$field] = $request->$field;
            }
            unset($validated[$field]);
        }

        if ($request->has('filling_station_ids') && !empty($request->filling_station_ids) && !$request->has('filling_station_id')) {
            $validated['filling_station_id'] = $request->filling_station_ids[0];
        }

        $product->update($validated);

        // Handle pivot station sync and pricing
        if ($request->has('filling_station_ids')) {
            $syncData = [];
            foreach ($request->filling_station_ids as $stationId) {
                $syncData[$stationId] = $pivotPrices;
            }
            $product->fillingStations()->sync($syncData);
        } elseif ($fillingStationId && !empty($pivotPrices)) {
            // Update pricing for the current station only
            $this->updatePivotPricing($product, $fillingStationId, $pivotPrices);
        }

        $product->load('fillingStations');

        if ($fillingStationId) {
            $this->injectPivotPricing($product, $fillingStationId);
        }

        return response()->json($product);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/products/{id}",
     *   summary="Delete product",
     *   tags={"Filling Station - Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Product deleted")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        $query = Product::query();
        $this->applyFillingStationScope($query, $this->getFillingStationId($request));
        $product = $query->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * Get inventory summary for products
     */
    public function inventory(Request $request)
    {
        $fillingStationId = $this->getFillingStationId($request);

        $query = Product::with([
            'fillingStations',
            'priceAdjustments' => function($query) {
                $query->latest()->take(1);
            }
        ]);

        $this->applyFillingStationScope($query, $fillingStationId);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->get();

        $inventory = $products->map(function($product) use ($fillingStationId) {
            $costPrice = $product->cost_price;
            if ($fillingStationId) {
                $pivot = $product->fillingStations
                    ->where('id', $fillingStationId)->first()?->pivot;
                $costPrice = $pivot->cost_price ?? $costPrice;
            }
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'purchased_qty' => 0,
                'received_qty' => 0,
                'dispensed_qty' => 0,
                'bulk_sales' => 0,
                'balance_qty' => $product->quantity,
                'inventory_value' => $product->quantity * ($costPrice ?? 0),
            ];
        });

        return response()->json($inventory);
    }
}



