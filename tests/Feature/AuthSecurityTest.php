<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rate_limiting_blocks_after_too_many_attempts(): void
    {
        RateLimiter::clear('admin@example.com|127.0.0.1');

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'WrongPassword!',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // The 6th attempt should be blocked by rate limiter
        $blockedResponse = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'WrongPassword!',
        ]);

        $blockedResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_idor_prevention_on_payslip_access(): void
    {
        $dept = Department::create(['code' => 'ENG', 'name' => 'Engineering', 'status' => 'active']);
        $desig = Designation::create(['code' => 'DEV', 'title' => 'Developer', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Regular', 'start_time' => '09:00:00', 'end_time' => '18:00:00']);

        // Employee 1 (Victim)
        $user1 = User::create([
            'name' => 'Employee One',
            'email' => 'emp1@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);
        $emp1 = Employee::create([
            'user_id' => $user1->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Employee',
            'last_name' => 'One',
            'gender' => 'male',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        // Employee 2 (Attacker)
        $user2 = User::create([
            'name' => 'Employee Two',
            'email' => 'emp2@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);
        $emp2 = Employee::create([
            'user_id' => $user2->id,
            'employee_code' => 'EMP-002',
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'gender' => 'female',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        // HR User
        $userHR = User::create([
            'name' => 'HR Manager',
            'email' => 'hr@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $batch = PayrollBatch::create([
            'batch_code' => 'BATCH-2026-01',
            'month' => 1,
            'year' => 2026,
            'status' => 'generated',
            'processed_by' => $userHR->id,
        ]);

        $payslip = Payslip::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $emp1->id,
            'payslip_number' => 'PS-2026-001',
            'basic_salary' => 10000000,
            'net_salary' => 10000000,
            'status' => 'generated',
        ]);

        // Attacker (Employee 2) attempts to view Employee 1's payslip -> 403 Forbidden
        $this->actingAs($user2)
            ->get(route('payroll.payslip', $payslip))
            ->assertStatus(403);

        // Victim (Employee 1) views own payslip -> 200 OK
        $this->actingAs($user1)
            ->get(route('payroll.payslip', $payslip))
            ->assertStatus(200);

        // HR Manager views payslip -> 200 OK
        $this->actingAs($userHR)
            ->get(route('payroll.payslip', $payslip))
            ->assertStatus(200);
    }

    public function test_manager_cannot_create_employees(): void
    {
        $managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->actingAs($managerUser)
            ->get(route('employees.create'))
            ->assertStatus(403);
    }
}
