<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'designation', 'user', 'manager']);

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $term = '%' . strtolower($search) . '%';
                $q->whereRaw('LOWER(employee_code) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(first_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->whereRaw('LOWER(email) LIKE ?', [$term]);
                  });
            });
        }

        // Department filter
        if ($deptId = $request->input('department_id')) {
            $query->where('department_id', $deptId);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('employment_status', $status);
        }

        $employees = $query->orderBy('first_name')->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::orderBy('title')->get();
        $managers = Employee::where('employment_status', 'active')->orderBy('first_name')->get();
        $shifts = Shift::orderBy('name')->get();

        // Generate next employee code suggestion
        $count = Employee::withTrashed()->count() + 1;
        $nextCode = sprintf('EMP-%04d-%03d', date('Y'), $count);

        return view('employees.create', compact('departments', 'designations', 'managers', 'shifts', 'nextCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', 'unique:employees,employee_code'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'hr_manager', 'manager', 'employee'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'employment_type' => ['required', Rule::in(['permanent', 'probation', 'contract', 'intern'])],
            'employment_status' => ['required', Rule::in(['active', 'on_leave', 'resigned', 'terminated'])],
            'join_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_holder_name' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            // Salary components
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'house_allowance' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'tax_deduction' => ['nullable', 'numeric', 'min:0'],
            'insurance_deduction' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee = DB::transaction(function () use ($validated) {
            // 1. Create User
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password'] ?? 'Password123!'),
                'role' => $validated['role'],
                'is_active' => $validated['employment_status'] === 'active',
            ]);

            // 2. Create Employee
            $employeeData = $validated;
            $employeeData['user_id'] = $user->id;
            unset($employeeData['email'], $employeeData['password'], $employeeData['role']);
            unset($employeeData['basic_salary'], $employeeData['house_allowance'], $employeeData['transport_allowance'], $employeeData['meal_allowance'], $employeeData['tax_deduction'], $employeeData['insurance_deduction']);

            $employee = Employee::create($employeeData);

            // 3. Create SalaryStructure
            SalaryStructure::create([
                'employee_id' => $employee->id,
                'basic_salary' => $validated['basic_salary'],
                'house_allowance' => $validated['house_allowance'] ?? 0.00,
                'transport_allowance' => $validated['transport_allowance'] ?? 0.00,
                'meal_allowance' => $validated['meal_allowance'] ?? 0.00,
                'tax_deduction' => $validated['tax_deduction'] ?? 0.00,
                'insurance_deduction' => $validated['insurance_deduction'] ?? 0.00,
                'effective_date' => $validated['join_date'],
            ]);

            // 4. Initialize annual leave balances
            $currentYear = (int)date('Y');
            $leaveTypes = LeaveType::all();
            foreach ($leaveTypes as $type) {
                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                    'allocated_days' => $type->days_allowed,
                    'used_days' => 0,
                    'pending_days' => 0,
                ]);
            }

            return $employee;
        });

        AuditLogger::log(
            action: 'created_employee',
            module: 'employees',
            modelType: Employee::class,
            modelId: $employee->id,
            changes: ['employee_code' => $employee->employee_code, 'name' => $employee->full_name]
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Employee profile created successfully.');
    }

    public function show(Employee $employee)
    {
        $user = Auth::user();
        $isSelf = $user->employee && $user->employee->id === $employee->id;

        if (! $user->isManager() && ! $isSelf) {
            abort(403, 'Unauthorized access to employee profile.');
        }

        $canViewFinancials = $user->isHR() || $isSelf;

        $relations = [
            'user',
            'department',
            'designation',
            'manager',
            'shift',
            'subordinates',
            'leaveBalances.leaveType',
            'leaveRequests.leaveType',
            'attendances' => function ($q) {
                $q->orderByDesc('date')->take(30);
            },
            'performanceReviews.reviewer',
        ];

        if ($canViewFinancials) {
            $relations[] = 'salaryStructure';
            $relations['payslips'] = function ($q) {
                $q->orderByDesc('year')->orderByDesc('month')->take(12);
            };
        }

        $employee->load($relations);

        return view('employees.show', compact('employee', 'canViewFinancials'));
    }

    public function edit(Employee $employee)
    {
        $employee->load(['user', 'salaryStructure']);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::orderBy('title')->get();
        $managers = Employee::where('employment_status', 'active')->where('id', '!=', $employee->id)->orderBy('first_name')->get();
        $shifts = Shift::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'departments', 'designations', 'managers', 'shifts'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'role' => ['required', Rule::in(['admin', 'hr_manager', 'manager', 'employee'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'employment_type' => ['required', Rule::in(['permanent', 'probation', 'contract', 'intern'])],
            'employment_status' => ['required', Rule::in(['active', 'on_leave', 'resigned', 'terminated'])],
            'join_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_holder_name' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            // Salary components
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'house_allowance' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'tax_deduction' => ['nullable', 'numeric', 'min:0'],
            'insurance_deduction' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $employee) {
            // Update User
            if ($employee->user) {
                $employee->user->update([
                    'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                    'email' => strtolower($validated['email']),
                    'role' => $validated['role'],
                    'is_active' => $validated['employment_status'] === 'active',
                ]);
            }

            // Update Employee
            $employeeData = $validated;
            unset($employeeData['email'], $employeeData['role']);
            unset($employeeData['basic_salary'], $employeeData['house_allowance'], $employeeData['transport_allowance'], $employeeData['meal_allowance'], $employeeData['tax_deduction'], $employeeData['insurance_deduction']);

            $employee->update($employeeData);

            // Update SalaryStructure
            SalaryStructure::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'basic_salary' => $validated['basic_salary'],
                    'house_allowance' => $validated['house_allowance'] ?? 0.00,
                    'transport_allowance' => $validated['transport_allowance'] ?? 0.00,
                    'meal_allowance' => $validated['meal_allowance'] ?? 0.00,
                    'tax_deduction' => $validated['tax_deduction'] ?? 0.00,
                    'insurance_deduction' => $validated['insurance_deduction'] ?? 0.00,
                    'effective_date' => $validated['join_date'],
                ]
            );
        });

        AuditLogger::log(
            action: 'updated_employee',
            module: 'employees',
            modelType: Employee::class,
            modelId: $employee->id,
            changes: ['employee_code' => $employee->employee_code]
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }
            $employee->delete();
        });

        AuditLogger::log(
            action: 'deleted_employee',
            module: 'employees',
            modelType: Employee::class,
            modelId: $employee->id
        );

        return redirect()->route('employees.index')->with('success', 'Employee has been removed.');
    }

    public function exportCsv()
    {
        $employees = Employee::with(['department', 'designation', 'user', 'salaryStructure'])
            ->orderBy('employee_code')
            ->get();

        if (! Auth::user()->isHR()) {
            abort(403, 'Unauthorized. Only HR and Administrators can export employee records.');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees_export_' . date('Ymd_His') . '.csv"',
        ];

        $sanitizeCsv = function ($val) {
            $val = (string)($val ?? '');
            if (strlen($val) > 0 && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"])) {
                return "'" . $val;
            }
            return $val;
        };

        $callback = function () use ($employees, $sanitizeCsv) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee Code',
                'Name',
                'Email',
                'Department',
                'Designation',
                'Gender',
                'Phone',
                'Status',
                'Type',
                'Join Date',
                'Basic Salary',
            ]);

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $sanitizeCsv($emp->employee_code),
                    $sanitizeCsv($emp->full_name),
                    $sanitizeCsv($emp->user?->email ?? ''),
                    $sanitizeCsv($emp->department?->name ?? ''),
                    $sanitizeCsv($emp->designation?->title ?? ''),
                    $sanitizeCsv(ucfirst($emp->gender)),
                    $sanitizeCsv($emp->phone ?? ''),
                    $sanitizeCsv(ucfirst($emp->employment_status)),
                    $sanitizeCsv(ucfirst($emp->employment_type)),
                    $sanitizeCsv($emp->join_date ? $emp->join_date->format('Y-m-d') : ''),
                    $sanitizeCsv($emp->salaryStructure?->basic_salary ?? 0),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
