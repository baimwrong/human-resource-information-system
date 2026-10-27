<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryStructure;

class StatutoryCalculationService
{
    /**
     * Compute statutory tax (income tax withholding) and social security deductions.
     * If explicit fixed amounts are provided on the salary structure, they take precedence
     * unless automatic statutory mode is activated.
     */
    public function computeDeductions(
        Employee $employee,
        ?SalaryStructure $salary,
        float $grossSalary,
        bool $useStatutoryFormulas = false
    ): array {
        // Fallback to configured structure values if statutory formula is disabled
        if (! $useStatutoryFormulas && $salary) {
            return [
                'tax' => (float)$salary->tax_deduction,
                'insurance' => (float)$salary->insurance_deduction,
                'breakdown' => [
                    'income_tax' => (float)$salary->tax_deduction,
                    'social_security' => (float)$salary->insurance_deduction,
                    'method' => 'configured_fixed',
                ],
            ];
        }

        // Automatic Statutory Formulas (e.g., standard progressive tax + social security contributions)
        // 1. Social Security (Health: 1%, Pension: 2% of basic or gross up to cap)
        $pensionContribution = round($grossSalary * 0.02, 2);
        $healthContribution = round($grossSalary * 0.01, 2);
        $totalInsurance = $pensionContribution + $healthContribution;

        // 2. Taxable Income estimation
        $taxableIncome = max(0, $grossSalary - $totalInsurance);

        // 3. Progressive Tax Brackets (Monthly normalized)
        // Tier 1: $0 - $4,000 -> 5%
        // Tier 2: $4,000 - $10,000 -> 15%
        // Tier 3: Above $10,000 -> 25%
        $tax = 0.00;
        if ($taxableIncome > 10000) {
            $tax += ($taxableIncome - 10000) * 0.25;
            $tax += 6000 * 0.15;
            $tax += 4000 * 0.05;
        } elseif ($taxableIncome > 4000) {
            $tax += ($taxableIncome - 4000) * 0.15;
            $tax += 4000 * 0.05;
        } elseif ($taxableIncome > 500) { // Standard non-taxable allowance threshold
            $tax += ($taxableIncome - 500) * 0.05;
        }

        $tax = round($tax, 2);

        return [
            'tax' => $tax,
            'insurance' => $totalInsurance,
            'breakdown' => [
                'income_tax' => $tax,
                'pension_contribution' => $pensionContribution,
                'health_contribution' => $healthContribution,
                'total_social_security' => $totalInsurance,
                'method' => 'statutory_progressive',
            ],
        ];
    }
}
