<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyAttendanceCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:daily-attendance-check {date? : The date to verify in Y-m-d format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check daily employee attendance, reconcile approved leaves, and flag unexcused absences.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateStr = $this->argument('date') ?? Carbon::today()->toDateString();
        $date = Carbon::parse($dateStr);

        $this->info("Running attendance reconciliation for: {$dateStr}");

        // Skip weekends
        if ($date->isWeekend()) {
            $this->info("Date {$dateStr} is a weekend. Skipping absence generation.");
            return self::SUCCESS;
        }

        $activeEmployees = Employee::where('employment_status', 'active')->get();
        $absentCount = 0;
        $leaveCount = 0;

        foreach ($activeEmployees as $employee) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $dateStr)
                ->first();

            // 1. Employee has no attendance recorded today
            if (! $attendance) {
                // Check if employee has an approved leave today
                $onLeave = LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $dateStr)
                    ->where('end_date', '>=', $dateStr)
                    ->with('leaveType')
                    ->first();

                if ($onLeave) {
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $dateStr,
                        'status' => 'on_leave',
                        'work_hours' => 0.00,
                        'overtime_hours' => 0.00,
                        'notes' => 'On approved leave: ' . ($onLeave->leaveType->name ?? 'Leave'),
                    ]);
                    $leaveCount++;
                } else {
                    // Mark as unexcused absence
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $dateStr,
                        'status' => 'absent',
                        'work_hours' => 0.00,
                        'overtime_hours' => 0.00,
                        'notes' => 'Auto-marked: No clock-in detected by end of day.',
                    ]);
                    $absentCount++;
                }
            } elseif ($attendance->clock_in && ! $attendance->clock_out) {
                // Clock-in without clock-out: flag missing clock-out
                if (empty($attendance->notes)) {
                    $attendance->update([
                        'notes' => 'Missing clock-out record.',
                    ]);
                }
            }
        }

        $this->info("Attendance check completed. Absences recorded: {$absentCount}, Leaves synchronized: {$leaveCount}.");
        return self::SUCCESS;
    }
}
