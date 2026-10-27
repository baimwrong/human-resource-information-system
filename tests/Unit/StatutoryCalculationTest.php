<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\StatutoryCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected StatutoryCalculationService $service;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StatutoryCalculationService();

        $user = User::create([
            'name' => 'John Dev',
            'email' => 'john.dev@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'employee',
        ]);

        $dept = Department::create(['name' => 'IT', 'code' => 'IT']);
        $desig = Designation::create(['title' => 'Engineer', 'code' => 'ENG', 'department_id' => $dept->id]);
        $shift = Shift::create(['name' => 'Standard', 'start_time' => '09:00:00', 'end_time' => '17:00:00']);

        $this->employee = Employee::create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'shift_id' => $shift->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'John',
            'last_name' => 'Dev',
            'email' => 'john.dev@example.com',
            'join_date' => '2025-01-01',
            'employment_status' => 'active',
            'employment_type' => 'permanent',
        ]);
    }

    public function test_configured_fixed_deductions_are_respected_by_default(): void
    {
        $structure = SalaryStructure::create([
            'employee_id' => $this->employee->id,
            'basic_salary' => 5000,
            'tax_deduction' => 250,
            'insurance_deduction' => 100,
            'effective_date' => '2025-01-01',
        ]);

        $result = $this->service->computeDeductions(
            $this->employee,
            $structure,
            grossSalary: 6000,
            useStatutoryFormulas: false
        );

        $this->assertEquals(250.00, $result['tax']);
        $this->assertEquals(100.00, $result['insurance']);
        $this->assertEquals('configured_fixed', $result['breakdown']['method']);
    }

    public function test_automatic_statutory_formulas_compute_progressive_tax_and_social_security(): void
    {
        $structure = SalaryStructure::create([
            'employee_id' => $this->employee->id,
            'basic_salary' => 5000,
            'tax_deduction' => 0,
            'insurance_deduction' => 0,
            'effective_date' => '2025-01-01',
        ]);

        $result = $this->service->computeDeductions(
            $this->employee,
            $structure,
            grossSalary: 6000,
            useStatutoryFormulas: true
        );

        // Social security: 2% pension ($120) + 1% health ($60) = $180
        $this->assertEquals(180.00, $result['insurance']);
        $this->assertEquals(120.00, $result['breakdown']['pension_contribution']);
        $this->assertEquals(60.00, $result['breakdown']['health_contribution']);

        // Taxable income = 6000 - 180 = 5820
        // Tier 1 ($0 - $4000): 4000 * 0.05 = 200
        // Tier 2 ($4000 - $5820): 1820 * 0.15 = 273
        // Total Tax = 473
        $this->assertEquals(473.00, $result['tax']);
        $this->assertEquals('statutory_progressive', $result['breakdown']['method']);
    }
}
