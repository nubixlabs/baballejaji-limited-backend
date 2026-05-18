<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\BulkSale;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\RetailSale;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/filling/customers",
     *   summary="Get all customers",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of customers")
     * )
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Filter by customer type
        if ($request->has('type')) {
            $query->where('customer_type', $request->type);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('company')->orderBy('contact_person')->get();
        return response()->json($customers);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/customers/{id}",
     *   summary="Get customer by ID",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Customer details")
     * )
     */
    public function show(int $id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
    }

    /**
     * @OA\Post(
     *   path="/api/filling/customers",
     *   summary="Create new customer",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"contact_person","phone_number"},
     *       @OA\Property(property="company", type="string"),
     *       @OA\Property(property="contact_person", type="string"),
     *       @OA\Property(property="phone_number", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="address", type="string"),
     *       @OA\Property(property="city", type="string"),
     *       @OA\Property(property="state", type="string"),
     *       @OA\Property(property="country", type="string"),
     *       @OA\Property(property="credit_limit", type="number"),
     *       @OA\Property(property="customer_type", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Customer created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_balance' => 'nullable|numeric|min:0',
            'customer_type' => 'nullable|string|in:retail,bulk',
        ]);

        $customer = Customer::create($validated);
        return response()->json($customer, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/filling/customers/{id}",
     *   summary="Update customer",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="company", type="string"),
     *       @OA\Property(property="contact_person", type="string"),
     *       @OA\Property(property="phone_number", type="string"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="address", type="string"),
     *       @OA\Property(property="city", type="string"),
     *       @OA\Property(property="state", type="string"),
     *       @OA\Property(property="country", type="string"),
     *       @OA\Property(property="credit_limit", type="number"),
     *       @OA\Property(property="customer_type", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Customer updated")
     * )
     */
    public function update(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'company' => 'nullable|string|max:255',
            'contact_person' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_balance' => 'nullable|numeric|min:0',
            'customer_type' => 'nullable|string|in:retail,bulk',
            'opening_balance_debit' => 'nullable|numeric|min:0',
            'opening_balance_credit' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);
        return response()->json($customer);
    }

    /**
     * @OA\Delete(
     *   path="/api/filling/customers/{id}",
     *   summary="Delete customer",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Customer deleted")
     * )
     */
    public function destroy(int $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    /**
     * @OA\Get(
     *   path="/api/filling/customers/credit-limit/report",
     *   summary="Get credit limit report",
     *   tags={"Filling Station - Customers"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Credit limit report")
     * )
     */
    public function creditLimitReport()
    {
        $customers = Customer::all();

        $report = $customers->map(function ($customer) {
            // Calculate invoiced (debit) - total of all sales where payment is not completed
            $bulkSalesInvoiced = $customer->bulkSales()
                ->where('payment_status', '!=', 'paid')
                ->where('payment_status', '!=', null)
                ->sum('grand_total');
            
            $retailSalesInvoiced = $customer->retailSales()
                ->where('payment_method', 'credit')
                ->sum('grand_total');
            
            $invoiced = (float) ($bulkSalesInvoiced + $retailSalesInvoiced);

            // Calculate paid (credit) - total of all paid sales
            $bulkSalesPaid = $customer->bulkSales()
                ->where('payment_status', 'paid')
                ->sum('grand_total');
            
            $retailSalesPaid = $customer->retailSales()
                ->where('payment_method', '!=', 'credit')
                ->whereNotNull('payment_method')
                ->sum('grand_total');
            
            $paid = (float) ($bulkSalesPaid + $retailSalesPaid);

            // Credit limit balance = credit_limit - credit_balance (remaining available credit)
            $creditLimit = (float) ($customer->credit_limit ?? 0);
            $creditBalance = (float) ($customer->credit_balance ?? 0);
            $creditLimitBalance = max(0, $creditLimit - $creditBalance);

            // Location (city, state)
            $locationParts = array_filter([$customer->city, $customer->state]);
            $location = !empty($locationParts) ? implode(', ', $locationParts) : 'N/A';

            return [
                'id' => $customer->id,
                'company' => $customer->company ?? $customer->contact_person ?? 'N/A',
                'location' => $location,
                'credit_limit' => $creditLimit,
                'invoiced_debit' => $invoiced,
                'paid_credit' => $paid,
                'credit_limit_balance' => $creditLimitBalance,
                'ledger_balance' => $creditBalance,
            ];
        });

        return response()->json($report);
    }

    /**
     * Get customer ledger entries
     */
    public function ledger(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $dateFrom = $request->get('date_from', '2000-01-01');
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $entries = [];

        // Opening balance entry
        $openingDebit = (float) ($customer->opening_balance_debit ?? 0);
        $openingCredit = (float) ($customer->opening_balance_credit ?? 0);
        $openingBalance = $openingDebit - $openingCredit;

        $entries[] = [
            'id' => 0,
            'date' => $customer->created_at ? date('M d, Y', strtotime($customer->created_at)) : '-',
            'particulars' => 'Opening Balance',
            'product' => '--',
            'driver_name' => '--',
            'truck_no' => '--',
            'qty' => 0,
            'debit' => $openingDebit,
            'credit' => $openingCredit,
            'balance' => $openingBalance,
            'type' => 'opening',
        ];

        $runningBalance = $openingBalance;

        // Bulk sales (debits) - only from closed shifts
        $bulkSales = BulkSale::with(['items.product', 'distributions'])
            ->where('customer_id', $id)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->whereHas('shift', function ($q) {
                $q->where('status', 'approved');
            })
            ->orderBy('sale_date')
            ->get();

        foreach ($bulkSales as $sale) {
            $qty = (float) $sale->items->sum('quantity');
            $productNames = $sale->items->pluck('product.name')->unique()->filter()->implode(', ');
            $firstDist = $sale->distributions->first();
            $truckNo = $firstDist->waybill_no ?? '--';
            $driverName = $firstDist->destination ?? '--';
            $debit = (float) $sale->grand_total;
            $runningBalance += $debit;

            $entries[] = [
                'id' => $sale->id,
                'date' => date('M d, Y', strtotime($sale->sale_date)),
                'particulars' => 'Bulk Sale - ' . ($sale->invoice_number ?? ''),
                'product' => $productNames ?: '--',
                'driver_name' => $driverName,
                'truck_no' => $truckNo,
                'qty' => $qty,
                'debit' => $debit,
                'credit' => 0,
                'balance' => $runningBalance,
                'type' => 'bulk_sale',
            ];
        }

        // Shift credit sales (debits) - credit sales entered in shift view
        $shifts = Shift::where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereNotNull('credit_sales_data')
            ->orderBy('date')
            ->get();

        foreach ($shifts as $shift) {
            $creditSalesData = $shift->credit_sales_data;
            if (!is_array($creditSalesData)) continue;

            foreach ($creditSalesData as $cs) {
                if (!isset($cs['customer_id']) || (int) $cs['customer_id'] !== $id) continue;

                $qty = (float) ($cs['quantity'] ?? 0);
                $amount = $qty * $this->getProductPrice($cs['product_id'] ?? null);
                $discount = (float) ($cs['discount'] ?? 0);
                $debit = max(0, $amount - $discount);
                $runningBalance += $debit;

                $productName = '--';
                if (!empty($cs['product_id'])) {
                    $product = Product::find($cs['product_id']);
                    $productName = $product ? $product->name : '--';
                }

                $entries[] = [
                    'id' => 'shift-cs-' . $shift->id . '-' . $cs['product_id'] . '-' . ($cs['customer_id'] ?? ''),
                    'date' => date('M d, Y', strtotime($shift->date)),
                    'particulars' => 'Credit Sale - Shift #' . $shift->id,
                    'product' => $productName,
                    'driver_name' => $cs['driver_name'] ?? '--',
                    'truck_no' => $cs['truck_no'] ?? '--',
                    'qty' => $qty,
                    'debit' => $debit,
                    'credit' => 0,
                    'balance' => $runningBalance,
                    'type' => 'shift_credit_sale',
                ];
            }
        }

        // Retail sales (debits) where payment method is credit - only from closed shifts
        $retailSales = RetailSale::with(['items.product'])
            ->where('customer_id', $id)
            ->where('payment_method', 'credit')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->whereHas('shift', function ($q) {
                $q->where('status', 'approved');
            })
            ->orderBy('sale_date')
            ->get();

        foreach ($retailSales as $sale) {
            $qty = (float) $sale->items->sum('quantity');
            $productNames = $sale->items->pluck('product.name')->unique()->filter()->implode(', ');
            $debit = (float) $sale->grand_total;
            $runningBalance += $debit;

            $entries[] = [
                'id' => $sale->id,
                'date' => date('M d, Y', strtotime($sale->sale_date)),
                'particulars' => 'Retail Sale - ' . ($sale->invoice_number ?? ''),
                'product' => $productNames ?: '--',
                'driver_name' => '--',
                'truck_no' => '--',
                'qty' => $qty,
                'debit' => $debit,
                'credit' => 0,
                'balance' => $runningBalance,
                'type' => 'retail_sale',
            ];
        }

        // Customer payments (credits) - only those linked to closed shift bulk sales
        $payments = CustomerPayment::where('customer_id', $id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('bulk_sale_id')
                  ->orWhereHas('bulkSale.shift', function ($sq) {
                      $sq->where('status', 'closed');
                  });
            })
            ->orderBy('payment_date')
            ->get();

        foreach ($payments as $payment) {
            $credit = (float) $payment->amount;
            $runningBalance -= $credit;

            $entries[] = [
                'id' => $payment->id,
                'date' => date('M d, Y', strtotime($payment->payment_date)),
                'particulars' => 'Payment - ' . ($payment->payment_method ?? ''),
                'product' => '--',
                'driver_name' => '--',
                'truck_no' => '--',
                'qty' => 0,
                'debit' => 0,
                'credit' => $credit,
                'balance' => $runningBalance,
                'type' => 'payment',
            ];
        }

        // Sort by date then by type (opening first, then by date)
        usort($entries, function ($a, $b) {
            if ($a['type'] === 'opening') return -1;
            if ($b['type'] === 'opening') return 1;
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Recalculate running balance after sort
        $balance = $openingBalance;
        foreach ($entries as &$entry) {
            if ($entry['type'] === 'opening') {
                $entry['balance'] = $balance;
            } else {
                $balance += $entry['debit'] - $entry['credit'];
                $entry['balance'] = $balance;
            }
        }

        return response()->json([
            'customer' => $customer,
            'entries' => $entries,
        ]);
    }

    /**
     * Get product price by product ID
     */
    private function getProductPrice($productId)
    {
        if (!$productId) return 0;
        $product = Product::find($productId);
        return $product ? (float) ($product->retail_price ?? 0) : 0;
    }
}

