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
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0.00);
            $table->decimal('house_allowance', 15, 2)->default(0.00);
            $table->decimal('transport_allowance', 15, 2)->default(0.00);
            $table->decimal('meal_allowance', 15, 2)->default(0.00);
            $table->decimal('other_allowances', 15, 2)->default(0.00);
            $table->decimal('tax_deduction', 15, 2)->default(0.00);
            $table->decimal('insurance_deduction', 15, 2)->default(0.00);
            $table->decimal('other_deductions', 15, 2)->default(0.00);
            $table->date('effective_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
