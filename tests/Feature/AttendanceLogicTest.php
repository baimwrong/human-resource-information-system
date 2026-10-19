<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceLogicTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceService $attendanceService;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceService = app(AttendanceService::class);

        $dept = Department::create(['code' => 'OPS', 'name' => 'Operations', 'status' => 'active']);
        $desig = Designation::create(['code' => 'OP1', 'title' => 'Operator', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Regular', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'grace_period_minutes' => 15]);

        $user = User::create([
            'name' => 'Jane Operator',
            'email' => 'jane@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-010',
            'first_name' => 'Jane',
            'last_name' => 'Operator',
            'gender' => 'female',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);
    }

    public function test_clock_in_creates_record_and_prevents_duplicate(): void
    {
        $attendance = $this->attendanceService->clockIn($this->employee->id);

        $this->assertNotNull($attendance);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        // Attempt second clock-in on the same day -> must throw ValidationException
        $this->expectException(ValidationException::class);
        $this->attendanceService->clockIn($this->employee->id);
    }

    public function test_clock_out_without_clock_in_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->attendanceService->clockOut($this->employee->id);
    }

    public function test_clock_in_blocked_when_on_approved_leave(): void
    {
        $leaveType = LeaveType::create([
            'code' => 'AL',
            'name' => 'Annual Leave',
            'days_allowed' => 12,
            'is_paid' => true,
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'total_days' => 3,
            'reason' => 'Annual vacation',
            'status' => 'approved',
        ]);

        $this->expectException(ValidationException::class);
        $this->attendanceService->clockIn($this->employee->id);
    }
}
