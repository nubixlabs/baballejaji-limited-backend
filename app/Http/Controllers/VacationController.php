<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacation;
use App\Models\Staff;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VacationController extends Controller
{
    public function index(Request $request)
    {
        $q = Vacation::query()->with([
            'employee:id,firstname,surname,department_id,level_id,date_of_employment,gender,phone_number',
            'employee.department:id,name',
            'employee.level:id,department_id',
        ]);
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('employee', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('level')) $q->whereHas('employee', fn($qq) => $qq->where('level_id', $v));
        if ($v = $request->input('vacation_from')) $q->whereDate('start_date', '>=', $v);
        if ($v = $request->input('vacation_to')) $q->whereDate('end_date', '<=', $v);

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);
        $paginator = $q->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function($v) {
            $e = $v->employee;
            $departmentId = $e?->department_id ?? ($e?->level?->department_id ?? null);
            return [
                'id' => $v->id,
                'employee_id' => $v->employee_id,
                'fullname' => $e ? trim(($e->firstname ?? '').' '.($e->surname ?? '')) : '',
                'employed_on' => $e?->date_of_employment?->toDateString(),
                'gender' => $e->gender ?? null,
                'phone' => $e->phone_number ?? null,
                'department_id' => $departmentId,
                'department_name' => optional($e?->department)->name,
                'start_date' => optional($v->start_date)->toDateString(),
                'end_date' => optional($v->end_date)->toDateString(),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $val = Validator::make($request->all(), [
            'employee_id' => 'required|exists:staff,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'allowance' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);
        $val->validate();
        $vac = Vacation::create($request->only(['employee_id','start_date','end_date','allowance','description']));
        return response()->json($vac, 201);
    }

    public function update($id, Request $request)
    {
        $vac = Vacation::findOrFail((int)$id);
        $vac->fill($request->only(['employee_id','start_date','end_date','allowance','description']));
        $vac->save();
        return response()->json($vac);
    }

    public function destroy($id)
    {
        $vac = Vacation::findOrFail((int)$id);
        $vac->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    public function export(Request $request)
    {
        $q = Vacation::query()->with('employee');
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('employee', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('level')) $q->whereHas('employee', fn($qq) => $qq->where('level_id', $v));
        if ($v = $request->input('vacation_from')) $q->whereDate('start_date', '>=', $v);
        if ($v = $request->input('vacation_to')) $q->whereDate('end_date', '<=', $v);

        $rows = $q->orderBy('id')->get();

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Employee','Start Date','End Date','Allowance','Description']);
            foreach ($rows as $v) {
                $e = $v->employee;
                $name = $e ? trim(($e->firstname ?? '').' '.($e->surname ?? '')) : '';
                fputcsv($out, [
                    $v->id,
                    $name,
                    optional($v->start_date)->toDateString(),
                    optional($v->end_date)->toDateString(),
                    $v->allowance,
                    $v->description,
                ]);
            }
            fclose($out);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vacations.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $q = Vacation::query()->with(['employee','employee.department','employee.level']);
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('employee', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('level')) $q->whereHas('employee', fn($qq) => $qq->where('level_id', $v));
        if ($v = $request->input('vacation_from')) $q->whereDate('start_date', '>=', $v);
        if ($v = $request->input('vacation_to')) $q->whereDate('end_date', '<=', $v);

        $rows = $q->orderBy('id')->get();
        $html = view('exports.vacations', compact('rows'))->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('vacations.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
