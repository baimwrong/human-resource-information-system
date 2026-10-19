<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollCalculationService $payrollService;
    protected User $hrUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payrollService = app(PayrollCalculationService::class);

        Setting::set('work_days_per_month', '20');
        Setting::set('company_name', 'Acme Corporation');
        Setting::set('currency_symbol', 'IDR');

        $dept = Department::create(['code' => 'FIN', 'name' => 'Finance', 'status' => 'active']);
        $desig = Designation::create(['code' => 'ACC', 'title' => 'Accountant', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Regular', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $this->hrUser = User::create([
            'name' => 'HR Manager',
            'email' => 'hr_calc@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $empUser = User::create([
            'name' => 'Bob Accountant',
            'email' => 'bob@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $empUser->id,
            'employee_code' => 'EMP-CALC-001',
            'first_name' => 'Bob',
            'last_name' => 'Accountant',
            'gender' => 'male',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        SalaryStructure::create([
            'employee_id' => $this->employee->id,
            'basic_salary' => 10000000,
            'house_allowance' => 1000000,
            'transport_allowance' => 500000,
            'meal_allowance' => 500000,
            'tax_deduction' => 500000,
            'insurance_deduction' => 300000,
            'effective_date' => '2024-01-01',
        ]);
    }

    public function test_payroll_calculates_unpaid_leave_deduction_with_settings_work_days(): void
    {
        $unpaidLeaveType = LeaveType::create([
            'code' => 'UL',
            'name' => 'Unpaid Leave',
            'days_allowed' => 30,
            'is_paid' => false,
        ]);

        // 2 unpaid leave days in October 2026
        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $unpaidLeaveType->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'total_days' => 2,
            'reason' => 'Personal unpaid time off',
            'status' => 'approved',
        ]);

        $batch = $this->payrollService->generateMonthlyPayroll(
            month: 10,
            year: 2026,
            processedBy: $this->hrUser->id
        );

        $this->assertEquals('generated', $batch->status);

        $payslip = Payslip::where('payroll_batch_id', $batch->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($payslip);

        // Daily rate = 10,000,000 / 20 work days = 500,000
        // Unpaid leave deduction = 500,000 * 2 = 1,000,000
        $this->assertEquals(1000000.0, (float)$payslip->unpaid_leave_deduction);

        // Gross = 10,000,000 (basic) + 1,000,000 (house) + 500,000 (transport) + 500,000 (meal) = 12,000,000
        // Deductions = 500,000 (tax) + 300,000 (insurance) + 1,000,000 (unpaid leave) = 1,800,000
        // Net = 12,000,000 - 1,800,000 = 10,200,000
        $this->assertEquals(12000000.0, (float)$payslip->gross_salary);
        $this->assertEquals(1800000.0, (float)$payslip->total_deductions);
        $this->assertEquals(10200000.0, (float)$payslip->net_salary);
    }

    public function test_cannot_regenerate_or_recalculate_paid_batch(): void
    {
        $batch = $this->payrollService->generateMonthlyPayroll(
            month: 10,
            year: 2026,
            processedBy: $this->hrUser->id
        );

        $batch->update(['status' => 'paid']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot recalculate payroll batch with status 'paid'");

        $this->payrollService->recalculateBatch($batch->id);
    }
}
