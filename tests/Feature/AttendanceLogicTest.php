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
        $attendance = $this->attendanceService->clockIn($this->employee);

        $this->assertNotNull($attendance);
        $this->assertEquals(Carbon::today()->toDateString(), $attendance->date->toDateString());
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
        ]);

        // Attempt second clock-in on the same day -> must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You have already clocked in today');
        $this->attendanceService->clockIn($this->employee);
    }

    public function test_clock_out_without_clock_in_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot clock out: You have not clocked in for today yet.');
        $this->attendanceService->clockOut($this->employee);
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

        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'on_leave',
            'notes' => 'On approved leave: ' . $leaveType->name,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot clock in: You are scheduled on approved leave for today.');
        $this->attendanceService->clockIn($this->employee);
    }

    public function test_night_shift_clock_out_across_midnight(): void
    {
        // Set fixed test now at 06:00 on Oct 27
        Carbon::setTestNow(Carbon::parse('2026-10-27 06:00:00'));

        // 1. Clock in yesterday at 22:00 (Oct 26)
        $clockInTime = Carbon::parse('2026-10-26 22:00:00');
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-26',
            'clock_in' => $clockInTime,
            'status' => 'present',
        ]);

        // 2. Clock out today at 06:00 (8 elapsed hours)
        $updatedAttendance = $this->attendanceService->clockOut($this->employee);

        $this->assertNotNull($updatedAttendance->clock_out);
        $this->assertEquals($attendance->id, $updatedAttendance->id);
        // 8 elapsed hours minus 1 hour meal break = 7.00 effective work hours
        $this->assertEquals(7.00, (float)$updatedAttendance->work_hours);
        $this->assertEquals(0.00, (float)$updatedAttendance->overtime_hours);

        Carbon::setTestNow(); // Reset mocked time
    }
}
