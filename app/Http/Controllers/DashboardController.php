<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollBatch;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Announcements visible to this user
        $announcements = Announcement::with('author')
            ->where(function ($q) use ($user) {
                $q->whereNull('target_department_id');
                if ($user->employee && $user->employee->department_id) {
                    $q->orWhere('target_department_id', $user->employee->department_id);
                }
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Check user clock-in status today
        $userAttendance = null;
        if ($user->employee) {
            $userAttendance = Attendance::where('employee_id', $user->employee->id)
                ->where('date', $today)
                ->first();
        }

        // Executive / HR Manager Metrics
        if ($user->isHR() || $user->isManager()) {
            $totalEmployees = Employee::count();
            $activeEmployees = Employee::where('employment_status', 'active')->count();
            $totalDepartments = Department::count();

            $todayPresent = Attendance::where('date', $today)
                ->whereIn('status', ['present', 'late'])
                ->count();

            $todayOnLeave = LeaveRequest::where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->count();

            $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

            $latestBatch = PayrollBatch::orderByDesc('year')
                ->orderByDesc('month')
                ->first();

            // Department headcount for Chart.js
            $departments = Department::withCount('employees')->get();
            $deptLabels = $departments->pluck('name')->toArray();
            $deptCounts = $departments->pluck('employees_count')->toArray();

            // Recent 7 days attendance trend
            $trendDates = [];
            $trendPresent = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i)->toDateString();
                $trendDates[] = Carbon::today()->subDays($i)->format('d M');
                $trendPresent[] = Attendance::where('date', $d)->whereIn('status', ['present', 'late'])->count();
            }

            // Recent audit logs
            $recentAuditLogs = AuditLog::with('user')
                ->orderByDesc('created_at')
                ->take(6)
                ->get();

            return view('dashboard.index', compact(
                'totalEmployees',
                'activeEmployees',
                'totalDepartments',
                'todayPresent',
                'todayOnLeave',
                'pendingLeaves',
                'latestBatch',
                'deptLabels',
                'deptCounts',
                'trendDates',
                'trendPresent',
                'announcements',
                'userAttendance',
                'recentAuditLogs'
            ));
        }

        // Employee self-service dashboard
        $leaveBalances = $user->employee
            ? LeaveBalance::with('leaveType')
                ->where('employee_id', $user->employee->id)
                ->where('year', $currentYear)
                ->get()
            : collect();

        $recentLeaves = $user->employee
            ? LeaveRequest::with('leaveType')
                ->where('employee_id', $user->employee->id)
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
            : collect();

        $latestPayslip = $user->employee
            ? $user->employee->payslips()->latest()->first()
            : null;

        return view('dashboard.index', compact(
            'announcements',
            'userAttendance',
            'leaveBalances',
            'recentLeaves',
            'latestPayslip'
        ));
    }
}
