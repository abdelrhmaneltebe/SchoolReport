<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('class_subject_assignment_id')->constrained('class_subject_assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('enrollment_status', 30)->default('ENROLLED'); // ENROLLED, DROPPED, AUDIT
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->unique(['class_subject_assignment_id', 'student_id'], 'uq_course_std_enrollment');
        });

        // Seed existing enrollments from students in the assigned class
        $assignments = DB::table('class_subject_assignments')->get();
        $now = now();

        foreach ($assignments as $assignment) {
            $students = DB::table('students')
                ->where('class_id', $assignment->class_id)
                ->where('school_id', $assignment->school_id)
                ->get();

            foreach ($students as $std) {
                DB::table('course_enrollments')->insertOrIgnore([
                    'school_id' => $assignment->school_id,
                    'class_subject_assignment_id' => $assignment->id,
                    'student_id' => $std->id,
                    'enrollment_status' => 'ENROLLED',
                    'enrolled_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
