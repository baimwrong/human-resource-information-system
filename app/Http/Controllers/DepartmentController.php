<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['manager', 'employees'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $managers = User::whereIn('role', ['admin', 'hr_manager', 'manager'])->orderBy('name')->get();

        return view('departments.index', compact('departments', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $department = Department::create($validated);

        AuditLogger::log(
            action: 'created_department',
            module: 'departments',
            modelType: Department::class,
            modelId: $department->id,
            changes: $validated
        );

        return back()->with('success', "Department '{$department->name}' created successfully.");
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $department->update($validated);

        AuditLogger::log(
            action: 'updated_department',
            module: 'departments',
            modelType: Department::class,
            modelId: $department->id,
            changes: $validated
        );

        return back()->with('success', "Department '{$department->name}' updated successfully.");
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete department with active employees. Reassign employees first.']);
        }

        $name = $department->name;
        $id = $department->id;
        $department->delete();

        AuditLogger::log(
            action: 'deleted_department',
            module: 'departments',
            modelType: Department::class,
            modelId: $id
        );

        return back()->with('success', "Department '{$name}' deleted successfully.");
    }
}
