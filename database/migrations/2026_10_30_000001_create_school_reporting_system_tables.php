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
        // 1. Schools (Multi-Tenant Master)
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50)->unique();
            $table->string('school_name', 150);
            $table->string('school_code', 20)->unique();
            $table->string('logo_path')->nullable();
            $table->string('principal_signature_path')->nullable();
            $table->string('active_scale_model', 50)->default('PERCENTAGE_100');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. School-Wide Attendance Policies (Pillar 1)
        Schema::create('school_attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->string('tracking_mode', 50)->default('DAILY_HOMEROOM');
            $table->decimal('min_attendance_rate', 5, 2)->default(85.00);
            $table->integer('chronic_truancy_days')->default(3);
            $table->integer('tardies_per_absence')->default(3);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 3. School Grading Systems (Pillar 2: Base + Overrides)
        Schema::create('school_grading_systems', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->string('scope_type', 50)->default('SCHOOL_BASE'); // SCHOOL_BASE, DIVISION, GRADE
            $table->string('scope_id', 50)->nullable();
            $table->string('grading_model', 50)->default('LETTER_GRADE_GPA');
            $table->decimal('default_cw_weight', 5, 2)->default(40.00);
            $table->decimal('default_exam_weight', 5, 2)->default(60.00);
            $table->decimal('passing_score', 5, 2)->default(60.00);
            $table->json('cutoffs_json');
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 4. School Divisions / Stages
        Schema::create('school_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->string('division_name', 100);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 5. School Grade Levels
        Schema::create('school_grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('division_id')->nullable()->constrained('school_divisions')->onDelete('set null');
            $table->string('grade_name', 100);
            $table->string('grade_code', 20);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 6. Master Subjects Catalog
        Schema::create('master_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->string('subject_name', 150);
            $table->string('subject_code', 50);
            $table->string('department', 100)->nullable();
            $table->decimal('max_score', 6, 2)->default(100.00);
            $table->decimal('default_credits', 4, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 7. School Classes / Sections
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('grade_id')->constrained('school_grade_levels')->onDelete('cascade');
            $table->string('class_name', 100);
            $table->string('homeroom_teacher', 150)->nullable();
            $table->integer('max_capacity')->default(30);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        // 8. Class Subject Assignments (Pillar 3: Assigned Separately per Class)
        Schema::create('class_subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('master_subjects')->onDelete('cascade');
            $table->string('subject_number_override', 50)->nullable();
            $table->string('assigned_teacher', 150)->nullable();
            $table->decimal('custom_max_score', 6, 2)->nullable();
            $table->decimal('credit_hours', 4, 2)->default(1.00);
            $table->decimal('cw_weight', 5, 2)->default(40.00);
            $table->decimal('exam_weight', 5, 2)->default(60.00);
            $table->decimal('passing_mark', 5, 2)->default(60.00);
            $table->integer('display_order')->default(0);
            $table->boolean('include_in_gpa')->default(true);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->unique(['class_id', 'subject_id']);
        });

        // 9. Students
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('admission_number', 50);
            $table->string('full_name', 150);
            $table->string('gender', 10)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->unique(['school_id', 'admission_number']);
        });

        // 10. Attendance Records
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('attendance_date');
            $table->string('status', 30); // PRESENT, EXCUSED, UNEXCUSED, TARDY
            $table->string('session_type', 30)->default('FULL_DAY');
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->index(['school_id', 'attendance_date']);
        });

        // 11. Grade Records
        Schema::create('grade_records', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_subject_assignment_id')->constrained('class_subject_assignments')->onDelete('cascade');
            $table->string('academic_cycle', 30)->default('2026-2027');
            $table->string('term_name', 50)->default('Term 1');
            $table->decimal('cw_score', 6, 2)->default(0.00);
            $table->decimal('exam_score', 6, 2)->default(0.00);
            $table->decimal('total_score', 6, 2)->default(0.00);
            $table->string('letter_grade', 10)->nullable();
            $table->decimal('gpa_point', 4, 2)->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->unique(['student_id', 'class_subject_assignment_id', 'academic_cycle', 'term_name'], 'uq_student_grade_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_records');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('students');
        Schema::dropIfExists('class_subject_assignments');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('master_subjects');
        Schema::dropIfExists('school_grade_levels');
        Schema::dropIfExists('school_divisions');
        Schema::dropIfExists('school_grading_systems');
        Schema::dropIfExists('school_attendance_policies');
        Schema::dropIfExists('schools');
    }
};
