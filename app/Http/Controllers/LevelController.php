<?php

namespace App\Http\Controllers;

use App\Models\Level;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LevelController extends Controller
{
    /**
     * List levels with optional search by name and pagination.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = (int) ($request->get('per_page', 15));

        $query = Level::with(['department'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name');

        return $query->paginate(max(1, $perPage));
    }

    /**
     * Create a new level.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:levels,name',
            'department_id' => 'nullable|exists:departments,id',
            'basic_pay_rate' => 'required|numeric|min:0',
            'basic_pay_period' => 'nullable|string|max:50',
            'overtime_rate' => 'nullable|numeric|min:0',
            'overtime_period' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $validated['last_modified_by'] = $request->user()->id ?? null;

        $level = Level::create($validated);
        return response()->json($level->load(['department']), 201);
    }

    /**
     * Update a level by id.
     */
    public function update(Request $request, int $id)
    {
        $level = Level::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:levels,name,' . $id,
            'department_id' => 'nullable|exists:departments,id',
            'basic_pay_rate' => 'sometimes|required|numeric|min:0',
            'basic_pay_period' => 'nullable|string|max:50',
            'overtime_rate' => 'nullable|numeric|min:0',
            'overtime_period' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $validated['last_modified_by'] = $request->user()->id ?? null;

        $level->update($validated);
        return response()->json($level->load(['department']));
    }

    /**
     * Delete a level by id.
     */
    public function destroy(int $id)
    {
        $level = Level::findOrFail($id);
        $level->delete();
        return response()->json(['message' => 'Level deleted successfully']);
    }

    /**
     * Export levels to CSV (Excel-readable).
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->get('search');

        $rows = Level::query()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id','name','basic_pay_rate','basic_pay_period','overtime_rate','overtime_period','description','created_at']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="levels.csv"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID','Name','Basic Pay Rate','Basic Pay Period','Overtime Rate','Overtime Period','Description','Created At']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->name,
                    $r->basic_pay_rate,
                    $r->basic_pay_period,
                    $r->overtime_rate,
                    $r->overtime_period,
                    $r->description,
                    optional($r->created_at)->toDateTimeString(),
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'levels.csv', $headers);
    }
}
