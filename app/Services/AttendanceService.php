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

        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            throw new \RuntimeException('You have already clocked in today at ' . Carbon::parse($existing->clock_in)->format('H:i:s') . '.');
        }

        if ($existing && $existing->status === 'on_leave') {
            throw new \RuntimeException('Cannot clock in: You are scheduled on approved leave for today.');
        }

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

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            throw new \RuntimeException('Cannot clock out: You have not clocked in for today yet.');
        }

        if ($attendance->clock_out) {
            throw new \RuntimeException('You have already clocked out today at ' . Carbon::parse($attendance->clock_out)->format('H:i:s') . '.');
        }

        $clockIn = Carbon::parse($attendance->clock_in);
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
