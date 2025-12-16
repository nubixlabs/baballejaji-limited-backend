<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * List staff with filters and pagination.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->get('per_page', 15));
        $search = $request->get('search');
        $departmentId = $request->get('department_id');
        $levelId = $request->get('level_id');
        $from = $request->get('employed_from');
        $to = $request->get('employed_to');

        $query = Staff::with(['department', 'level', 'creator', 'lastModifier'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('surname', 'like', "%{$search}%")
                       ->orWhere('firstname', 'like', "%{$search}%")
                       ->orWhere('othernames', 'like', "%{$search}%")
                       ->orWhere('email_address', 'like', "%{$search}%")
                       ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($levelId, fn($q) => $q->where('level_id', $levelId))
            ->when($from, fn($q) => $q->whereDate('date_of_employment', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date_of_employment', '<=', $to))
            ->orderByDesc('created_at');

        return $query->paginate(max(1, $perPage));
    }

    /**
     * Store a new staff.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'othernames' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:50',
            'email_address' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'work_experience' => 'nullable|string',
            'previous_employer' => 'nullable|string|max:255',
            'resume' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'currently_employed' => 'nullable|in:Yes,No',
            'date_of_employment' => 'nullable|date',
            'referee_1' => 'nullable|string|max:255',
            'referee_2' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'level_id' => 'nullable|exists:levels,id',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:50',
            'photo' => 'nullable|string|max:2048',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;
        $validated['currently_employed'] = $validated['currently_employed'] ?? 'Yes';

        $staff = Staff::create($validated);
        return response()->json($staff->load(['department','level','creator','lastModifier']), 201);
    }

    /**
     * Update a staff record.
     */
    public function update(Request $request, int $id)
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'surname' => 'sometimes|required|string|max:255',
            'firstname' => 'sometimes|required|string|max:255',
            'othernames' => 'nullable|string|max:255',
            'gender' => 'nullable|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:50',
            'email_address' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'work_experience' => 'nullable|string',
            'previous_employer' => 'nullable|string|max:255',
            'resume' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'currently_employed' => 'nullable|in:Yes,No',
            'date_of_employment' => 'nullable|date',
            'referee_1' => 'nullable|string|max:255',
            'referee_2' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'level_id' => 'nullable|exists:levels,id',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:50',
            'photo' => 'nullable|string|max:2048',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;

        $staff->update($validated);
        return response()->json($staff->load(['department','level','creator','lastModifier']));
    }

    /**
     * Delete a staff record.
     */
    public function destroy(int $id)
    {
        $staff = Staff::findOrFail($id);
        $staff->delete();
        return response()->json(['message' => 'Staff deleted successfully']);
    }

    /**
     * Show a single staff.
     */
    public function show(int $id)
    {
        $staff = Staff::with(['department','level'])->findOrFail($id);
        return response()->json($staff);
    }
}
