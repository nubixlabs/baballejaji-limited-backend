<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\BulkSale;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DistributionController extends Controller
{
    /**
     * Get all distributions
     */
    public function index(Request $request)
    {
        $query = Distribution::with(['bulkSale.customer', 'bulkSale.items.product', 'tank', 'nozzle', 'creator', 'approver']);

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $distributions = $query->orderByDesc('created_at')->get();
        return response()->json($distributions);
    }

    /**
     * Get distribution by ID
     */
    public function show(int $id)
    {
        $distribution = Distribution::with(['bulkSale.customer', 'bulkSale.items.product', 'tank', 'nozzle', 'creator', 'approver'])->findOrFail($id);
        return response()->json($distribution);
    }

    /**
     * Store new distributions
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulk_sale_id' => 'required|exists:bulk_sales,id',
            'distributions' => 'required|array|min:1',
            'distributions.*.tank_id' => 'required|exists:tanks,id',
            'distributions.*.nozzle_id' => 'required|exists:nozzles,id',
            'distributions.*.quantity' => 'required|numeric|min:0',
            'distributions.*.destination' => 'nullable|string',
            'distributions.*.sale_date' => 'required|date',
            'distributions.*.waybill_no' => 'nullable|string',
            'distributions.*.narration' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $createdDistributions = [];
            foreach ($validated['distributions'] as $dist) {
                $distribution = Distribution::create([
                    'bulk_sale_id' => $validated['bulk_sale_id'],
                    'tank_id' => $dist['tank_id'],
                    'nozzle_id' => $dist['nozzle_id'],
                    'quantity' => $dist['quantity'],
                    'destination' => $dist['destination'] ?? null,
                    'sale_date' => $dist['sale_date'],
                    'waybill_no' => $dist['waybill_no'] ?? null,
                    'narration' => $dist['narration'] ?? null,
                    'status' => 'Pending',
                    'created_by' => Auth::id(),
                ]);
                
                // Deduct from tank? Usually happens on approval or dispensing. 
                // For now just create record.
                
                $createdDistributions[] = $distribution;
            }

            DB::commit();
            
            // Return the first created distribution or all?
            // The frontend might expect a single object or list.
            // For now return list.
            return response()->json($createdDistributions, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating distributions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve distribution
     */
    public function approve(int $id)
    {
        $distribution = Distribution::findOrFail($id);
        
        if ($distribution->status === 'Approved') {
            return response()->json(['message' => 'Distribution already approved'], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct quantity from tank
            $tank = Tank::find($distribution->tank_id);
            if ($tank) {
                if ($tank->content < $distribution->quantity) {
                    throw new \Exception("Insufficient tank balance");
                }
                $tank->content -= $distribution->quantity;
                $tank->save();
            }

            $distribution->update([
                'status' => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return response()->json($distribution->load(['approver']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error approving distribution: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Delete distribution
     */
    public function destroy(int $id)
    {
        $distribution = Distribution::findOrFail($id);
        
        if ($distribution->status === 'Approved') {
            return response()->json(['message' => 'Cannot delete approved distribution'], 400);
        }

        $distribution->delete();
        return response()->json(['message' => 'Distribution deleted successfully']);
    }
}
