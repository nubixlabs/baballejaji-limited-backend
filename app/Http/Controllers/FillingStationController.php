<?php

namespace App\Http\Controllers;

use App\Models\FillingStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FillingStationController extends Controller
{
    public function index(Request $request)
    {
        $query = FillingStation::query()->with(['createdBy', 'updatedBy']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $fillingStations = $query->latest()->paginate(10);

        return response()->json([
            'data' => $fillingStations,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:filling_stations,code|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fillingStation = FillingStation::create([
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'phone' => $request->phone,
            'email' => $request->email,
            'license_number' => $request->license_number,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $fillingStation->load(['createdBy', 'updatedBy']);

        return response()->json([
            'message' => 'Filling station created successfully',
            'data' => $fillingStation,
        ], 201);
    }

    public function show($id)
    {
        $fillingStation = FillingStation::with(['createdBy', 'updatedBy'])->find($id);

        if (!$fillingStation) {
            return response()->json([
                'message' => 'Filling station not found',
            ], 404);
        }

        return response()->json([
            'data' => $fillingStation,
        ]);
    }

    public function update(Request $request, $id)
    {
        $fillingStation = FillingStation::find($id);

        if (!$fillingStation) {
            return response()->json([
                'message' => 'Filling station not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:filling_stations,code,' . $id . '|max:50',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fillingStation->update([
            'name' => $request->name ?? $fillingStation->name,
            'code' => $request->code ?? $fillingStation->code,
            'address' => $request->address ?? $fillingStation->address,
            'city' => $request->city ?? $fillingStation->city,
            'state' => $request->state ?? $fillingStation->state,
            'phone' => $request->phone ?? $fillingStation->phone,
            'email' => $request->email ?? $fillingStation->email,
            'license_number' => $request->license_number ?? $fillingStation->license_number,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $fillingStation->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $fillingStation->load(['createdBy', 'updatedBy']);

        return response()->json([
            'message' => 'Filling station updated successfully',
            'data' => $fillingStation,
        ]);
    }

    public function destroy($id)
    {
        $fillingStation = FillingStation::find($id);

        if (!$fillingStation) {
            return response()->json([
                'message' => 'Filling station not found',
            ], 404);
        }

        $fillingStation->delete();

        return response()->json([
            'message' => 'Filling station deleted successfully',
        ]);
    }
}
