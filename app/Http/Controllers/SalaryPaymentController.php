<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPayment;
use App\Models\Payslip;
use App\Models\Staff;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryPaymentController extends Controller
{
    public function store(Request $request)
    {
        // Option A: payslip_ids
        if ($request->has('payslip_ids')) {
            $val = Validator::make($request->all(), [
                'payslip_ids' => 'required|array|min:1',
                'payslip_ids.*' => 'integer|exists:payslips,id',
            ]);
            $val->validate();

            $created = 0;
            $batchId = now()->timestamp;
            DB::transaction(function () use ($request, &$created) {
                $slips = Payslip::whereIn('id', $request->input('payslip_ids'))->get();
                foreach ($slips as $p) {
                    $exists = SalaryPayment::where('payslip_id', $p->id)->exists();
                    if ($exists) continue;
                    SalaryPayment::create([
                        'payslip_id' => $p->id,
                        'employee_id' => $p->employee_id,
                        'emp_id' => $p->emp_id,
                        'total_pay' => $p->total_pay,
                        'cheque_account' => $request->input('cheque_account'),
                        'paid_at' => now(),
                    ]);
                    $created++;
                }
            });
            return response()->json([
                'success' => true,
                'message' => 'Payments recorded',
                'data' => [
                    'payment_batch_id' => $batchId,
                    'paid_count' => $created,
                ],
            ]);
        }

        // Option B: employee_ids and salary_period
        $val = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:staff,id',
            'salary_period' => 'required|string',
            'date_from' => 'required|date',
            'cheque_account' => 'nullable|string',
        ]);
        $val->validate();

        $created = 0;
        $batchId = now()->timestamp;
        DB::transaction(function () use ($request, &$created) {
            $staff = Staff::whereIn('id', $request->input('employee_ids'))->get(['id']);
            foreach ($staff as $s) {
                SalaryPayment::create([
                    'payslip_id' => null,
                    'employee_id' => $s->id,
                    'emp_id' => (string)$s->id,
                    'total_pay' => 0,
                    'cheque_account' => $request->input('cheque_account'),
                    'paid_at' => now(),
                ]);
                $created++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payments recorded',
            'data' => [
                'payment_batch_id' => $batchId,
                'paid_count' => $created,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $q = SalaryPayment::query()->with([
            'payslip:id,employee_id,emp_id,department_id,level_id,created_at',
            'payslip.employee:id,firstname,surname',
            'payslip.department:id,name',
            'employee:id,firstname,surname,department_id',
            'employee.department:id,name',
        ]);
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('payslip', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('date_from')) $q->whereDate('paid_at', '>=', $v);
        if ($v = $request->input('date_to')) $q->whereDate('paid_at', '<=', $v);

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);
        $paginator = $q->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function($sp) {
            $emp = $sp->payslip?->employee ?: $sp->employee;
            $employeeName = $emp ? trim(($emp->firstname ?? '').' '.($emp->surname ?? '')) : '';
            $deptId = $sp->payslip?->department_id ?: $sp->employee?->department_id;
            $deptName = $sp->payslip?->department?->name ?: $sp->employee?->department?->name;
            $reference = 'PAY-'.optional($sp->paid_at)->format('Y-m-d').'-'.str_pad((string)$sp->id, 3, '0', STR_PAD_LEFT);
            return [
                'id' => $sp->id,
                'payslip_id' => $sp->payslip_id,
                'employee_id' => $sp->employee_id,
                'employee_name' => $employeeName,
                'emp_id' => $sp->emp_id,
                'department_id' => $deptId,
                'department_name' => $deptName,
                'amount' => (float)$sp->total_pay,
                'method' => 'bank',
                'cheque_account' => $sp->cheque_account,
                'paid_on' => optional($sp->paid_at)->toDateString(),
                'reference' => $reference,
                'created_at' => optional($sp->created_at)->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $q = SalaryPayment::query()->with(['payslip.employee','payslip.department','employee.department']);
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('payslip', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('date_from')) $q->whereDate('paid_at', '>=', $v);
        if ($v = $request->input('date_to')) $q->whereDate('paid_at', '<=', $v);

        $rows = $q->orderBy('id')->get();

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Payment ID','Payslip ID','Employee','Emp ID','Department','Amount','Method','Cheque Account','Paid On','Reference']);
            foreach ($rows as $sp) {
                $emp = $sp->payslip?->employee ?: $sp->employee;
                $name = $emp ? trim(($emp->firstname ?? '').' '.($emp->surname ?? '')) : '';
                $deptName = $sp->payslip?->department?->name ?: $sp->employee?->department?->name;
                $reference = 'PAY-'.optional($sp->paid_at)->format('Y-m-d').'-'.str_pad((string)$sp->id, 3, '0', STR_PAD_LEFT);
                fputcsv($out, [
                    $sp->id,
                    $sp->payslip_id,
                    $name,
                    $sp->emp_id,
                    $deptName,
                    $sp->total_pay,
                    'bank',
                    $sp->cheque_account,
                    optional($sp->paid_at)->toDateString(),
                    $reference,
                ]);
            }
            fclose($out);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="salary_payments.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $q = SalaryPayment::query()->with(['payslip.employee','payslip.department','employee.department']);
        if ($v = $request->input('employee_id')) $q->where('employee_id', $v);
        if ($v = $request->input('department')) $q->whereHas('payslip', fn($qq) => $qq->where('department_id', $v));
        if ($v = $request->input('date_from')) $q->whereDate('paid_at', '>=', $v);
        if ($v = $request->input('date_to')) $q->whereDate('paid_at', '<=', $v);

        $rows = $q->orderBy('id')->get();
        $html = view('exports.salary_payments', compact('rows'))->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('salary_payments.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
