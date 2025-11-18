<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    /**
     * List departments with optional search by name and pagination.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = (int) ($request->get('per_page', 15));

        $query = Department::query()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name');

        return $query->paginate(max(1, $perPage));
    }

    /**
     * Get a department by ID.
     */
    public function show(int $id)
    {
        $department = Department::findOrFail($id);
        return response()->json($department);
    }

    /**
     * Create a new department.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;

        $department = Department::create($validated);
        return response()->json($department, 201);
    }

    /**
     * Export departments to CSV (Excel-readable).
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->get('search');

        $rows = Department::query()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'created_at']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="departments.csv"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Description', 'Created At']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->name,
                    $r->description,
                    optional($r->created_at)->toDateTimeString(),
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'departments.csv', $headers);
    }
}
