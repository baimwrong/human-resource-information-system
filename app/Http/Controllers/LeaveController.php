<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();

        return view('leaves.index', compact('requests', 'pendingCount'));
    }

    public function myLeaves(Request $request)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'No employee profile linked to your user account.');
        }

        $currentYear = (int)date('Y');
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get();

        $requests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('leaves.my-leaves', compact('employee', 'balances', 'requests'));
    }

    public function create()
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'No employee profile linked.');
        }

        $currentYear = (int)date('Y');
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get();

        $leaveTypes = LeaveType::all();

        return view('leaves.create', compact('balances', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $employee = Auth::user()->employee;
        if (! $employee) {
            return back()->with('error', 'No employee profile linked.');
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        if ($leaveType->requires_attachment && ! $request->hasFile('attachment')) {
            return back()->withErrors(['attachment' => "An attachment (e.g., doctor certificate) is mandatory for {$leaveType->name}."])->withInput();
        }

        // Prevent overlapping leave applications
        $overlapping = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhere(function ($sub) use ($validated) {
                      $sub->where('start_date', '<=', $validated['start_date'])
                          ->where('end_date', '>=', $validated['end_date']);
                  });
            })->exists();

        if ($overlapping) {
            return back()->withErrors(['start_date' => 'You already have an active leave application (pending or approved) overlapping this date range.'])->withInput();
        }

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        // Calculate working days (excluding Saturdays and Sundays)
        $period = CarbonPeriod::create($start, $end);
        $totalDays = 0;
        foreach ($period as $date) {
            if (! $date->isWeekend()) {
                $totalDays++;
            }
        }

        if ($totalDays <= 0) {
            return back()->withErrors(['start_date' => 'Selected date range contains only weekends.'])->withInput();
        }

        $currentYear = (int)$start->year;

        // Check balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $currentYear)
            ->first();

        if ($balance && $balance->remaining_days < $totalDays) {
            return back()->withErrors(['leave_type_id' => "Insufficient leave balance. You have {$balance->remaining_days} days remaining, but requested {$totalDays} days."])->withInput();
        }

        // Store private attachment on local disk for medical confidentiality
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'local');
        }

        DB::transaction(function () use ($employee, $validated, $totalDays, $attachmentPath, $balance) {
            LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
            ]);

            if ($balance) {
                $balance->increment('pending_days', $totalDays);
            }
        });

        AuditLogger::log(
            action: 'submitted_leave_request',
            module: 'leaves',
            changes: ['total_days' => $totalDays, 'start_date' => $validated['start_date']]
        );

        return redirect()->route('leaves.my-leaves')->with('success', 'Leave request submitted successfully.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        $user = Auth::user();

        // Prevent self-approval
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return back()->with('error', 'You cannot approve your own leave request.');
        }

        // Only HR or the employee's direct manager can approve
        if (! $user->isHR() && (! $user->employee || $user->employee->id !== $leaveRequest->employee->manager_id)) {
            return back()->with('error', 'Unauthorized. Only HR or the direct reporting supervisor can review this leave request.');
        }

        DB::transaction(function () use ($leaveRequest, $request) {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'action_reason' => $request->input('action_reason', 'Approved by HR / Manager'),
                'acted_at' => now(),
            ]);

            // Update balance
            $year = (int)$leaveRequest->start_date->year;
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $year)
                ->first();

            if ($balance) {
                $balance->decrement('pending_days', $leaveRequest->total_days);
                $balance->increment('used_days', $leaveRequest->total_days);
            }

            // Sync with attendance records
            $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
            foreach ($period as $date) {
                if (! $date->isWeekend()) {
                    Attendance::updateOrCreate(
                        [
                            'employee_id' => $leaveRequest->employee_id,
                            'date' => $date->toDateString(),
                        ],
                        [
                            'status' => 'on_leave',
                            'notes' => 'On approved leave: ' . $leaveRequest->leaveType->name,
                        ]
                    );
                }
            }
        });

        AuditLogger::log(
            action: 'approved_leave_request',
            module: 'leaves',
            modelType: LeaveRequest::class,
            modelId: $leaveRequest->id
        );

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $user = Auth::user();

        // Prevent self-rejection / tampering
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return back()->with('error', 'You cannot act on your own leave request.');
        }

        // Only HR or direct supervisor can reject
        if (! $user->isHR() && (! $user->employee || $user->employee->id !== $leaveRequest->employee->manager_id)) {
            return back()->with('error', 'Unauthorized. Only HR or the direct reporting supervisor can review this leave request.');
        }

        $request->validate(['action_reason' => 'required|string']);

        DB::transaction(function () use ($leaveRequest, $request) {
            $leaveRequest->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'action_reason' => $request->input('action_reason'),
                'acted_at' => now(),
            ]);

            // Release pending days on balance
            $year = (int)$leaveRequest->start_date->year;
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $year)
                ->first();

            if ($balance) {
                $balance->decrement('pending_days', $leaveRequest->total_days);
            }
        });

        AuditLogger::log(
            action: 'rejected_leave_request',
            module: 'leaves',
            modelType: LeaveRequest::class,
            modelId: $leaveRequest->id
        );

        return back()->with('success', 'Leave request rejected.');
    }

    public function downloadAttachment(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        $isSelf = $user->employee && $user->employee->id === $leaveRequest->employee_id;
        $isManagerOfEmp = $user->employee && $user->employee->id === $leaveRequest->employee->manager_id;

        if (! $user->isHR() && ! $isSelf && ! $isManagerOfEmp) {
            abort(403, 'Unauthorized access to confidential leave attachment.');
        }

        if (! $leaveRequest->attachment_path) {
            abort(404, 'No attachment on file for this leave request.');
        }

        if (Storage::disk('local')->exists($leaveRequest->attachment_path)) {
            return Storage::disk('local')->download($leaveRequest->attachment_path);
        }

        if (Storage::disk('public')->exists($leaveRequest->attachment_path)) {
            return Storage::disk('public')->download($leaveRequest->attachment_path);
        }

        abort(404, 'Attachment file not found on storage disk.');
    }
}
