<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

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
}

