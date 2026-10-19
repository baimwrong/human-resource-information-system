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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('review_period', 50)->index();
            $table->smallInteger('technical_rating')->default(3);
            $table->smallInteger('communication_rating')->default(3);
            $table->smallInteger('teamwork_rating')->default(3);
            $table->decimal('overall_rating', 3, 2)->default(3.00);
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->json('goals')->nullable();
            $table->enum('status', ['draft', 'submitted', 'completed'])->default('draft')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
