<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Academic Grading Periods (Flat or Hierarchical with Sub-Periods & Dates)
        Schema::create('academic_grading_periods', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('parent_period_id')->nullable()->constrained('academic_grading_periods')->onDelete('cascade');
            $table->string('period_name', 100); // e.g. Semester 1, Quarter 1
            $table->string('period_type', 20)->default('MAIN'); // MAIN or SUB
            $table->decimal('weight', 5, 2)->default(50.00); // Percentage weight within parent or cycle
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 2. Multi-Scenario Grading Rules (All Courses vs Course-Specific)
        Schema::create('grading_structure_rules', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->string('rule_name', 150);
            $table->string('scope_type', 30)->default('ALL_COURSES'); // ALL_COURSES, DIVISION, GRADE, COURSE
            $table->foreignId('course_id')->nullable()->constrained('master_subjects')->onDelete('cascade');
            $table->foreignId('division_id')->nullable()->constrained('school_divisions')->onDelete('cascade');
            $table->foreignId('grade_id')->nullable()->constrained('school_grade_levels')->onDelete('cascade');
            $table->string('scenario_depth', 50)->default('3_TIER_SUB_PERIOD_MAIN_CAT'); // 2_TIER_MAIN_CAT, 3_TIER_SUB_PERIOD_MAIN_CAT, 4_TIER_FULL_SUB_CAT
            $table->decimal('pass_limit', 6, 2)->default(60.00); // Course pass limit threshold
            $table->decimal('total_grade', 6, 2)->default(100.00); // Total grade scale maximum (e.g. 100, 20, 50)
            $table->decimal('credit_hours', 4, 2)->default(1.00); // Course credits
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 3. Hierarchical Grading Categories (Main Categories + Sub Categories)
        Schema::create('grading_categories', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('rule_id')->constrained('grading_structure_rules')->onDelete('cascade');
            $table->foreignId('grading_period_id')->nullable()->constrained('academic_grading_periods')->onDelete('cascade');
            $table->foreignId('parent_category_id')->nullable()->constrained('grading_categories')->onDelete('cascade');
            $table->string('category_name', 100); // e.g. Cat1, Homework, Exam, Sub1, Sub2
            $table->string('category_type', 20)->default('MAIN'); // MAIN or SUB
            $table->decimal('weight', 5, 2)->default(20.00); // Weight % within parent category or period
            $table->decimal('max_score', 6, 2)->default(100.00);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 4. Granular Student Sub-Category Scores
        Schema::create('student_sub_scores', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_subject_assignment_id')->constrained('class_subject_assignments')->onDelete('cascade');
            $table->foreignId('grading_period_id')->constrained('academic_grading_periods')->onDelete('cascade');
            $table->foreignId('grading_category_id')->constrained('grading_categories')->onDelete('cascade');
            $table->decimal('raw_score', 6, 2)->default(0.00);
            $table->decimal('max_score', 6, 2)->default(100.00);
            $table->decimal('weighted_score', 6, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->unique(['student_id', 'class_subject_assignment_id', 'grading_period_id', 'grading_category_id'], 'uq_sub_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sub_scores');
        Schema::dropIfExists('grading_categories');
        Schema::dropIfExists('grading_structure_rules');
        Schema::dropIfExists('academic_grading_periods');
    }
};
