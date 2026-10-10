<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class AttendanceService
{
    /**
     * Handle employee clock-in.
     */
    public function clockIn(Employee $employee, ?string $notes = null): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $shift = $employee->shift;
        $status = 'present';

        if ($shift) {
            $shiftStart = Carbon::parse($today . ' ' . $shift->start_time);
            $graceEnd = $shiftStart->copy()->addMinutes($shift->grace_period_minutes);

            if ($now->greaterThan($graceEnd)) {
                $status = 'late';
            }
        }

        return Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'clock_in' => $now,
                'status' => $status,
                'ip_address' => Request::ip(),
                'notes' => $notes,
            ]
        );
    }

    /**
     * Handle employee clock-out.
     */
    public function clockOut(Employee $employee, ?string $notes = null): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'date' => $today,
        ]);

        $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : $now->copy()->subHours(8);
        $totalMinutes = max(0, $now->diffInMinutes($clockIn));
        $workHours = round($totalMinutes / 60, 2);

        // Standard 8 work hours, rest is overtime
        $overtimeHours = max(0, round($workHours - 8.0, 2));

        $attendance->clock_out = $now;
        $attendance->work_hours = $workHours;
        $attendance->overtime_hours = $overtimeHours;
        if ($notes) {
            $attendance->notes = trim(($attendance->notes ? $attendance->notes . ' | ' : '') . $notes);
        }
        $attendance->save();

        return $attendance;
    }
}
