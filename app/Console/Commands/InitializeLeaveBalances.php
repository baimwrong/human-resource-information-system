<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;

class InitializeLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:initialize-leave-balances {year? : Target calendar year (default: current year)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision or roll forward annual leave quotas for all active employees for a given calendar year.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int)($this->argument('year') ?? date('Y'));

        $this->info("Initializing leave balances for calendar year: {$year}...");

        $employees = Employee::where('employment_status', 'active')->get();
        $leaveTypes = LeaveType::all();

        if ($employees->isEmpty()) {
            $this->warn('No active employees found to initialize leave balances.');
            return self::SUCCESS;
        }

        if ($leaveTypes->isEmpty()) {
            $this->warn('No leave types configured in the system.');
            return self::SUCCESS;
        }

        $provisionedCount = 0;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                $balance = LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    [
                        'allocated_days' => $type->days_allowed,
                        'used_days' => 0.0,
                        'pending_days' => 0.0,
                    ]
                );

                if ($balance->wasRecentlyCreated) {
                    $provisionedCount++;
                }
            }
        }

        $this->info("Successfully provisioned {$provisionedCount} leave balance records for year {$year}.");
        return self::SUCCESS;
    }
}
