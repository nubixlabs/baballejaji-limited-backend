<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\StockLevel;
use App\Models\DailySale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShiftController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/shifts",
     *   summary="Get all shifts",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of shifts")
     * )
     */
    public function index(Request $request)
    {
        // Auto-expire shifts
        Shift::where('status', 'open')
             ->where('expiry_date', '<', now())
             ->update(['status' => 'expired']);

        $query = Shift::query();

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by shift ID
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        $shifts = $query->orderByDesc('date')->orderByDesc('id')->get();
        return response()->json($shifts);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/shifts/{id}",
     *   summary="Get shift by ID",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Shift details")
     * )
     */
    public function show(int $id)
    {
        $relations = [
            'stockLevels.product',
            'bulkSales',
            'bulkSales.items.product',
            'openedByUser',
            'closedByUser',
            'approvedByUser'
        ];
        if (\Illuminate\Support\Facades\Schema::hasTable('daily_sales')) {
            $relations[] = 'dailySales.product';
        }
        $shift = Shift::with($relations)->findOrFail($id);
        
        // Calculate Bulk Sales Revenue
        $shift->bulk_sales_revenue = $shift->bulkSales
            ->where('payment_status', 'approved')
            ->sum('grand_total');

        // Calculate Retail Sales Revenue from nozzle readings
        $retailSales = 0;
        if ($shift->nozzle_readings && is_array($shift->nozzle_readings)) {
            foreach ($shift->nozzle_readings as $reading) {
                $qtySold = $reading['qty_sold'] ?? 0;
                $retailPrice = 0;
                if (isset($reading['product_name'])) {
                    $product = \App\Models\Product::where('name', $reading['product_name'])->first();
                    if ($product) {
                        $retailPrice = $product->retail_price ?? 0;
                    }
                }
                $retailSales += $qtySold * $retailPrice;
            }
        }
        $shift->retail_sales_revenue = $retailSales;

        // Dealer Sales Revenue (placeholder for now)
        $shift->dealer_sales_revenue = 0;

        // Shift Revenue = Retail Sales Revenue + Bulk Sales Revenue
        $cashGiven = 0;
        $creditSales = 0;
        if ($shift->nozzle_readings && is_array($shift->nozzle_readings)) {
            foreach ($shift->nozzle_readings as $reading) {
                $qtySold = $reading['qty_sold'] ?? 0;
                $product = null;
                if (isset($reading['product_name'])) {
                    $product = \App\Models\Product::where('name', $reading['product_name'])->first();
                }
                $price = $product ? ($product->retail_price ?? 0) : 0;
                $cashGiven += $qtySold * $price;
            }
        }
        // Add credit sales data
        if ($shift->credit_sales_data && is_array($shift->credit_sales_data)) {
            foreach ($shift->credit_sales_data as $sale) {
                $qty = $sale['quantity'] ?? 0;
                $productId = $sale['product_id'] ?? null;
                $discount = $sale['discount'] ?? 0;
                $product = $productId ? \App\Models\Product::find($productId) : null;
                $price = $product ? ($product->retail_price ?? 0) : 0;
                $creditSales += ($qty * $price) - $discount;
            }
        }
        $shift->cash_given = $cashGiven;
        $shift->shift_revenue = $shift->bulk_sales_revenue + $shift->retail_sales_revenue;
        $shift->total_revenue = $shift->shift_revenue;

        // Calculate margin (Total Revenue - Cost - Expenses)
        $totalCost = 0;
        if ($shift->nozzle_readings && is_array($shift->nozzle_readings)) {
            foreach ($shift->nozzle_readings as $reading) {
                $qtySold = $reading['qty_sold'] ?? 0;
                $product = null;
                if (isset($reading['product_name'])) {
                    $product = \App\Models\Product::where('name', $reading['product_name'])->first();
                }
                $costPrice = $product ? ($product->cost_price ?? 0) : 0;
                $totalCost += $qtySold * $costPrice;
            }
        }

        // Calculate total expenses
        $totalExpenses = 0;
        if ($shift->expenses_data && is_array($shift->expenses_data)) {
            foreach ($shift->expenses_data as $expense) {
                $totalExpenses += $expense['amount'] ?? 0;
            }
        }
        $shift->total_expenses = $totalExpenses;

        $shift->margin = $shift->total_revenue - $totalCost - $totalExpenses;

        // Cashback given
        $cashbackGiven = 0;
        if ($shift->cashbacks_data && is_array($shift->cashbacks_data)) {
            foreach ($shift->cashbacks_data as $cb) {
                $cashbackGiven += $cb['amount_given'] ?? 0;
            }
        }
        $shift->cashback_given = $cashbackGiven;

        // Amount remitted and unremitted
        $shift->amount_remitted = $shift->cash_given - $cashbackGiven;
        $shift->amount_unremitted = $cashbackGiven;
        $shift->change_owed = 0;

        // Fillup Payments - Calculate from approved purchases on this shift's date
        $fillupPayments = 0;
        if ($shift->date) {
            $purchases = \App\Models\Purchase::where('status', 'approved')
                ->whereDate('purchase_date', $shift->date)
                ->get();
            foreach ($purchases as $purchase) {
                $fillupPayments += $purchase->grand_total ?? 0;
            }
        }
        $shift->fillup_payments = $fillupPayments;

        // Include credit retail sales (retail sales with payment_method = 'credit' for this shift)
        $creditRetailSales = \App\Models\RetailSale::with('items.product')
            ->where('shift_id', $shift->id)
            ->where('payment_method', 'credit')
            ->get()
            ->map(function ($sale) {
                $productName = '';
                $productId = null;
                $quantity = 0;
                if ($sale->items && $sale->items->count() > 0) {
                    $item = $sale->items->first();
                    $productName = $item->product->name ?? '';
                    $productId = $item->product_id;
                    $quantity = $item->quantity;
                }
                return [
                    'customer_name' => $sale->notes ? explode("\n", explode(":", $sale->notes)[1] ?? '')[0] ?? '' : '',
                    'product_name' => $productName,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'amount' => $sale->grand_total,
                    'payment_method' => $sale->payment_method,
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date,
                ];
            });
        $shift->credit_retail_sales = $creditRetailSales;

        return response()->json($shift);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/shifts",
     *   summary="Create new shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","date"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="date", type="string", format="date")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Shift created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'duration' => 'required|string',
            'nozzle_readings' => 'nullable|array',
            'nozzle_readings.*.nozzle_id' => 'required|integer|exists:nozzles,id',
            'nozzle_readings.*.opening_reading' => 'required|numeric|min:0',
        ]);

        // Check if there is already an open shift
        $openShift = Shift::where('status', 'open')->first();
        if ($openShift) {
            // Check if it's expired before blocking
            if ($openShift->expiry_date && $openShift->expiry_date < now()) {
                $openShift->update(['status' => 'expired']);
            } else {
                return response()->json([
                    'message' => 'There is still an open or pending shift. Please close and approve it before opening a new shift.'
                ], 400);
            }
        }

        // Get the last shift record for this station (automatically scoped by trait if header exists)
        $lastShift = Shift::orderBy('id', 'desc')->first();

        // Generate next shift id
        $nextId = $lastShift ? intval($lastShift->shift_id) + 1 : 1;

        // Format as 3 digits with leading zeros (001, 002, 010, 120)
        $validated['shift_id'] = str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $validated['status'] = 'open';
        $validated['opened_by'] = $request->user()->id;

        // Use provided nozzle readings if any, otherwise snapshot from DB
        if ($request->has('nozzle_readings') && is_array($request->nozzle_readings)) {
            $providedReadings = $request->nozzle_readings;
            $nozzles = \App\Models\Nozzle::with('tank.product')->get();
            $nozzleReadings = [];
            foreach ($nozzles as $nozzle) {
                $provided = collect($providedReadings)->firstWhere('nozzle_id', $nozzle->id);
                $nozzleReadings[] = [
                    'nozzle_id' => $nozzle->id,
                    'nozzle_name' => $nozzle->name,
                    'tank_name' => $nozzle->tank->name ?? 'N/A',
                    'product_name' => $nozzle->tank->product->name ?? 'N/A',
                    'opening_reading' => $provided['opening_reading'] ?? $nozzle->reading,
                    'closing_reading' => 0,
                    'qty_sold' => 0,
                    'rtt' => 0,
                    'revenue' => 0,
                    'retail_sales' => 0,
                    'retail_revenue' => 0,
                    'total_revenue' => 0,
                    'shortage' => 0,
                    'overage' => 0,
                ];
            }
            $validated['nozzle_readings'] = $nozzleReadings;
        }

        // Calculate expiry date
        $duration = $validated['duration'];
        $expiryDate = now();

        if (str_contains(strtolower($duration), 'week')) {
            $weeks = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT) ?: 1;
            $expiryDate = now()->addWeeks($weeks);
        } elseif (str_contains(strtolower($duration), 'day')) {
            $days = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT) ?: 1;
            $expiryDate = now()->addDays($days);
        } else {
            // Assume hours if just number or contains hours
            $hours = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT) ?: 24;
            $expiryDate = now()->addHours($hours);
        }
        
        $validated['expiry_date'] = $expiryDate;

        // If no nozzle readings provided in request, snapshot from DB
        if (!$request->has('nozzle_readings') || !is_array($request->nozzle_readings)) {
            $nozzles = \App\Models\Nozzle::with('tank.product')->get();
            $nozzleReadings = [];
            foreach ($nozzles as $nozzle) {
                $nozzleReadings[] = [
                    'nozzle_id' => $nozzle->id,
                    'nozzle_name' => $nozzle->name,
                    'tank_name' => $nozzle->tank->name ?? 'N/A',
                    'product_name' => $nozzle->tank->product->name ?? 'N/A',
                    'opening_reading' => $nozzle->reading,
                    'closing_reading' => 0,
                    'qty_sold' => 0,
                    'rtt' => 0,
                    'revenue' => 0,
                    'retail_sales' => 0,
                    'retail_revenue' => 0,
                    'total_revenue' => 0,
                    'shortage' => 0,
                    'overage' => 0,
                ];
            }
            $validated['nozzle_readings'] = $nozzleReadings;
        }

        $shift = Shift::create($validated);

        // Auto-initialize Daily Sales and Shift Sales Summary for all products
        $products = \App\Models\Product::all();
        foreach ($products as $product) {
            // Keep DailySale for other purposes if needed, BUT user specifically asked for Shift Sales Summary table.
            // We will initialize both to be safe, or just ShiftSalesSummary if DailySale is redundant.
            // Given previous instruction "created in sale too", let's keep DailySale init as well.
            
            DailySale::create([
                'shift_id' => $shift->id,
                'product_id' => $product->id,
                'quantity' => 0,
                'price' => $product->retail_price,
                'amount' => 0,
                'date' => $shift->date,
            ]);

            \App\Models\ShiftSalesSummary::create([
                'shift_id' => $shift->id,
                'product_id' => $product->id,
                'cost_price' => $product->cost_price ?? 0,
                'pump_price' => $product->retail_price,
                'shift_vol' => 0,
                'shift_amount' => 0,
                'bulk_sales' => 0,
                'retail_sales' => 0,
                'total_revenue' => 0,
                'date' => $shift->date,
            ]);
        }

        return response()->json($shift, 201);
    }


    /**
     * @OA\Put(
     *   path="/api/filling/shifts/{id}",
     *   summary="Update shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="date", type="string", format="date"),
     *       @OA\Property(property="cash_sales", type="number"),
     *       @OA\Property(property="credit_sales", type="number"),
     *       @OA\Property(property="status", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Shift updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'cash_sales' => 'nullable|numeric|min:0',
            'credit_sales' => 'nullable|numeric|min:0',
            'status' => 'sometimes|required|string|in:open,closed,approved,expired,cancelled',
        ]);

        // Calculate sales revenue
        if (isset($validated['cash_sales']) || isset($validated['credit_sales'])) {
            $cashSales = $validated['cash_sales'] ?? $shift->cash_sales;
            $creditSales = $validated['credit_sales'] ?? $shift->credit_sales;
            $validated['sales_revenue'] = $cashSales + $creditSales;
        }

        // Handle status changes
        if (isset($validated['status'])) {
            if ($validated['status'] === 'closed' && $shift->status === 'open') {
                $validated['closed_at'] = now();
                $validated['closed_by'] = $request->user()->id;
            }
            if ($validated['status'] === 'approved' && $shift->status !== 'approved') {
                $validated['approved_at'] = now();
                $validated['approved_by'] = $request->user()->id;
            }
        }

        $shift->update($validated);
        return response()->json($shift);
    }

    /**
     * Close a shift
     */
    public function close(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        if ($shift->status !== 'open') {
            return response()->json(['message' => 'Shift is not open'], 400);
        }

        $this->calculateShiftSales($shift);

        // Validate and deduct tank content
        $tankSales = $this->getTankSalesFromShift($shift);
        foreach ($tankSales as $tankId => $qtySold) {
            $tank = \App\Models\Tank::find($tankId);
            if (!$tank) continue;

            if ((float) $tank->content < $qtySold) {
                return response()->json([
                    'message' => "Cannot close shift. Tank {$tank->name} has only {$tank->content} litres but {$qtySold} litres were sold."
                ], 422);
            }
        }

        foreach ($tankSales as $tankId => $qtySold) {
            $tank = \App\Models\Tank::find($tankId);
            if ($tank) {
                $tank->content = max(0, (float) $tank->content - $qtySold);
                $tank->save();
            }
        }

        $shift->status = 'closed';
        $shift->closed_at = now();
        $shift->closed_by = $request->user()->id;
        $shift->save();

        return response()->json($shift->fresh(['closedByUser', 'approvedByUser']));
    }

    /**
     * Approve a shift
     */
    public function approve(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        if ($shift->status === 'approved') {
            return response()->json(['message' => 'Shift is already approved'], 400);
        }

        $this->calculateShiftSales($shift);

        $shift->status = 'approved';
        $shift->approved_at = now();
        $shift->approved_by = $request->user()->id;
        $shift->save();

        return response()->json($shift->fresh(['closedByUser', 'approvedByUser']));
    }

    /**
     * Save shift values (nozzle readings and credit sales)
     */
    public function saveValues(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        $validated = $request->validate([
            'nozzle_readings' => 'nullable|array',
            'nozzle_readings.*.nozzle_id' => 'required|integer|exists:nozzles,id',
            'nozzle_readings.*.closing_reading' => 'required|numeric|min:0',
            'nozzle_readings.*.rtt' => 'nullable|numeric|min:0',
            'nozzle_readings.*.cash_over' => 'nullable|numeric|min:0',
            'nozzle_readings.*.cash_shortage' => 'nullable|numeric|min:0',
            'nozzle_readings.*.tank_id' => 'nullable|integer|exists:tanks,id',
            'credit_sales' => 'nullable|array',
            'credit_sales.*.customer_id' => 'required|integer|exists:customers,id',
            'credit_sales.*.product_id' => 'required|integer|exists:products,id',
            'credit_sales.*.quantity' => 'required|numeric|min:0',
            'credit_sales.*.discount' => 'nullable|numeric|min:0',
            'credit_sales.*.narration' => 'nullable|string',
            'credit_sales.*.ledger_notes' => 'nullable|string',
            'credit_sales.*.driver_name' => 'nullable|string',
            'credit_sales.*.truck_no' => 'nullable|string',
            'nozzle_reading_name' => 'nullable|string',
            'additional_readings' => 'nullable|array',
            'additional_readings.*.name' => 'nullable|string',
            'additional_readings.*.readings' => 'required|array',
            'additional_readings.*.readings.*.nozzle_id' => 'required|integer|exists:nozzles,id',
            'additional_readings.*.readings.*.closing_reading' => 'required|numeric|min:0',
            'additional_readings.*.readings.*.rtt' => 'nullable|numeric|min:0',
            'additional_readings.*.readings.*.cash_over' => 'nullable|numeric|min:0',
            'additional_readings.*.readings.*.cash_shortage' => 'nullable|numeric|min:0',
            'additional_readings.*.readings.*.tank_id' => 'nullable|integer|exists:tanks,id',
            'cashbacks' => 'nullable|array',
            'cashbacks.*.receipt_number' => 'nullable|string',
            'cashbacks.*.driver_name' => 'nullable|string',
            'cashbacks.*.driver_phone' => 'nullable|string',
            'cashbacks.*.amount_given' => 'nullable|numeric|min:0',
            'expenses' => 'nullable|array',
            'expenses.*.description' => 'required|string',
            'expenses.*.amount' => 'required|numeric|min:0',
        ]);

        // Store nozzle readings in shift's metadata or separate table
        // For now, we'll store in a JSON column
        if (isset($validated['nozzle_readings'])) {
            // Preserve existing structure and merge closing readings
            $updatedNozzleReadings = [];
            foreach ($shift->nozzle_readings as $reading) {
                foreach ($validated['nozzle_readings'] as $newReading) {
                    if ($reading['nozzle_id'] === $newReading['nozzle_id']) {
                        $reading['closing_reading'] = $newReading['closing_reading'] ?? 0;
                        $reading['rtt'] = $newReading['rtt'] ?? 0;
                        $reading['cash_over'] = $newReading['cash_over'] ?? 0;
                        $reading['cash_shortage'] = $newReading['cash_shortage'] ?? 0;
                        $reading['tank_id'] = $newReading['tank_id'] ?? ($reading['tank_id'] ?? null);
                        $opening = $reading['opening_reading'] ?? 0;
                        $closing = $reading['closing_reading'];
                        $rtt = $reading['rtt'];
                        $reading['qty_sold'] = max(0, $closing - $opening - $rtt);
                        break;
                    }
                }
                $updatedNozzleReadings[] = $reading;
            }
            $shift->nozzle_readings = $updatedNozzleReadings;
        }

        // Store the main reading name
        if ($request->has('nozzle_reading_name')) {
            $shift->nozzle_reading_name = $request->nozzle_reading_name;
        }

        // Store additional reading snapshots
        if ($request->has('additional_readings')) {
            $shift->additional_readings = $request->additional_readings;
        }

        // Store credit sales data
        if (isset($validated['credit_sales'])) {
            $shift->credit_sales_data = $validated['credit_sales'];
        }

        // Store cashbacks data
        if (isset($validated['cashbacks'])) {
            $shift->cashbacks_data = $validated['cashbacks'];
        }

        // Store expenses data
        if (isset($validated['expenses'])) {
            $shift->expenses_data = $validated['expenses'];
        }

        $shift->save();

        return response()->json([
            'message' => 'Shift values saved successfully',
            'shift' => $shift
        ]);
    }

    /**
     * Delete saved shift values
     */
    public function deleteValues(Request $request, int $id)
    {
        $shift = Shift::findOrFail($id);
        
        // Clear nozzle readings and credit sales data
        $shift->nozzle_readings = null;
        $shift->credit_sales_data = null;
        $shift->cashbacks_data = null;
        $shift->expenses_data = null;
        $shift->save();

        return response()->json([
            'message' => 'Shift values deleted successfully'
        ]);
    }

    /**
     * Re-open a closed shift (Return shift)
     */
    public function reopen(int $id)
    {
        $shift = Shift::findOrFail($id);
        
        // Only allow re-opening closed shifts
        if ($shift->status !== 'closed') {
            return response()->json([
                'message' => 'Only closed shifts can be returned/re-opened.'
            ], 400);
        }

        // Restore tank content from this shift's sales
        $tankSales = $this->getTankSalesFromShift($shift);
        foreach ($tankSales as $tankId => $qtySold) {
            $tank = \App\Models\Tank::find($tankId);
            if ($tank) {
                $tank->content = min((float) $tank->capacity, (float) $tank->content + $qtySold);
                $tank->save();
            }
        }

        $shift->status = 'open';
        $shift->closed_at = null;
        $shift->closed_by = null;
        $shift->save();

        return response()->json([
            'message' => 'Shift returned/re-opened successfully',
            'shift' => $shift
        ]);
    }

    /**
     * Cancel a shift (Soft delete/status update)
     */
    public function cancel(int $id)
    {
        $shift = Shift::findOrFail($id);
        
        // Prevent cancelling if already closed or approved
        if (in_array($shift->status, ['closed', 'approved'])) {
            return response()->json([
                'message' => 'Cannot cancel a closed or approved shift.'
            ], 400);
        }

        $shift->status = 'cancelled';
        $shift->save();

        return response()->json([
            'message' => 'Shift cancelled successfully',
            'shift' => $shift
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/shifts/{id}",
     *   summary="Delete shift",
     *   tags={"Filling Station - Shifts"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Shift deleted")
     * )
     */
    public function destroy(int $id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully']);
    }

    /**
     * Recalculate sales values for all closed/approved shifts
     */
    public function recalculateSales()
    {
        $shifts = Shift::whereIn('status', ['closed', 'approved'])->get();
        $count = 0;
        foreach ($shifts as $shift) {
            $this->calculateShiftSales($shift);
            $shift->save();
            $count++;
        }

        return response()->json([
            'message' => "Recalculated sales for {$count} shifts",
            'count' => $count,
        ]);
    }

    /**
     * Calculate total quantity sold per tank from shift nozzle readings
     */
    private function getTankSalesFromShift(Shift $shift): array
    {
        $tankQty = [];
        $prevClosingPerNozzle = [];

        // Main nozzle readings
        foreach ($shift->nozzle_readings ?? [] as $reading) {
            $opening = (float) ($reading['opening_reading'] ?? 0);
            $closing = (float) ($reading['closing_reading'] ?? 0);
            $rtt = (float) ($reading['rtt'] ?? 0);
            $qtySold = max(0, $closing - $opening - $rtt);

            $tankId = $reading['tank_id'] ?? null;
            if (!$tankId) {
                $nozzle = \App\Models\Nozzle::find($reading['nozzle_id'] ?? null);
                $tankId = $nozzle?->tank_id;
            }

            if ($tankId) {
                $tankQty[$tankId] = ($tankQty[$tankId] ?? 0) + $qtySold;
            }

            $prevClosingPerNozzle[$reading['nozzle_id']] = $closing;
        }

        // Additional snapshot readings
        foreach ($shift->additional_readings ?? [] as $snapshot) {
            foreach ($snapshot['readings'] ?? [] as $r) {
                $closing = (float) ($r['closing_reading'] ?? 0);
                $rtt = (float) ($r['rtt'] ?? 0);
                $opening = $prevClosingPerNozzle[$r['nozzle_id']] ?? 0;
                $qtySold = max(0, $closing - $opening - $rtt);

                $tankId = $r['tank_id'] ?? null;
                if (!$tankId) {
                    $nozzle = \App\Models\Nozzle::find($r['nozzle_id'] ?? null);
                    $tankId = $nozzle?->tank_id;
                }

                if ($tankId) {
                    $tankQty[$tankId] = ($tankQty[$tankId] ?? 0) + $qtySold;
                }

                $prevClosingPerNozzle[$r['nozzle_id']] = $closing;
            }
        }

        return $tankQty;
    }

    /**
     * Calculate cash_sales, credit_sales, and sales_revenue from shift data
     */
    private function calculateShiftSales(Shift $shift): void
    {
        $totalRevenue = 0;
        $creditSalesTotal = 0;

        // Get product prices map for quick lookup
        $products = \App\Models\Product::all()->keyBy('id');

        // Calculate total revenue from nozzle readings (qty_sold * retail_price)
        $nozzleReadings = $shift->nozzle_readings;
        if (is_array($nozzleReadings)) {
            foreach ($nozzleReadings as $reading) {
                $opening = (float) ($reading['opening_reading'] ?? 0);
                $closing = (float) ($reading['closing_reading'] ?? 0);
                $rtt = (float) ($reading['rtt'] ?? 0);
                $qtySold = max(0, $closing - $opening - $rtt);

                $nozzleId = $reading['nozzle_id'] ?? null;
                $price = 0;
                if ($nozzleId) {
                    $nozzle = \App\Models\Nozzle::with('tank.product')->find($nozzleId);
                    $price = $nozzle && $nozzle->tank && $nozzle->tank->product
                        ? (float) ($nozzle->tank->product->retail_price ?? 0)
                        : 0;
                }

                $totalRevenue += $qtySold * $price;
            }
        }

        // Calculate credit sales from credit_sales_data
        $creditSalesData = $shift->credit_sales_data;
        if (is_array($creditSalesData)) {
            foreach ($creditSalesData as $cs) {
                $qty = (float) ($cs['quantity'] ?? 0);
                $discount = (float) ($cs['discount'] ?? 0);
                $productId = $cs['product_id'] ?? null;
                $product = $productId ? ($products->get($productId) ?? \App\Models\Product::find($productId)) : null;
                $price = $product ? (float) ($product->retail_price ?? 0) : 0;
                $creditSalesTotal += max(0, ($qty * $price) - $discount);
            }
        }

        $cashSales = max(0, $totalRevenue - $creditSalesTotal);

        $shift->cash_sales = $cashSales;
        $shift->credit_sales = $creditSalesTotal;
        $shift->sales_revenue = $totalRevenue;
    }
}



