<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::withCount('requests')->orderBy('name')->get();
        return view('leaves.types', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:leave_types,code'],
            'name' => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'days_allowed' => ['required', 'integer', 'min:1'],
            'is_paid' => ['required', 'boolean'],
            'requires_attachment' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $leaveType = LeaveType::create($validated);

        AuditLogger::log(
            action: 'created_leave_type',
            module: 'leaves',
            modelType: LeaveType::class,
            modelId: $leaveType->id,
            changes: $validated
        );

        return back()->with('success', "Leave type '{$leaveType->name}' created successfully.");
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')->ignore($leaveType->id)],
            'name' => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->ignore($leaveType->id)],
            'days_allowed' => ['required', 'integer', 'min:1'],
            'is_paid' => ['required', 'boolean'],
            'requires_attachment' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $leaveType->update($validated);

        AuditLogger::log(
            action: 'updated_leave_type',
            module: 'leaves',
            modelType: LeaveType::class,
            modelId: $leaveType->id,
            changes: $validated
        );

        return back()->with('success', "Leave type '{$leaveType->name}' updated successfully.");
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->requests()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete leave type that has associated leave requests.']);
        }

        if ($leaveType->balances()->where('used_days', '>', 0)->orWhere('pending_days', '>', 0)->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete leave type that has active or utilized employee leave balances.']);
        }

        $name = $leaveType->name;
        $id = $leaveType->id;
        $leaveType->delete();

        AuditLogger::log(
            action: 'deleted_leave_type',
            module: 'leaves',
            modelType: LeaveType::class,
            modelId: $id
        );

        return back()->with('success', "Leave type '{$name}' deleted successfully.");
    }
}
