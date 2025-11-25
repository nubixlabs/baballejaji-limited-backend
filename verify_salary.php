<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Staff;
use App\Models\Level;
use App\Models\Payslip;
use App\Models\SalaryPayment;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Ensure we have a level
$level = Level::firstOrCreate(
    ['name' => 'Test Level'],
    [
        'basic_pay_rate' => 5000,
        'basic_pay_period' => 'Monthly',
        'department_id' => 1 // Assuming dept 1 exists or nullable
    ]
);

// Ensure we have a staff member
$staff = Staff::firstOrCreate(
    ['email_address' => 'test_salary@example.com'],
    [
        'surname' => 'Test',
        'firstname' => 'User',
        'level_id' => $level->id,
        'date_of_employment' => now(),
        'department_id' => 1
    ]
);

echo "Staff ID: " . $staff->id . "\n";

// Create some attendance for this month
$monthStart = now()->startOfMonth();
AttendanceRecord::create([
    'employee_id' => $staff->id,
    'date' => $monthStart->copy()->addDays(1),
    'status' => 'present',
    'clock_in' => '08:00',
    'clock_out' => '17:00'
]);

// 1. Test Generate Payslips
echo "\nTesting Generate Payslips...\n";
$response = app()->handle(
    Illuminate\Http\Request::create('/api/filling/payslips/generate', 'POST', [
        'slip_name' => 'Test Slip ' . now()->format('M Y'),
        'date_from' => $monthStart->toDateString(),
        'level_ids' => [$level->id]
    ])
);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";

$payslip = Payslip::where('employee_id', $staff->id)->latest()->first();
if ($payslip) {
    echo "Payslip Generated: ID " . $payslip->id . ", Amount: " . $payslip->total_pay . "\n";
} else {
    echo "Payslip NOT generated!\n";
}

// 2. Test View Payslips
echo "\nTesting View Payslips...\n";
$response = app()->handle(
    Illuminate\Http\Request::create('/api/filling/payslips', 'GET', [
        'salary' => 'monthly',
        'find' => 'Test'
    ])
);
echo "Status: " . $response->getStatusCode() . "\n";
// echo "Content: " . substr($response->getContent(), 0, 500) . "...\n"; // Truncate

// 3. Test Add Payment
echo "\nTesting Add Payment...\n";
$response = app()->handle(
    Illuminate\Http\Request::create('/api/filling/salary-payments', 'POST', [
        'employee_ids' => [$staff->id],
        'salary_period' => 'monthly',
        'date_from' => $monthStart->toDateString(),
        'cheque_account' => 'Test Bank'
    ])
);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";

$payment = SalaryPayment::where('employee_id', $staff->id)->latest()->first();
if ($payment) {
    echo "Payment Created: ID " . $payment->id . ", Amount: " . $payment->total_pay . ", Payslip ID: " . $payment->payslip_id . "\n";
} else {
    echo "Payment NOT created!\n";
}
