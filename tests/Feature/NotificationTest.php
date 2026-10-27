<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\LeaveRequestedNotification;
use App\Notifications\LeaveStatusUpdatedNotification;
use App\Notifications\PayslipGeneratedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;
    protected User $managerUser;
    protected User $employeeUser;
    protected Employee $managerEmployee;
    protected Employee $subordinateEmployee;
    protected LeaveType $annualLeave;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $desig = Designation::create(['title' => 'Developer', 'code' => 'DEV', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Standard', 'start_time' => '09:00:00', 'end_time' => '17:00:00']);

        $this->hrUser = User::create([
            'name' => 'HR Manager',
            'email' => 'hr@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'name' => 'Team Lead',
            'email' => 'lead@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->managerEmployee = Employee::create([
            'user_id' => $this->managerUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'employee_code' => 'EMP-LEAD',
            'first_name' => 'Team',
            'last_name' => 'Lead',
            'email' => 'lead@example.com',
            'join_date' => '2025-01-01',
            'employment_status' => 'active',
            'employment_type' => 'permanent',
        ]);

        $this->employeeUser = User::create([
            'name' => 'Staff Dev',
            'email' => 'staff@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->subordinateEmployee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'manager_id' => $this->managerEmployee->id,
            'employee_code' => 'EMP-STAFF',
            'first_name' => 'Staff',
            'last_name' => 'Dev',
            'email' => 'staff@example.com',
            'join_date' => '2025-01-01',
            'employment_status' => 'active',
            'employment_type' => 'permanent',
        ]);

        $this->annualLeave = LeaveType::create([
            'name' => 'Annual Leave',
            'code' => 'AL',
            'days_per_year' => 12,
            'requires_approval' => true,
            'is_paid' => true,
        ]);

        LeaveBalance::create([
            'employee_id' => $this->subordinateEmployee->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => (int)date('Y'),
            'allocated_days' => 12,
            'used_days' => 0,
            'pending_days' => 0,
        ]);
    }

    public function test_manager_receives_notification_when_subordinate_submits_leave(): void
    {
        Notification::fake();

        $startDate = Carbon::now()->addDays(5)->toDateString();
        $endDate = Carbon::now()->addDays(6)->toDateString();

        $response = $this->actingAs($this->employeeUser)->post('/leaves', [
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual family vacation',
        ]);

        $response->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $this->managerUser,
            LeaveRequestedNotification::class
        );
    }

    public function test_employee_receives_notification_when_leave_is_approved(): void
    {
        Notification::fake();

        $startDate = Carbon::now()->addDays(5)->toDateString();
        $endDate = Carbon::now()->addDays(6)->toDateString();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->subordinateEmployee->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 2,
            'reason' => 'Doctor appointment',
            'status' => 'pending',
        ]);

        $this->actingAs($this->hrUser)->post("/leaves/{$leaveRequest->id}/approve", [
            'action_reason' => 'Approved on medical grounds',
        ]);

        Notification::assertSentTo(
            $this->employeeUser,
            LeaveStatusUpdatedNotification::class
        );
    }

    public function test_employee_receives_notification_when_payroll_batch_is_marked_paid(): void
    {
        Notification::fake();

        $batch = PayrollBatch::create([
            'batch_number' => 'BATCH-2026-05',
            'month' => 5,
            'year' => 2026,
            'total_gross' => 5000,
            'total_deductions' => 500,
            'total_net' => 4500,
            'status' => 'processed',
            'created_by' => $this->hrUser->id,
        ]);

        Payslip::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $this->subordinateEmployee->id,
            'slip_number' => 'SLIP-2026-05-001',
            'month' => 5,
            'year' => 2026,
            'basic_salary' => 5000,
            'gross_salary' => 5000,
            'net_salary' => 4500,
            'total_deductions' => 500,
            'payment_status' => 'unpaid',
            'breakdown' => ['basic' => 5000],
        ]);

        $response = $this->actingAs($this->hrUser)->post("/payroll/batch/{$batch->id}/mark-paid", [
            'payment_date' => '2026-05-31',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $this->employeeUser,
            PayslipGeneratedNotification::class
        );
    }
}
