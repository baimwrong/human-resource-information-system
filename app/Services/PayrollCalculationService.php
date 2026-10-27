<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollCalculationService
{
    protected StatutoryCalculationService $statutoryService;

    public function __construct(?StatutoryCalculationService $statutoryService = null)
    {
        $this->statutoryService = $statutoryService ?? new StatutoryCalculationService();
    }

    /**
     * Generate payroll batch and payslips for a given month and year.
     */
    public function generateMonthlyPayroll(int $month, int $year, int $creatorId): PayrollBatch
    {
        return DB::transaction(function () use ($month, $year, $creatorId) {
            // Check if batch already exists
            $batchNumber = sprintf('PAY-%04d-%02d', $year, $month);
            $batch = PayrollBatch::where('month', $month)->where('year', $year)->first();

            if ($batch && $batch->status === 'paid') {
                throw new \RuntimeException("Payroll batch for {$batch->period_name} is already finalized and marked as PAID. It cannot be re-generated.");
            }

            if (! $batch) {
                $batch = PayrollBatch::create([
                    'batch_number' => $batchNumber,
                    'month' => $month,
                    'year' => $year,
                    'status' => 'draft',
                    'created_by' => $creatorId,
                ]);
            }

            // Remove existing unpaid payslips for re-generation
            Payslip::where('payroll_batch_id', $batch->id)->where('payment_status', 'unpaid')->delete();

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Include active employees and employees terminated during this month cycle
            $employees = Employee::with(['salaryStructure', 'department', 'designation'])
                ->where(function ($q) use ($startDate) {
                    $q->where('employment_status', 'active')
                      ->orWhere(function ($sub) use ($startDate) {
                          $sub->whereIn('employment_status', ['resigned', 'terminated'])
                              ->where('termination_date', '>=', $startDate->toDateString());
                      });
                })
                ->where('join_date', '<=', $endDate->toDateString())
                ->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $count = 0;

            // Configurable work days and localization from settings
            $workDays = max(1, (int)Setting::get('work_days_per_month', 22));
            $companyName = Setting::get('company_name', 'Acme Global Technologies Inc.');
            $companyAddress = Setting::get('company_address', 'Level 24, Cyber Tower One, Jakarta');
            $currencySymbol = Setting::get('currency_symbol', 'IDR');

            // Count actual calendar working days in this month (Mon-Fri) for accurate proration
            $monthPeriod = CarbonPeriod::create($startDate, $endDate);
            $actualMonthWorkDays = 0;
            foreach ($monthPeriod as $day) {
                if (! $day->isWeekend()) {
                    $actualMonthWorkDays++;
                }
            }
            $monthWorkDays = max(1, $actualMonthWorkDays);

            foreach ($employees as $employee) {
                $salary = $employee->salaryStructure;
                $fullBasic = $salary ? (float)$salary->basic_salary : 0.00;

                if ($fullBasic <= 0) {
                    continue; // Skip employee without configured salary
                }

                // Mid-month hire and termination proration calculation
                $joinDate = $employee->join_date ? Carbon::parse($employee->join_date) : $startDate;
                $termDate = $employee->termination_date ? Carbon::parse($employee->termination_date) : $endDate;

                $effectiveStart = $joinDate->greaterThan($startDate) ? $joinDate : $startDate;
                $effectiveEnd = $termDate->lessThan($endDate) ? $termDate : $endDate;

                $prorated = false;
                $activeWorkDays = $monthWorkDays;

                if ($effectiveStart->greaterThan($startDate) || $effectiveEnd->lessThan($endDate)) {
                    $activeWorkDays = 0;
                    if ($effectiveStart->lessThanOrEqualTo($effectiveEnd)) {
                        $activePeriod = CarbonPeriod::create($effectiveStart, $effectiveEnd);
                        foreach ($activePeriod as $d) {
                            if (! $d->isWeekend()) {
                                $activeWorkDays++;
                            }
                        }
                    }
                    $prorated = true;
                }

                $prorationRatio = min(1.0, max(0.0, $activeWorkDays / $monthWorkDays));
                $basic = round($fullBasic * $prorationRatio, 2);

                $dailyRate = round($fullBasic / $workDays, 2);
                $hourlyRate = round($fullBasic / ($workDays * 8), 2);

                $fullHouse = $salary ? (float)$salary->house_allowance : 0.00;
                $fullTransport = $salary ? (float)$salary->transport_allowance : 0.00;
                $fullMeal = $salary ? (float)$salary->meal_allowance : 0.00;
                $fullOther = $salary ? (float)$salary->other_allowances : 0.00;

                $house = round($fullHouse * $prorationRatio, 2);
                $transport = round($fullTransport * $prorationRatio, 2);
                $meal = round($fullMeal * $prorationRatio, 2);
                $otherAllowances = round($fullOther * $prorationRatio, 2);

                // Overtime pay calculation from Attendance logs (1.5x hourly rate)
                $overtimeHours = (float)Attendance::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->sum('overtime_hours');
                $overtimePay = round($overtimeHours * $hourlyRate * 1.5, 2);

                $totalAllowance = $house + $transport + $meal + $otherAllowances + $overtimePay;

                // Statutory tax and social security deductions
                $grossBeforeDeductions = $basic + $totalAllowance;
                $statutory = $this->statutoryService->computeDeductions(
                    $employee,
                    $salary,
                    $grossBeforeDeductions
                );

                $tax = $statutory['tax'];
                $insurance = $statutory['insurance'];
                $otherDeductions = $salary ? (float)$salary->other_deductions : 0.00;

                // Accurate Unpaid Leaves calculation strictly within this month
                $unpaidLeaveRequests = LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereHas('leaveType', function ($q) {
                        $q->where('is_paid', false);
                    })
                    ->where('start_date', '<=', $endDate->toDateString())
                    ->where('end_date', '>=', $startDate->toDateString())
                    ->get();

                $unpaidLeaveDays = 0;
                foreach ($unpaidLeaveRequests as $req) {
                    $lStart = Carbon::parse($req->start_date)->max($startDate);
                    $lEnd = Carbon::parse($req->end_date)->min($endDate);
                    $p = CarbonPeriod::create($lStart, $lEnd);
                    foreach ($p as $d) {
                        if (! $d->isWeekend()) {
                            $unpaidLeaveDays++;
                        }
                    }
                }
                $unpaidLeaveDeduction = round((float)$unpaidLeaveDays * $dailyRate, 2);

                // Unexcused Absenteeism deduction from Attendance
                $absentDays = Attendance::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->where('status', 'absent')
                    ->count();
                $absentDeduction = round((float)$absentDays * $dailyRate, 2);

                $totalDeduction = $tax + $insurance + $otherDeductions + $unpaidLeaveDeduction + $absentDeduction;
                $netSalary = max(0, ($basic + $totalAllowance) - $totalDeduction);

                $basicLabel = $prorated ? "Basic Salary (Prorated: {$activeWorkDays}/{$monthWorkDays} days)" : 'Basic Salary';
                $earningsList = [
                    ['label' => $basicLabel, 'amount' => $basic],
                    ['label' => 'Housing Allowance', 'amount' => $house],
                    ['label' => 'Transport Allowance', 'amount' => $transport],
                    ['label' => 'Meal Allowance', 'amount' => $meal],
                    ['label' => 'Other Allowance', 'amount' => $otherAllowances],
                ];
                if ($overtimePay > 0) {
                    $earningsList[] = ['label' => "Overtime Compensation ({$overtimeHours} hrs @ 1.5x)", 'amount' => $overtimePay];
                }

                $deductionsList = [
                    ['label' => 'Income Tax (PPh 21)', 'amount' => $tax],
                    ['label' => 'Health & Social Insurance', 'amount' => $insurance],
                ];
                if ($unpaidLeaveDeduction > 0) {
                    $deductionsList[] = ['label' => "Unpaid Leave ({$unpaidLeaveDays} days)", 'amount' => $unpaidLeaveDeduction];
                }
                if ($absentDeduction > 0) {
                    $deductionsList[] = ['label' => "Unexcused Absence ({$absentDays} days)", 'amount' => $absentDeduction];
                }
                if ($otherDeductions > 0) {
                    $deductionsList[] = ['label' => 'Other Deductions', 'amount' => $otherDeductions];
                }

                $breakdown = [
                    'earnings' => $earningsList,
                    'deductions' => $deductionsList,
                    'metadata' => [
                        'company_name' => $companyName,
                        'company_address' => $companyAddress,
                        'currency_symbol' => $currencySymbol,
                        'department' => $employee->department?->name ?? 'N/A',
                        'designation' => $employee->designation?->title ?? 'N/A',
                        'bank_name' => $employee->bank_name ?? 'N/A',
                        'bank_account' => $employee->bank_account_number ?? 'N/A',
                    ]
                ];

                $slipNumber = sprintf('PS-%04d%02d-%04d', $year, $month, $employee->id);

                Payslip::updateOrCreate(
                    [
                        'payroll_batch_id' => $batch->id,
                        'employee_id' => $employee->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'slip_number' => $slipNumber,
                        'basic_salary' => $basic,
                        'total_allowance' => $totalAllowance,
                        'total_deduction' => $totalDeduction,
                        'net_salary' => $netSalary,
                        'breakdown' => $breakdown,
                        'payment_status' => 'unpaid',
                    ]
                );

                $totalGross += ($basic + $totalAllowance);
                $totalDeductions += $totalDeduction;
                $totalNet += $netSalary;
                $count++;
            }

            $batch->update([
                'total_employees' => $count,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'status' => 'processed',
            ]);

            return $batch;
        });
    }
}
