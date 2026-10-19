<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Services\AuditLogger;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    protected PayrollCalculationService $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index()
    {
        $batches = PayrollBatch::with('creator')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');

        return view('payroll.index', compact('batches', 'currentMonth', 'currentYear'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2035'],
        ]);

        $batch = $this->payrollService->generateMonthlyPayroll(
            $validated['month'],
            $validated['year'],
            Auth::id()
        );

        AuditLogger::log(
            action: 'generated_payroll_batch',
            module: 'payroll',
            modelType: PayrollBatch::class,
            modelId: $batch->id,
            changes: ['month' => $batch->month, 'year' => $batch->year, 'net' => $batch->total_net]
        );

        return redirect()->route('payroll.batch', $batch)->with('success', "Payroll batch for {$batch->period_name} generated successfully. {$batch->total_employees} payslips created.");
    }

    public function showBatch(PayrollBatch $batch)
    {
        $batch->load(['payslips.employee.department', 'payslips.employee.designation']);
        return view('payroll.batch', compact('batch'));
    }

    public function payslip(Payslip $payslip)
    {
        $user = Auth::user();

        // Enforce authorization: Admin/HR can view any payslip, non-HR can only view their own
        if (! $user->isHR() && (! $user->employee || $user->employee->id !== $payslip->employee_id)) {
            abort(403, 'Unauthorized access to payslip.');
        }

        $payslip->load(['employee.department', 'employee.designation', 'payrollBatch']);
        return view('payroll.payslip', compact('payslip'));
    }

    public function markPaid(Request $request, PayrollBatch $batch)
    {
        $paymentDate = $request->input('payment_date', Carbon::today()->toDateString());

        $batch->update([
            'status' => 'paid',
            'payment_date' => $paymentDate,
        ]);

        $batch->payslips()->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
        ]);

        AuditLogger::log(
            action: 'marked_payroll_paid',
            module: 'payroll',
            modelType: PayrollBatch::class,
            modelId: $batch->id
        );

        return back()->with('success', "Payroll batch {$batch->batch_number} marked as PAID.");
    }

    public function salaryStructures()
    {
        $employees = Employee::with(['salaryStructure', 'department', 'designation'])
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->paginate(15);

        return view('payroll.salary-structures', compact('employees'));
    }
}
