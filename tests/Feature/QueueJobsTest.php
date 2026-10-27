<?php

namespace Tests\Feature;

use App\Jobs\GeneratePayrollBatchJob;
use App\Jobs\ProcessBulkImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $csvContent = "employee_code,first_name,last_name,email,department_code,designation_code,join_date,basic_salary\n";
        $csvContent .= "EMP999,Test,Queue,test.queue@example.com,TECH,DEV,2025-01-01,5000\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/bulk-import/upload', [
            'import_type' => 'employees',
            'csv_file' => $file,
            'run_in_background' => 1,
        ]);

        $response->assertRedirect('/bulk-import');
        $response->assertSessionHas('info');

        Queue::assertPushed(ProcessBulkImportJob::class);
    }

    public function test_payroll_generation_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $hr = User::create([
            'name' => 'HR Manager',
            'email' => 'hr@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $response = $this->actingAs($hr)->post('/payroll/generate', [
            'month' => 6,
            'year' => 2026,
            'run_in_background' => 1,
        ]);

        $response->assertRedirect('/payroll');
        $response->assertSessionHas('info');

        Queue::assertPushed(GeneratePayrollBatchJob::class, function ($job) use ($hr) {
            return $job->month === 6 && $job->year === 2026 && $job->userId === $hr->id;
        });
    }
}
