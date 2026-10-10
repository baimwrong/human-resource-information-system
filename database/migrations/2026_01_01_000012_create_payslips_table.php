<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for PostgreSQL 18.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained('payroll_batches')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('slip_number', 50)->unique()->index();
            $table->smallInteger('month');
            $table->smallInteger('year');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('total_allowance', 15, 2)->default(0.00);
            $table->decimal('total_deduction', 15, 2)->default(0.00);
            $table->decimal('net_salary', 15, 2);
            $table->jsonb('breakdown');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->default('bank_transfer');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
