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
            creatorId: $this->hrUser->id
        );

        $this->assertEquals('processed', $batch->status);

        $payslip = Payslip::where('payroll_batch_id', $batch->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($payslip);

        // Daily rate = 10,000,000 / 20 work days = 500,000
        // Unpaid leave deduction = 500,000 * 2 = 1,000,000
        $deductions = collect($payslip->breakdown['deductions'] ?? []);
        $unpaidLeaveItem = $deductions->first(fn ($d) => str_contains($d['label'], 'Unpaid Leave'));
        $this->assertNotNull($unpaidLeaveItem);
        $this->assertEquals(1000000.0, (float)$unpaidLeaveItem['amount']);

        // Allowances = 1,000,000 (house) + 500,000 (transport) + 500,000 (meal) = 2,000,000
        // Deductions = 500,000 (tax) + 300,000 (insurance) + 1,000,000 (unpaid leave) = 1,800,000
        // Net = (10,000,000 + 2,000,000) - 1,800,000 = 10,200,000
        $this->assertEquals(10000000.0, (float)$payslip->basic_salary);
        $this->assertEquals(2000000.0, (float)$payslip->total_allowance);
        $this->assertEquals(1800000.0, (float)$payslip->total_deduction);
        $this->assertEquals(10200000.0, (float)$payslip->net_salary);
    }

    public function test_cannot_regenerate_or_recalculate_paid_batch(): void
    {
        $batch = $this->payrollService->generateMonthlyPayroll(
            month: 10,
            year: 2026,
            creatorId: $this->hrUser->id
        );

        $batch->update(['status' => 'paid']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already finalized and marked as PAID');

        $this->payrollService->generateMonthlyPayroll(
            month: 10,
            year: 2026,
            creatorId: $this->hrUser->id
        );
    }

    public function test_payroll_proration_for_mid_month_hire(): void
    {
        $dept = Department::where('code', 'FIN')->first();
        $desig = Designation::where('code', 'ACC')->first();
        $shift = Shift::first();

        $midHireUser = User::create([
            'name' => 'Mid Month Hire',
            'email' => 'midhire@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Joins Oct 19, 2026 (10 working days remaining out of 22 in October)
        $midEmployee = Employee::create([
            'user_id' => $midHireUser->id,
            'employee_code' => 'EMP-MID-001',
            'first_name' => 'Mid',
            'last_name' => 'Hire',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2026-10-19',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        SalaryStructure::create([
            'employee_id' => $midEmployee->id,
            'basic_salary' => 22000000, // 22M / 22 days = 1M/day
            'house_allowance' => 2200000, // 2.2M / 22 days = 100k/day
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'tax_deduction' => 0,
            'insurance_deduction' => 0,
            'effective_date' => '2026-10-19',
        ]);

        $batch = $this->payrollService->generateMonthlyPayroll(
            month: 10,
            year: 2026,
            creatorId: $this->hrUser->id
        );

        $payslip = Payslip::where('payroll_batch_id', $batch->id)
            ->where('employee_id', $midEmployee->id)
            ->first();

        $this->assertNotNull($payslip);
        // 10 active working days out of 22 -> 10/22 * 22,000,000 = 10,000,000
        $this->assertEquals(10000000.0, (float)$payslip->basic_salary);
        // 10/22 * 2,200,000 = 1,000,000
        $this->assertEquals(1000000.0, (float)$payslip->total_allowance);
        $this->assertEquals(11000000.0, (float)$payslip->net_salary);
        $this->assertStringContainsString('Prorated', $payslip->breakdown['earnings'][0]['label']);
    }
}
