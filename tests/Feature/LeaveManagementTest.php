<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;
    protected Employee $managerEmployee;
    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['code' => 'HRD', 'name' => 'Human Resources', 'status' => 'active']);
        $desig = Designation::create(['code' => 'MGR', 'title' => 'Manager', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Regular', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        $this->managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $this->managerEmployee = Employee::create([
            'user_id' => $this->managerUser->id,
            'employee_code' => 'EMP-MGR',
            'first_name' => 'Manager',
            'last_name' => 'User',
            'gender' => 'female',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        $this->leaveType = LeaveType::create([
            'code' => 'AL',
            'name' => 'Annual Leave',
            'days_allowed' => 12,
            'is_paid' => true,
        ]);

        LeaveBalance::create([
            'employee_id' => $this->managerEmployee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => (int)date('Y'),
            'allocated_days' => 12,
            'used_days' => 0,
            'pending_days' => 0,
        ]);
    }

    public function test_manager_cannot_self_approve_own_leave_request(): void
    {
        $startDate = Carbon::today()->addDays(7)->next(Carbon::MONDAY);
        $endDate = (clone $startDate)->addDays(2);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->managerEmployee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_days' => 3,
            'reason' => 'Rest and relaxation',
            'status' => 'pending',
        ]);

        // Manager attempts to approve their own request
        $response = $this->actingAs($this->managerUser)
            ->post(route('leaves.approve', $leaveRequest));

        $response->assertSessionHas('error', 'You cannot approve your own leave request.');
        $this->assertEquals('pending', $leaveRequest->fresh()->status);
    }

    public function test_cannot_submit_overlapping_leave_request(): void
    {
        $startDate = Carbon::today()->addDays(14)->next(Carbon::MONDAY);
        $endDate = (clone $startDate)->addDays(4); // Mon to Fri

        LeaveRequest::create([
            'employee_id' => $this->managerEmployee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_days' => 5,
            'reason' => 'Vacation trip',
            'status' => 'approved',
        ]);

        // Submit overlapping leave request
        $overlapStart = (clone $startDate)->addDays(2); // Wednesday
        $overlapEnd = (clone $endDate)->addDays(3);

        $response = $this->actingAs($this->managerUser)
            ->post(route('leaves.store'), [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => $overlapStart->toDateString(),
                'end_date' => $overlapEnd->toDateString(),
                'reason' => 'Overlapping trip',
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_cannot_delete_leave_type_with_active_balances(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->delete(route('leave-types.destroy', $this->leaveType));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('leave_types', ['id' => $this->leaveType->id]);
    }
}
