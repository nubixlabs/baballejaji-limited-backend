<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\Staff;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::query()->with(['employee:id,firstname,surname,department_id,level_id']);

        if ($v = $request->input('employee_id')) $query->where('employee_id', $v);
        if ($v = $request->input('department')) $query->where('department_id', $v);
        if ($v = $request->input('level')) $query->where('level_id', $v);
        if ($v = $request->input('status')) $query->where('status', $v);
        if ($v = $request->input('start_from')) $query->whereDate('start_date', '>=', $v);
        if ($v = $request->input('start_to')) $query->whereDate('start_date', '<=', $v);

        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function ($loan) {
            $emp = $loan->employee;
            $employee_name = $emp ? trim(($emp->firstname ?? '').' '.($emp->surname ?? '')) : '';
            return [
                'id' => $loan->id,
                'employee_id' => $loan->employee_id,
                'employee_name' => $employee_name,
                'department_id' => $loan->department_id,
                'department_name' => null,
                'amount' => (float)$loan->amount,
                'interest_rate' => (float)$loan->interest_rate,
                'paid_amount' => (float)$loan->paid_amount,
                'start_date' => optional($loan->start_date)->toDateString(),
                'end_date' => optional($loan->end_date)->toDateString(),
                'status' => (string)$loan->status,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $val = Validator::make($request->all(), [
            'employee_id' => 'required|exists:staff,id',
            'amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'repayment_method' => 'required|string',
            'description' => 'nullable|string',
        ]);
        $val->validate();

        $staff = Staff::find($request->integer('employee_id'));

        $loan = Loan::create([
            'employee_id' => $request->integer('employee_id'),
            'department_id' => $staff?->level?->department_id,
            'level_id' => $staff?->level_id,
            'amount' => $request->input('amount'),
            'interest_rate' => $request->input('interest_rate'),
            'paid_amount' => 0,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'repayment_method' => $request->input('repayment_method'),
            'description' => $request->input('description'),
            'status' => 'active',
        ]);

        return response()->json($loan, 201);
    }

    public function update($id, Request $request)
    {
        $loan = Loan::findOrFail((int)$id);

        $data = $request->only(['employee_id','amount','interest_rate','start_date','end_date','repayment_method','description','status','paid_amount']);

        if (isset($data['employee_id'])) {
            $staff = Staff::findOrFail((int)$data['employee_id']);
            $data['department_id'] = $staff->department_id;
            $data['level_id'] = $staff->level_id;
        }

        $loan->fill($data);
        $loan->save();

        return response()->json($loan);
    }

    public function destroy($id)
    {
        $loan = Loan::findOrFail((int)$id);
        $loan->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    public function export(Request $request)
    {
        $query = Loan::query()->with('employee');
        if ($v = $request->input('employee_id')) $query->where('employee_id', $v);
        if ($v = $request->input('department')) $query->where('department_id', $v);
        if ($v = $request->input('level')) $query->where('level_id', $v);
        if ($v = $request->input('status')) $query->where('status', $v);
        if ($v = $request->input('start_from')) $query->whereDate('start_date', '>=', $v);
        if ($v = $request->input('start_to')) $query->whereDate('start_date', '<=', $v);

        $rows = $query->orderBy('id')->get();

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Employee ID','Employee Name','Department ID','Level ID','Amount','Interest Rate','Paid Amount','Start Date','End Date','Status']);
            foreach ($rows as $loan) {
                $emp = $loan->employee;
                $employee_name = $emp ? trim(($emp->firstname ?? '').' '.($emp->surname ?? '')) : '';
                fputcsv($out, [
                    $loan->id,
                    $loan->employee_id,
                    $employee_name,
                    $loan->department_id,
                    $loan->level_id,
                    $loan->amount,
                    $loan->interest_rate,
                    $loan->paid_amount,
                    optional($loan->start_date)->toDateString(),
                    optional($loan->end_date)->toDateString(),
                    $loan->status,
                ]);
            }
            fclose($out);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="loans.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $query = Loan::query()->with('employee');
        if ($v = $request->input('employee_id')) $query->where('employee_id', $v);
        if ($v = $request->input('department')) $query->where('department_id', $v);
        if ($v = $request->input('level')) $query->where('level_id', $v);
        if ($v = $request->input('status')) $query->where('status', $v);
        if ($v = $request->input('start_from')) $query->whereDate('start_date', '>=', $v);
        if ($v = $request->input('start_to')) $query->whereDate('start_date', '<=', $v);

        $rows = $query->orderBy('id')->get();

        $html = view('exports.loans', compact('rows'))->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('loans.pdf');
        }
        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
