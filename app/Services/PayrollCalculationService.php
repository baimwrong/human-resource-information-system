<?php

namespace App\Services;

use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollCalculationService
{
    /**
     * Generate payroll batch and payslips for a given month and year.
     */
    public function generateMonthlyPayroll(int $month, int $year, int $creatorId): PayrollBatch
    {
        return DB::transaction(function () use ($month, $year, $creatorId) {
            // Check if batch already exists
            $batchNumber = sprintf('PAY-%04d-%02d', $year, $month);
            $batch = PayrollBatch::where('month', $month)->where('year', $year)->first();

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

            $employees = Employee::with(['salaryStructure', 'department', 'designation'])
                ->where('employment_status', 'active')
                ->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $count = 0;

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            foreach ($employees as $employee) {
                $salary = $employee->salaryStructure;
                $basic = $salary ? (float)$salary->basic_salary : 0.00;

                if ($basic <= 0) {
                    continue; // Skip employee without configured salary
                }

                $house = $salary ? (float)$salary->house_allowance : 0.00;
                $transport = $salary ? (float)$salary->transport_allowance : 0.00;
                $meal = $salary ? (float)$salary->meal_allowance : 0.00;
                $otherAllowances = $salary ? (float)$salary->other_allowances : 0.00;
                $totalAllowance = $house + $transport + $meal + $otherAllowances;

                // Deductions
                $tax = $salary ? (float)$salary->tax_deduction : 0.00;
                $insurance = $salary ? (float)$salary->insurance_deduction : 0.00;
                $otherDeductions = $salary ? (float)$salary->other_deductions : 0.00;

                // Check unpaid leaves in this month
                $unpaidLeaves = LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereHas('leaveType', function ($q) {
                        $q->where('is_paid', false);
                    })
                    ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->sum('total_days');

                $dailyRate = $basic > 0 ? round($basic / 22, 2) : 0;
                $unpaidLeaveDeduction = round((float)$unpaidLeaves * $dailyRate, 2);

                $totalDeduction = $tax + $insurance + $otherDeductions + $unpaidLeaveDeduction;
                $netSalary = max(0, ($basic + $totalAllowance) - $totalDeduction);

                $breakdown = [
                    'earnings' => [
                        ['label' => 'Basic Salary', 'amount' => $basic],
                        ['label' => 'Housing Allowance', 'amount' => $house],
                        ['label' => 'Transport Allowance', 'amount' => $transport],
                        ['label' => 'Meal Allowance', 'amount' => $meal],
                        ['label' => 'Other Allowance', 'amount' => $otherAllowances],
                    ],
                    'deductions' => [
                        ['label' => 'Income Tax (PPh 21)', 'amount' => $tax],
                        ['label' => 'Health & Social Insurance', 'amount' => $insurance],
                        ['label' => 'Unpaid Leave Penalty (' . $unpaidLeaves . ' days)', 'amount' => $unpaidLeaveDeduction],
                        ['label' => 'Other Deductions', 'amount' => $otherDeductions],
                    ],
                    'metadata' => [
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
