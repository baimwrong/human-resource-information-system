<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for PostgreSQL 18.
     */
    public function run(): void
    {
        // 1. System Settings
        Setting::set('company_name', 'Acme Global Technologies Inc.');
        Setting::set('company_email', 'hr@acmeglobal.com');
        Setting::set('company_phone', '+62 21 555 0199');
        Setting::set('company_address', 'Level 24, Cyber Tower One, Jl. HR Rasuna Said, Jakarta 12950');
        Setting::set('currency_symbol', 'IDR');
        Setting::set('timezone', 'Asia/Jakarta');
        Setting::set('work_days_per_month', '22');

        // 2. Shifts
        $shiftRegular = Shift::create([
            'name' => 'Regular Corporate Shift',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_period_minutes' => 15,
            'is_night_shift' => false,
        ]);

        $shiftMorning = Shift::create([
            'name' => 'Early Bird Shift',
            'start_time' => '07:30:00',
            'end_time' => '16:30:00',
            'grace_period_minutes' => 10,
            'is_night_shift' => false,
        ]);

        $shiftNight = Shift::create([
            'name' => 'Night Shift (Support)',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'grace_period_minutes' => 15,
            'is_night_shift' => true,
        ]);

        // 3. Leave Types
        $leaveTypes = [
            ['code' => 'AL', 'name' => 'Annual Leave', 'days_allowed' => 12, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Standard paid annual vacation leave'],
            ['code' => 'SL', 'name' => 'Sick Leave', 'days_allowed' => 14, 'is_paid' => true, 'requires_attachment' => true, 'description' => 'Medical sick leave requiring doctor medical certificate'],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'days_allowed' => 90, 'is_paid' => true, 'requires_attachment' => true, 'description' => 'Maternity leave for expectant mothers'],
            ['code' => 'PL', 'name' => 'Paternity Leave', 'days_allowed' => 5, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Paternity leave for new fathers'],
            ['code' => 'UL', 'name' => 'Unpaid Leave', 'days_allowed' => 30, 'is_paid' => false, 'requires_attachment' => false, 'description' => 'Leave without pay subject to salary deduction'],
            ['code' => 'CL', 'name' => 'Compassionate Leave', 'days_allowed' => 3, 'is_paid' => true, 'requires_attachment' => false, 'description' => 'Leave for bereavement or immediate family emergencies'],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::create($lt);
        }

        // 4. Departments
        $deptEng = Department::create([
            'code' => 'ENG',
            'name' => 'Engineering & Technology',
            'description' => 'Core software engineering, DevOps, QA, and cloud infrastructure.',
            'budget' => 500000000,
            'status' => 'active',
        ]);

        $deptHR = Department::create([
            'code' => 'HRD',
            'name' => 'Human Resources & People Ops',
            'description' => 'Talent acquisition, employee welfare, payroll, and organizational development.',
            'budget' => 120000000,
            'status' => 'active',
        ]);

        $deptFin = Department::create([
            'code' => 'FIN',
            'name' => 'Finance & Accounting',
            'description' => 'Financial planning, accounting, treasury, and tax compliance.',
            'budget' => 180000000,
            'status' => 'active',
        ]);

        $deptMkt = Department::create([
            'code' => 'MKT',
            'name' => 'Sales & Growth',
            'description' => 'Enterprise sales, product marketing, and client relationships.',
            'budget' => 250000000,
            'status' => 'active',
        ]);

        // 5. Designations
        $desEngLead = Designation::create([
            'code' => 'ENG-LEAD',
            'title' => 'Lead Software Architect',
            'department_id' => $deptEng->id,
            'pay_grade' => 'Executive',
            'min_salary' => 25000000,
            'max_salary' => 45000000,
            'description' => 'Technical architectural lead and engineering mentor',
        ]);

        $desSrDev = Designation::create([
            'code' => 'SR-DEV',
            'title' => 'Senior Fullstack Engineer',
            'department_id' => $deptEng->id,
            'pay_grade' => 'Grade A',
            'min_salary' => 15000000,
            'max_salary' => 25000000,
        ]);

        $desJrDev = Designation::create([
            'code' => 'JR-DEV',
            'title' => 'Junior Web Developer',
            'department_id' => $deptEng->id,
            'pay_grade' => 'Grade B',
            'min_salary' => 8000000,
            'max_salary' => 14000000,
        ]);

        $desHRMgr = Designation::create([
            'code' => 'HR-MGR',
            'title' => 'Human Resources Manager',
            'department_id' => $deptHR->id,
            'pay_grade' => 'Executive',
            'min_salary' => 18000000,
            'max_salary' => 30000000,
        ]);

        $desHRExec = Designation::create([
            'code' => 'HR-EXEC',
            'title' => 'People Operations Specialist',
            'department_id' => $deptHR->id,
            'pay_grade' => 'Grade B',
            'min_salary' => 8500000,
            'max_salary' => 15000000,
        ]);

        $desFinMgr = Designation::create([
            'code' => 'FIN-MGR',
            'title' => 'Finance Controller',
            'department_id' => $deptFin->id,
            'pay_grade' => 'Executive',
            'min_salary' => 20000000,
            'max_salary' => 35000000,
        ]);

        $passwordHash = Hash::make('Password123!');

        // 6. Super Admin User
        $userAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@enterprise-hris.local',
            'password' => $passwordHash,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 7. HR Manager User & Employee
        $userHR = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'hr@enterprise-hris.local',
            'password' => $passwordHash,
            'role' => 'hr_manager',
            'is_active' => true,
        ]);

        $empHR = Employee::create([
            'user_id' => $userHR->id,
            'employee_code' => 'EMP-2026-001',
            'first_name' => 'Sarah',
            'last_name' => 'Jenkins',
            'gender' => 'female',
            'date_of_birth' => '1988-04-12',
            'national_id' => '3171018804120001',
            'phone' => '+62 811 1234 567',
            'emergency_contact_name' => 'David Jenkins (Spouse)',
            'emergency_contact_phone' => '+62 811 9876 543',
            'address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
            'department_id' => $deptHR->id,
            'designation_id' => $desHRMgr->id,
            'shift_id' => $shiftRegular->id,
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'join_date' => '2022-01-10',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1270008899123',
            'bank_holder_name' => 'SARAH JENKINS',
            'tax_id' => '71.234.567.8-012.000',
        ]);

        $deptHR->update(['manager_id' => $userHR->id]);

        SalaryStructure::create([
            'employee_id' => $empHR->id,
            'basic_salary' => 22000000,
            'house_allowance' => 3500000,
            'transport_allowance' => 1500000,
            'meal_allowance' => 1200000,
            'tax_deduction' => 1800000,
            'insurance_deduction' => 850000,
            'effective_date' => '2022-01-10',
        ]);

        // 8. Engineering Manager User & Employee
        $userManager = User::create([
            'name' => 'Alexander Hayes',
            'email' => 'manager@enterprise-hris.local',
            'password' => $passwordHash,
            'role' => 'manager',
            'is_active' => true,
        ]);

        $empManager = Employee::create([
            'user_id' => $userManager->id,
            'employee_code' => 'EMP-2026-002',
            'first_name' => 'Alexander',
            'last_name' => 'Hayes',
            'gender' => 'male',
            'date_of_birth' => '1985-09-23',
            'national_id' => '3171018509230002',
            'phone' => '+62 812 2345 678',
            'department_id' => $deptEng->id,
            'designation_id' => $desEngLead->id,
            'shift_id' => $shiftRegular->id,
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'join_date' => '2021-06-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '8271928371',
            'bank_holder_name' => 'ALEXANDER HAYES',
            'tax_id' => '65.432.109.8-011.000',
        ]);

        $deptEng->update(['manager_id' => $userManager->id]);

        SalaryStructure::create([
            'employee_id' => $empManager->id,
            'basic_salary' => 32000000,
            'house_allowance' => 5000000,
            'transport_allowance' => 2000000,
            'meal_allowance' => 1500000,
            'tax_deduction' => 3200000,
            'insurance_deduction' => 1200000,
            'effective_date' => '2021-06-01',
        ]);

        // 9. Staff Employees
        $staffMembers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'employee@enterprise-hris.local',
                'role' => 'employee',
                'code' => 'EMP-2026-003',
                'first' => 'Budi',
                'last' => 'Santoso',
                'gender' => 'male',
                'dob' => '1994-03-15',
                'phone' => '+62 813 3456 789',
                'dept' => $deptEng->id,
                'desig' => $desSrDev->id,
                'manager' => $empManager->id,
                'shift' => $shiftRegular->id,
                'type' => 'permanent',
                'join' => '2023-02-01',
                'basic' => 18000000,
                'house' => 2500000,
                'transport' => 1200000,
                'meal' => 1000000,
                'tax' => 1350000,
                'ins' => 650000,
            ],
            [
                'name' => 'Citra Lestari',
                'email' => 'citra.lestari@enterprise-hris.local',
                'role' => 'employee',
                'code' => 'EMP-2026-004',
                'first' => 'Citra',
                'last' => 'Lestari',
                'gender' => 'female',
                'dob' => '1997-11-28',
                'phone' => '+62 815 4567 890',
                'dept' => $deptEng->id,
                'desig' => $desJrDev->id,
                'manager' => $empManager->id,
                'shift' => $shiftMorning->id,
                'type' => 'probation',
                'join' => '2024-05-15',
                'basic' => 11000000,
                'house' => 1500000,
                'transport' => 800000,
                'meal' => 800000,
                'tax' => 650000,
                'ins' => 420000,
            ],
            [
                'name' => 'Maya Putri',
                'email' => 'maya.putri@enterprise-hris.local',
                'role' => 'employee',
                'code' => 'EMP-2026-005',
                'first' => 'Maya',
                'last' => 'Putri',
                'gender' => 'female',
                'dob' => '1996-07-09',
                'phone' => '+62 817 5678 901',
                'dept' => $deptHR->id,
                'desig' => $desHRExec->id,
                'manager' => $empHR->id,
                'shift' => $shiftRegular->id,
                'type' => 'permanent',
                'join' => '2023-08-01',
                'basic' => 10500000,
                'house' => 1500000,
                'transport' => 800000,
                'meal' => 800000,
                'tax' => 600000,
                'ins' => 400000,
            ],
        ];

        $allEmployees = [$empHR, $empManager];

        foreach ($staffMembers as $staff) {
            $user = User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => $passwordHash,
                'role' => $staff['role'],
                'is_active' => true,
            ]);

            $emp = Employee::create([
                'user_id' => $user->id,
                'employee_code' => $staff['code'],
                'first_name' => $staff['first'],
                'last_name' => $staff['last'],
                'gender' => $staff['gender'],
                'date_of_birth' => $staff['dob'],
                'phone' => $staff['phone'],
                'department_id' => $staff['dept'],
                'designation_id' => $staff['desig'],
                'manager_id' => $staff['manager'],
                'shift_id' => $staff['shift'],
                'employment_type' => $staff['type'],
                'employment_status' => 'active',
                'join_date' => $staff['join'],
                'bank_name' => 'BCA',
                'bank_account_number' => '555' . rand(1000000, 9999999),
                'bank_holder_name' => strtoupper($staff['name']),
            ]);

            SalaryStructure::create([
                'employee_id' => $emp->id,
                'basic_salary' => $staff['basic'],
                'house_allowance' => $staff['house'],
                'transport_allowance' => $staff['transport'],
                'meal_allowance' => $staff['meal'],
                'tax_deduction' => $staff['tax'],
                'insurance_deduction' => $staff['ins'],
                'effective_date' => $staff['join'],
            ]);

            $allEmployees[] = $emp;
        }

        // 10. Seed Leave Balances for 2026
        $allLeaveTypes = LeaveType::all();
        foreach ($allEmployees as $emp) {
            foreach ($allLeaveTypes as $lt) {
                LeaveBalance::create([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $lt->id,
                    'year' => 2026,
                    'allocated_days' => $lt->days_allowed,
                    'used_days' => ($lt->code === 'AL') ? rand(0, 3) : 0,
                    'pending_days' => 0,
                ]);
            }
        }

        // 11. Seed Attendance for past 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($allEmployees as $index => $emp) {
                $isLate = ($index === 2 && $i === 1);
                $clockInTime = $isLate ? '09:25:00' : sprintf('08:%02d:00', rand(40, 58));
                $clockOutTime = sprintf('18:%02d:00', rand(5, 45));

                $clockIn = Carbon::parse($date->toDateString() . ' ' . $clockInTime);
                $clockOut = Carbon::parse($date->toDateString() . ' ' . $clockOutTime);
                $workHours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
                $overtime = max(0, round($workHours - 8.0, 2));

                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'status' => $isLate ? 'late' : 'present',
                    'work_hours' => $workHours,
                    'overtime_hours' => $overtime,
                    'notes' => $isLate ? 'Traffic delay on highway' : 'Regular shift',
                ]);
            }
        }

        // 12. Seed Sample Leave Request
        $annualLeaveType = LeaveType::where('code', 'AL')->first();
        if ($annualLeaveType && count($allEmployees) > 2) {
            LeaveRequest::create([
                'employee_id' => $allEmployees[2]->id,
                'leave_type_id' => $annualLeaveType->id,
                'start_date' => Carbon::tomorrow()->toDateString(),
                'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
                'total_days' => 2,
                'reason' => 'Family vacation trip',
                'status' => 'pending',
            ]);
        }

        // 13. Seed Announcements
        Announcement::create([
            'title' => 'Welcome to the New Enterprise HRIS Portal',
            'content' => "We are excited to launch the unified HRIS platform. Employees can now manage time attendance, review monthly payslips, and apply for annual leave with real-time approvals directly through the portal.\n\nFor any queries or assistance, feel free to reach out to the People Operations team.",
            'priority' => 'urgent',
            'published_at' => now(),
            'is_pinned' => true,
            'created_by' => $userHR->id,
        ]);

        Announcement::create([
            'title' => 'Upcoming Public Holiday Notice - Labor Day',
            'content' => 'The company office will be closed in observance of the upcoming national public holiday. Emergency and critical infrastructure teams will operate under on-call rotations.',
            'priority' => 'info',
            'published_at' => now()->subDays(2),
            'is_pinned' => false,
            'created_by' => $userAdmin->id,
        ]);
    }
}
