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
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique()->index();
            $table->smallInteger('month')->index();
            $table->smallInteger('year')->index();
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross', 18, 2)->default(0.00);
            $table->decimal('total_deductions', 18, 2)->default(0.00);
            $table->decimal('total_net', 18, 2)->default(0.00);
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft')->index();
            $table->date('payment_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_batches');
    }
};
