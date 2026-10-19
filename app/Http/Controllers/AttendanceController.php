<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $departmentId = $request->input('department_id');

        $query = Attendance::with(['employee.department', 'employee.designation'])
            ->where('date', $date);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $attendances = $query->orderBy('clock_in')->paginate(20)->withQueryString();
        $departments = Department::where('status', 'active')->orderBy('name')->get();

        // Statistics for this date
        $totalPresent = Attendance::where('date', $date)->whereIn('status', ['present', 'late'])->count();
        $totalLate = Attendance::where('date', $date)->where('status', 'late')->count();
        $totalHalfDay = Attendance::where('date', $date)->where('status', 'half_day')->count();
        $totalEmployees = Employee::where('employment_status', 'active')->count();

        return view('attendance.index', compact(
            'attendances',
            'departments',
            'date',
            'totalPresent',
            'totalLate',
            'totalHalfDay',
            'totalEmployees'
        ));
    }

    public function myAttendance(Request $request)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'No employee profile linked to your user account.');
        }

        $month = (int)$request->input('month', Carbon::now()->month);
        $year = (int)$request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->get();

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        $totalPresent = $attendances->whereIn('status', ['present', 'late'])->count();
        $totalLate = $attendances->where('status', 'late')->count();
        $totalHours = $attendances->sum('work_hours');
        $totalOvertime = $attendances->sum('overtime_hours');

        return view('attendance.my-attendance', compact(
            'employee',
            'attendances',
            'todayAttendance',
            'month',
            'year',
            'totalPresent',
            'totalLate',
            'totalHours',
            'totalOvertime'
        ));
    }

    public function clockIn(Request $request)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return back()->with('error', 'No employee profile linked to your user account.');
        }

        try {
            $attendance = $this->attendanceService->clockIn($employee, $request->input('notes'));

            AuditLogger::log(
                action: 'clock_in',
                module: 'attendance',
                modelType: Attendance::class,
                modelId: $attendance->id
            );

            return back()->with('success', 'Clock-in successful at ' . Carbon::parse($attendance->clock_in)->format('H:i:s'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function clockOut(Request $request)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return back()->with('error', 'No employee profile linked to your user account.');
        }

        try {
            $attendance = $this->attendanceService->clockOut($employee, $request->input('notes'));

            AuditLogger::log(
                action: 'clock_out',
                module: 'attendance',
                modelType: Attendance::class,
                modelId: $attendance->id,
                changes: ['work_hours' => $attendance->work_hours]
            );

            return back()->with('success', "Clock-out successful! Total work hours today: {$attendance->work_hours} hrs.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function adjust(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,late,half_day,absent,on_leave,holiday'],
            'notes' => ['nullable', 'string'],
        ]);

        $date = $attendance->date->toDateString();
        $clockIn = ! empty($validated['clock_in']) ? Carbon::parse($date . ' ' . $validated['clock_in']) : null;
        $clockOut = ! empty($validated['clock_out']) ? Carbon::parse($date . ' ' . $validated['clock_out']) : null;

        $workHours = 0.00;
        $overtimeHours = 0.00;
        if ($clockIn && $clockOut) {
            $minutes = max(0, $clockOut->diffInMinutes($clockIn));
            $workHours = round($minutes / 60, 2);
            $overtimeHours = max(0, round($workHours - 8.0, 2));
        }

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => $validated['status'],
            'work_hours' => $workHours,
            'overtime_hours' => $overtimeHours,
            'notes' => $validated['notes'],
            'is_adjusted' => true,
            'adjusted_by' => Auth::id(),
        ]);

        AuditLogger::log(
            action: 'adjusted_attendance',
            module: 'attendance',
            modelType: Attendance::class,
            modelId: $attendance->id,
            changes: $validated
        );

        return back()->with('success', 'Attendance record adjusted successfully.');
    }
}
