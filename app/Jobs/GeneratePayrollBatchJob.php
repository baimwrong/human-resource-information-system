<?php

namespace App\Jobs;

use App\Models\PayrollBatch;
use App\Services\AuditLogger;
use App\Services\PayrollCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePayrollBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes timeout for 1000+ staff calculations
    public int $tries = 1;

    public function __construct(
        public int $month,
        public int $year,
        public int $userId
    ) {
    }

    public function handle(PayrollCalculationService $payrollService): void
    {
        try {
            $batch = $payrollService->generateMonthlyPayroll(
                $this->month,
                $this->year,
                $this->userId
            );

            AuditLogger::log(
                action: 'queued_payroll_batch_completed',
                module: 'payroll',
                modelType: PayrollBatch::class,
                modelId: $batch->id,
                changes: [
                    'month' => $batch->month,
                    'year' => $batch->year,
                    'employees' => $batch->total_employees,
                    'net' => $batch->total_net,
                ]
            );
        } catch (\Throwable $e) {
            Log::error("GeneratePayrollBatchJob failed for {$this->month}/{$this->year}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
