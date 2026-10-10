<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index()
    {
        $designations = Designation::with(['department'])
            ->withCount('employees')
            ->orderBy('title')
            ->get();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('designations.index', compact('designations', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:designations,code'],
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'pay_grade' => ['required', 'string', 'max:50'],
            'min_salary' => ['required', 'numeric', 'min:0'],
            'max_salary' => ['required', 'numeric', 'gte:min_salary'],
            'description' => ['nullable', 'string'],
        ]);

        $designation = Designation::create($validated);

        AuditLogger::log(
            action: 'created_designation',
            module: 'designations',
            modelType: Designation::class,
            modelId: $designation->id,
            changes: $validated
        );

        return back()->with('success', "Designation '{$designation->title}' created successfully.");
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('designations', 'code')->ignore($designation->id)],
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'pay_grade' => ['required', 'string', 'max:50'],
            'min_salary' => ['required', 'numeric', 'min:0'],
            'max_salary' => ['required', 'numeric', 'gte:min_salary'],
            'description' => ['nullable', 'string'],
        ]);

        $designation->update($validated);

        AuditLogger::log(
            action: 'updated_designation',
            module: 'designations',
            modelType: Designation::class,
            modelId: $designation->id,
            changes: $validated
        );

        return back()->with('success', "Designation '{$designation->title}' updated successfully.");
    }

    public function destroy(Designation $designation)
    {
        if ($designation->employees()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete designation assigned to employees.']);
        }

        $title = $designation->title;
        $id = $designation->id;
        $designation->delete();

        AuditLogger::log(
            action: 'deleted_designation',
            module: 'designations',
            modelType: Designation::class,
            modelId: $id
        );

        return back()->with('success', "Designation '{$title}' deleted successfully.");
    }
}
