<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payslip;
use App\Models\Staff;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function generate(Request $request)
    {
        $val = Validator::make($request->all(), [
            'slip_name' => 'required|string',
            'date_from' => 'required|date',
            'level_ids' => 'nullable|array',
            'level_ids.*' => 'integer|exists:levels,id',
        ]);
        $val->validate();

        $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
        // Infer date_to as end of month for simplicity; adjust as needed in future.
        $dateTo = $dateFrom->copy()->endOfMonth();
        $levelIds = $request->input('level_ids', []);

        $staffQuery = Staff::query()->with('level');
        if (!empty($levelIds)) {
            $staffQuery->whereIn('level_id', $levelIds);
        }
        $staff = $staffQuery->get();
        $generated = 0;
        $batchId = now()->timestamp;

        DB::transaction(function () use ($staff, $dateFrom, $dateTo, $request, &$generated) {
            foreach ($staff as $s) {
                $exists = Payslip::where('employee_id', $s->id)
                    ->whereDate('date_from', $dateFrom->toDateString())
                    ->whereDate('date_to', $dateTo->toDateString())
                    ->exists();
                if ($exists) continue;

                $daysWorked = AttendanceRecord::where('employee_id', $s->id)
                    ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->distinct()->count('date');

                // Calculate Pay
                $pay = 0;
                if ($s->level) {
                    $rate = (float)$s->level->basic_pay_rate;
                    $period = strtolower($s->level->basic_pay_period ?? 'monthly');
                    
                    if ($period === 'daily') {
                        $pay = $rate * $daysWorked;
                    } else {
                        // Default to monthly flat rate
                        $pay = $rate;
                    }
                }

                Payslip::create([
                    'employee_id' => $s->id,
                    'emp_id' => (string)$s->id,
                    'department_id' => $s->department_id,
                    'level_id' => $s->level_id,
                    'salary_period' => 'monthly', // Defaulting to monthly for now as per typical flow
                    'slip_name' => $request->input('slip_name'),
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'days_worked' => $daysWorked,
                    'total_pay' => $pay,
                ]);
                $generated++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payslips generated successfully',
            'data' => [
                'batch_id' => $batchId,
                'generated_count' => $generated,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $q = Payslip::query()->with(['employee:id,firstname,surname,department_id,level_id', 'department:id,name', 'level:id,name']);
        
        // Filter by salary period if provided (e.g. "monthly")
        if ($v = $request->input('salary')) $q->where('salary_period', $v);
        
        if ($v = $request->input('find')) {
            $q->where(function($qq) use ($v) {
                $qq->where('emp_id', 'like', "%$v%")
                   ->orWhereHas('employee', function($qe) use ($v) {
                        $qe->where('firstname', 'like', "%$v%")
                           ->orWhere('surname', 'like', "%$v%");
                   });
            });
        }
        if ($v = $request->input('level')) $q->where('level_id', $v);
        if ($v = $request->input('department')) $q->where('department_id', $v);

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 200); // Default to 200 as per requirement example
        $paginator = $q->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function($p) {
            $e = $p->employee;
            return [
                'id' => $p->id,
                'salary_period' => ucfirst($p->salary_period),
                'days_worked' => $p->days_worked,
                'net_pay' => (float)($p->total_pay ?? 0),
                'employee_id' => $p->employee_id,
                'employee_name' => $e ? trim(($e->firstname ?? '').' '.($e->surname ?? '')) : '',
                'employee' => $e ? [
                    'emp_id' => $p->emp_id,
                    'surname' => $e->surname,
                    'firstname' => $e->firstname,
                ] : null,
                'department_id' => $p->department_id,
                'department_name' => optional($p->department)->name,
                'level_id' => $p->level_id,
                'level_name' => optional($p->level)->name,
            ];
        })->values();

        return response()->json([
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function export(Request $request)
    {
        $q = Payslip::query()->with('employee');
        if ($v = $request->input('salary')) $q->where('slip_name', 'like', "%$v%");
        if ($v = $request->input('find')) {
            $q->where(function($qq) use ($v) {
                $qq->where('emp_id', 'like', "%$v%")
                   ->orWhereHas('employee', function($qe) use ($v) {
                        $qe->where('firstname', 'like', "%$v%")
                           ->orWhere('surname', 'like', "%$v%");
                   });
            });
        }
        if ($v = $request->input('level')) $q->where('level_id', $v);
        if ($v = $request->input('department')) $q->where('department_id', $v);

        $rows = $q->orderBy('id')->get();

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Slip ID','Emp ID','Employee Name','Department','Level','Slip Name','Period From','Period To','Net Pay','Status']);
            foreach ($rows as $p) {
                $e = $p->employee;
                $name = $e ? trim(($e->firstname ?? '').' '.($e->surname ?? '')) : '';
                fputcsv($out, [
                    $p->id,
                    $p->emp_id,
                    $name,
                    $p->department_id,
                    $p->level_id,
                    $p->slip_name,
                    optional($p->date_from)->toDateString(),
                    optional($p->date_to)->toDateString(),
                    $p->total_pay,
                    'unpaid',
                ]);
            }
            fclose($out);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payslips.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $q = Payslip::query()->with(['employee','department','level']);
        if ($v = $request->input('salary')) $q->where('slip_name', 'like', "%$v%");
        if ($v = $request->input('find')) {
            $q->where(function($qq) use ($v) {
                $qq->where('emp_id', 'like', "%$v%")
                   ->orWhereHas('employee', function($qe) use ($v) {
                        $qe->where('firstname', 'like', "%$v%")
                           ->orWhere('surname', 'like', "%$v%");
                   });
            });
        }
        if ($v = $request->input('level')) $q->where('level_id', $v);
        if ($v = $request->input('department')) $q->where('department_id', $v);

        $rows = $q->orderBy('id')->get();
        $html = view('exports.payslips', compact('rows'))->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('payslips.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
