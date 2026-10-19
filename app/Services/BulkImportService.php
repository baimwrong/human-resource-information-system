<?php

namespace App\Services;

use App\Models\BulkImportLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BulkImportService
{
    /**
     * Supported import types.
     */
    public const TYPE_EMPLOYEES = 'employees';
    public const TYPE_DEPARTMENTS = 'departments';
    public const TYPE_ATTENDANCE = 'attendance';
    public const TYPE_SALARIES = 'salaries';

    /**
     * Get downloadable CSV template for a given import type.
     */
    public function getTemplateCsv(string $type): string
    {
        return match ($type) {
            self::TYPE_EMPLOYEES => implode("\n", [
                "employee_code,first_name,last_name,email,department_code,designation_code,gender,date_of_birth,phone,join_date,employment_type,basic_salary",
                "EMP-2026-001,John,Doe,john.doe@example.com,ENG,SR-DEV,male,1992-05-15,+62812345678,2024-01-15,permanent,12500000",
                "EMP-2026-002,Jane,Smith,jane.smith@example.com,HRD,HR-EXEC,female,1995-08-20,+62812345679,2024-03-01,permanent,9500000",
            ]),
            self::TYPE_DEPARTMENTS => implode("\n", [
                "code,name,description,budget,status",
                "ENG,Engineering & Technology,Software development and infrastructure,250000000,active",
                "HRD,Human Resources,Talent acquisition and people operations,80000000,active",
                "FIN,Finance & Accounting,Financial planning and treasury,120000000,active",
            ]),
            self::TYPE_ATTENDANCE => implode("\n", [
                "employee_code,date,clock_in,clock_out,status,notes",
                "EMP-2026-001,2026-10-01,08:55:00,18:05:00,present,Regular shift",
                "EMP-2026-002,2026-10-01,09:20:00,18:00:00,late,Traffic congestion",
            ]),
            self::TYPE_SALARIES => implode("\n", [
                "employee_code,basic_salary,house_allowance,transport_allowance,meal_allowance,tax_deduction,insurance_deduction,effective_date",
                "EMP-2026-001,12500000,2000000,1000000,800000,750000,500000,2026-01-01",
                "EMP-2026-002,9500000,1500000,800000,600000,450000,380000,2026-01-01",
            ]),
            default => throw new \InvalidArgumentException("Unknown import type: {$type}"),
        };
    }

    /**
     * Process bulk import from CSV file using memory-efficient streaming and cached lookups.
     */
    public function processImport(
        string $type,
        string $filePath,
        string $fileName,
        int $userId,
        bool $rollbackOnError = false
    ): array {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \RuntimeException("CSV file cannot be read: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open CSV file: {$filePath}");
        }

        try {
            // Read header row
            $rawHeaders = fgetcsv($handle);
            if ($rawHeaders === false || empty(array_filter($rawHeaders))) {
                throw new \RuntimeException("CSV file is empty or missing header row.");
            }

            // Strip UTF-8 BOM if present on the first header
            $rawHeaders[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rawHeaders[0]);

            $headers = array_map(function ($h) {
                return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', strtolower(trim((string) $h))));
            }, $rawHeaders);

            // Pre-cache lookup maps to avoid N+1 query storms during bulk iteration
            $context = [
                'departments' => [],
                'designations' => [],
                'leave_types' => null,
                'employees' => [],
            ];

            if ($type === self::TYPE_EMPLOYEES) {
                foreach (Department::all(['id', 'code', 'name']) as $dept) {
                    $context['departments'][strtoupper($dept->code)] = $dept->id;
                    $context['departments'][strtoupper($dept->name)] = $dept->id;
                }
                foreach (Designation::all(['id', 'code', 'title']) as $desig) {
                    $context['designations'][strtoupper($desig->code)] = $desig->id;
                    $context['designations'][strtoupper($desig->title)] = $desig->id;
                }
                $context['leave_types'] = LeaveType::all();
            }

            $totalRows = 0;
            $successfulRows = 0;
            $failedRows = 0;
            $errorDetails = [];

            $streamProcessor = function () use (
                $handle,
                $type,
                $headers,
                $rollbackOnError,
                &$context,
                &$totalRows,
                &$successfulRows,
                &$failedRows,
                &$errorDetails
            ) {
                $rowNumber = 1; // Header was row 1

                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    // Skip empty / blank lines
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $totalRows++;

                    if (count($row) !== count($headers)) {
                        $failedRows++;
                        $errorDetails[] = [
                            'row' => $rowNumber,
                            'identifier' => 'Row ' . $rowNumber,
                            'error' => 'Column count mismatch. Expected ' . count($headers) . ' columns, found ' . count($row),
                        ];
                        if ($rollbackOnError) {
                            throw new \Exception("Row {$rowNumber}: Column count mismatch.");
                        }
                        continue;
                    }

                    $data = array_combine($headers, array_map('trim', $row));

                    try {
                        match ($type) {
                            self::TYPE_EMPLOYEES => $this->importEmployeeRow($data, $rowNumber, $context),
                            self::TYPE_DEPARTMENTS => $this->importDepartmentRow($data, $rowNumber),
                            self::TYPE_ATTENDANCE => $this->importAttendanceRow($data, $rowNumber, $context),
                            self::TYPE_SALARIES => $this->importSalaryRow($data, $rowNumber, $context),
                            default => throw new \InvalidArgumentException("Invalid import type: {$type}"),
                        };

                        $successfulRows++;
                    } catch (\Throwable $e) {
                        $failedRows++;
                        $errorDetails[] = [
                            'row' => $rowNumber,
                            'identifier' => $data['employee_code'] ?? ($data['code'] ?? 'Row ' . $rowNumber),
                            'error' => $e->getMessage(),
                        ];

                        if ($rollbackOnError) {
                            throw $e;
                        }
                    }
                }

                if ($totalRows === 0) {
                    throw new \RuntimeException("CSV file does not contain any data rows.");
                }
            };

            if ($rollbackOnError) {
                try {
                    DB::transaction($streamProcessor);
                } catch (\Throwable $e) {
                    $successfulRows = 0;
                }
            } else {
                $streamProcessor();
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $status = ($failedRows === 0) ? 'completed' : (($successfulRows > 0) ? 'partial' : 'failed');

        // Persist import log
        $log = BulkImportLog::create([
            'user_id' => $userId,
            'import_type' => $type,
            'file_name' => $fileName,
            'total_rows' => $totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'status' => $status,
            'error_details' => $errorDetails,
        ]);

        AuditLogger::log(
            action: 'bulk_import_' . $type,
            module: 'bulk_import',
            modelType: BulkImportLog::class,
            modelId: $log->id,
            changes: [
                'type' => $type,
                'file' => $fileName,
                'success' => $successfulRows,
                'failed' => $failedRows,
            ]
        );

        return [
            'log_id' => $log->id,
            'status' => $status,
            'total' => $totalRows,
            'successful' => $successfulRows,
            'failed' => $failedRows,
            'errors' => $errorDetails,
        ];
    }

    /**
     * Import a single employee row with pre-cached lookups.
     */
    protected function importEmployeeRow(array $data, int $rowNumber, array &$context = []): void
    {
        if (empty($data['employee_code']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            throw new \Exception("Missing required fields: employee_code, first_name, last_name, or email.");
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email format: {$data['email']}");
        }

        $employeeCode = strtoupper(trim($data['employee_code']));

        // Check duplicates
        if (Employee::where('employee_code', $employeeCode)->exists()) {
            throw new \Exception("Employee code '{$employeeCode}' already exists.");
        }

        if (User::where('email', strtolower($data['email']))->exists()) {
            throw new \Exception("Email '{$data['email']}' is already registered.");
        }

        // Resolve Department via cached map or fallback
        $departmentId = null;
        if (! empty($data['department_code'])) {
            $deptKey = strtoupper(trim($data['department_code']));
            if (isset($context['departments'][$deptKey])) {
                $departmentId = $context['departments'][$deptKey];
            } else {
                $department = Department::where('code', $deptKey)->orWhere('name', $data['department_code'])->first();
                if ($department) {
                    $departmentId = $department->id;
                    $context['departments'][$deptKey] = $departmentId;
                }
            }
        }

        // Resolve Designation via cached map or fallback
        $designationId = null;
        if (! empty($data['designation_code'])) {
            $desigKey = strtoupper(trim($data['designation_code']));
            if (isset($context['designations'][$desigKey])) {
                $designationId = $context['designations'][$desigKey];
            } else {
                $designation = Designation::where('code', $desigKey)->orWhere('title', $data['designation_code'])->first();
                if ($designation) {
                    $designationId = $designation->id;
                    $context['designations'][$desigKey] = $designationId;
                }
            }
        }

        // Create portal User account
        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => strtolower($data['email']),
            'password' => Hash::make('Password123!'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Create Employee
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => $employeeCode,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => in_array(strtolower($data['gender'] ?? ''), ['male', 'female', 'other']) ? strtolower($data['gender']) : 'other',
            'date_of_birth' => ! empty($data['date_of_birth']) ? Carbon::parse($data['date_of_birth'])->toDateString() : null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'employment_type' => in_array(strtolower($data['employment_type'] ?? ''), ['permanent', 'probation', 'contract', 'intern']) ? strtolower($data['employment_type']) : 'permanent',
            'employment_status' => 'active',
            'join_date' => ! empty($data['join_date']) ? Carbon::parse($data['join_date'])->toDateString() : Carbon::today()->toDateString(),
        ]);

        // Cache for subsequent references
        $context['employees'][$employeeCode] = $employee;

        // Initialize SalaryStructure if basic_salary is provided
        if (isset($data['basic_salary']) && is_numeric($data['basic_salary']) && (float)$data['basic_salary'] > 0) {
            SalaryStructure::create([
                'employee_id' => $employee->id,
                'basic_salary' => (float)$data['basic_salary'],
                'house_allowance' => 0.00,
                'transport_allowance' => 0.00,
                'meal_allowance' => 0.00,
                'tax_deduction' => 0.00,
                'insurance_deduction' => 0.00,
                'effective_date' => $employee->join_date,
            ]);
        }

        // Initialize annual leave balances for current year using cached LeaveType collection
        $currentYear = (int)date('Y');
        $leaveTypes = $context['leave_types'] ?? LeaveType::all();
        foreach ($leaveTypes as $type) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                ],
                [
                    'allocated_days' => $type->days_allowed,
                    'used_days' => 0,
                    'pending_days' => 0,
                ]
            );
        }
    }

    /**
     * Import a single department row.
     */
    protected function importDepartmentRow(array $data, int $rowNumber): void
    {
        if (empty($data['code']) || empty($data['name'])) {
            throw new \Exception("Missing required fields: code or name.");
        }

        Department::updateOrCreate(
            ['code' => strtoupper($data['code'])],
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'budget' => isset($data['budget']) && is_numeric($data['budget']) ? (float)$data['budget'] : 0.00,
                'status' => in_array(strtolower($data['status'] ?? ''), ['active', 'inactive']) ? strtolower($data['status']) : 'active',
            ]
        );
    }

    /**
     * Import a single attendance record with cached employee lookup.
     */
    protected function importAttendanceRow(array $data, int $rowNumber, array &$context = []): void
    {
        if (empty($data['employee_code']) || empty($data['date'])) {
            throw new \Exception("Missing employee_code or date.");
        }

        $code = strtoupper(trim($data['employee_code']));
        if (isset($context['employees'][$code])) {
            $employee = $context['employees'][$code];
        } else {
            $employee = Employee::where('employee_code', $code)->first();
            if ($employee) {
                $context['employees'][$code] = $employee;
            }
        }

        if (! $employee) {
            throw new \Exception("Employee with code '{$data['employee_code']}' not found.");
        }

        $date = Carbon::parse($data['date'])->toDateString();
        $clockIn = ! empty($data['clock_in']) ? Carbon::parse($date . ' ' . $data['clock_in']) : null;
        $clockOut = ! empty($data['clock_out']) ? Carbon::parse($date . ' ' . $data['clock_out']) : null;

        $workHours = 0.00;
        $overtimeHours = 0.00;
        if ($clockIn && $clockOut) {
            $minutes = max(0, $clockOut->diffInMinutes($clockIn));
            $workHours = round($minutes / 60, 2);
            $overtimeHours = max(0, round($workHours - 8.0, 2));
        }

        Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => in_array(strtolower($data['status'] ?? ''), ['present', 'late', 'half_day', 'absent', 'on_leave', 'holiday']) ? strtolower($data['status']) : 'present',
                'work_hours' => $workHours,
                'overtime_hours' => $overtimeHours,
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Import a single salary structure record with cached employee lookup.
     */
    protected function importSalaryRow(array $data, int $rowNumber, array &$context = []): void
    {
        if (empty($data['employee_code']) || ! isset($data['basic_salary'])) {
            throw new \Exception("Missing employee_code or basic_salary.");
        }

        $code = strtoupper(trim($data['employee_code']));
        if (isset($context['employees'][$code])) {
            $employee = $context['employees'][$code];
        } else {
            $employee = Employee::where('employee_code', $code)->first();
            if ($employee) {
                $context['employees'][$code] = $employee;
            }
        }

        if (! $employee) {
            throw new \Exception("Employee with code '{$data['employee_code']}' not found.");
        }

        SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'basic_salary' => (float)$data['basic_salary'],
                'house_allowance' => isset($data['house_allowance']) && is_numeric($data['house_allowance']) ? (float)$data['house_allowance'] : 0.00,
                'transport_allowance' => isset($data['transport_allowance']) && is_numeric($data['transport_allowance']) ? (float)$data['transport_allowance'] : 0.00,
                'meal_allowance' => isset($data['meal_allowance']) && is_numeric($data['meal_allowance']) ? (float)$data['meal_allowance'] : 0.00,
                'tax_deduction' => isset($data['tax_deduction']) && is_numeric($data['tax_deduction']) ? (float)$data['tax_deduction'] : 0.00,
                'insurance_deduction' => isset($data['insurance_deduction']) && is_numeric($data['insurance_deduction']) ? (float)$data['insurance_deduction'] : 0.00,
                'effective_date' => ! empty($data['effective_date']) ? Carbon::parse($data['effective_date'])->toDateString() : Carbon::today()->toDateString(),
            ]
        );
    }
}
