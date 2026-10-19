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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 30)->unique()->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->default('other');
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 50)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('address')->nullable();
            
            // Organizational relationships
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            
            // Employment Lifecycle
            $table->enum('employment_type', ['permanent', 'probation', 'contract', 'intern'])->default('permanent')->index();
            $table->enum('employment_status', ['active', 'on_leave', 'resigned', 'terminated'])->default('active')->index();
            $table->date('join_date')->index();
            $table->date('probation_end_date')->nullable();
            $table->date('termination_date')->nullable();
            
            // Financial & Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->string('tax_id')->nullable();
            
            // Portable JSON column for dynamic metadata/documents (PostgreSQL jsonb, MySQL json, SQLite text)
            $table->json('custom_attributes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
