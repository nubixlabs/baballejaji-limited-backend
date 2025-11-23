<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\Staff;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function summary(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);
        $find = $request->input('find');
        $level = $request->input('level');
        $department = $request->input('department');
        $from = $request->input('period_from');
        $to = $request->input('period_to');

        $staffQuery = Staff::query();
        if ($find) {
            $staffQuery->where(function($q) use ($find) {
                $q->where('id', $find)
                  ->orWhere('surname', 'like', "%$find%")
                  ->orWhere('firstname', 'like', "%$find%");
            });
        }
        if ($level) $staffQuery->where('level_id', $level);
        if ($department) $staffQuery->where('department_id', $department);

        $staffIds = $staffQuery->pluck('id');

        $rec = AttendanceRecord::query()
            ->when($staffIds->isNotEmpty(), fn($q) => $q->whereIn('employee_id', $staffIds))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->selectRaw('employee_id, COUNT(DISTINCT date) as days_worked, SUM(total_hours) as hours_worked')
            ->groupBy('employee_id');

        $total = $rec->count();
        $rows = $rec->skip(($page-1)*$perPage)->take($perPage)->get();

        $staffMap = Staff::with(['department:id,name','level:id,department_id'])
            ->whereIn('id', $rows->pluck('employee_id')->all())
            ->get(['id','firstname','surname','gender','department_id','level_id'])
            ->keyBy('id');

       \Log::info('Staff map:', $staffMap->toArray());   

        $data = $rows->map(function($r) use ($staffMap) {
            $s = $staffMap[$r->employee_id] ?? null;
            $departmentId = $s->department_id ?? ($s->level->department_id ?? null);
            return [
                'id' => $r->employee_id,
                'emp_id' => (string)$r->employee_id,
                'employee_id' => (int)$r->employee_id,
                'fullname' => $s ? trim(($s->firstname ?? '').' '.($s->surname ?? '')) : '',
                'gender' => $s->gender ?? null,
                'department_id' => $departmentId,
                'department_name' => optional($s?->department)->name,
                'days_worked' => (int)($r->days_worked ?? 0),
                'hours_worked' => (float)($r->hours_worked ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ]
        ]);
    }

    public function summaryExport(Request $request)
    {
        $request->merge(['page' => 1, 'per_page' => PHP_INT_MAX]);
        $summary = $this->summary($request)->getData(true);

        $callback = function() use ($summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Emp ID','Fullname','Gender','Department','Days Worked','Hours Worked']);
            foreach ($summary['data'] as $row) {
                fputcsv($out, [
                    $row['emp_id'],
                    $row['fullname'],
                    $row['gender'],
                    $row['department_name'],
                    $row['days_worked'],
                    $row['hours_worked'],
                ]);
            }
            fclose($out);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_summary.csv"',
        ]);
    }

    public function summaryExportPdf(Request $request)
    {
        $request->merge(['page' => 1, 'per_page' => PHP_INT_MAX]);
        $summary = $this->summary($request)->getData(true);
        $rows = $summary['data'] ?? [];
        $html = view('exports.attendance_summary', compact('rows'))->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('attendance_summary.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    public function store(Request $request)
    {
        $val = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:staff,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'hours_from' => 'required|date_format:H:i',
            'hours_to' => 'required|date_format:H:i',
        ]);
        $val->validate();

        $dateFrom = Carbon::parse($request->input('date_from'));
        $dateTo = Carbon::parse($request->input('date_to'));
        $hoursFrom = Carbon::createFromFormat('H:i', $request->input('hours_from'));
        $hoursTo = Carbon::createFromFormat('H:i', $request->input('hours_to'));
        $diffHours = max(0, $hoursTo->diffInMinutes($hoursFrom) / 60);

        $created = 0;
        DB::transaction(function () use ($request, $dateFrom, $dateTo, $diffHours, &$created) {
            $creator = optional(auth()->user())->id;
            foreach ($request->input('employee_ids') as $empId) {
                $date = $dateFrom->copy();
                while ($date->lte($dateTo)) {
                    $exists = AttendanceRecord::where('employee_id', $empId)
                        ->whereDate('date', $date->toDateString())
                        ->exists();
                    if (!$exists) {
                        AttendanceRecord::create([
                            'employee_id' => $empId,
                            'date' => $date->toDateString(),
                            'hours_from' => $request->input('hours_from'),
                            'hours_to' => $request->input('hours_to'),
                            'total_hours' => $diffHours,
                            'created_by' => $creator,
                        ]);
                        $created++;
                    }
                    $date->addDay();
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Created', 'created_count' => $created], 201);
    }

    public function holidaysList()
    {
        return response()->json(Holiday::orderBy('date', 'asc')->get());
    }

    public function holidaysStore(Request $request)
    {
        $val = Validator::make($request->all(), [
            'name' => 'required|string',
            'date' => 'required|date',
        ]);
        $val->validate();

        $h = Holiday::create($request->only(['name','date']));
        return response()->json($h, 201);
    }

    public function holidaysDestroy($id)
    {
        $h = Holiday::findOrFail((int)$id);
        $h->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
