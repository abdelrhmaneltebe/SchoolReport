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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50)->index();
            $table->string('title', 150);
            $table->string('report_code', 50);
            $table->string('category', 80)->default('General');
            $table->text('description')->nullable();
            $table->string('base_type', 50)->default('card_single');
            $table->json('parameters')->nullable();
            $table->string('default_format', 30)->default('view');
            $table->boolean('is_system')->default(false);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial default reports for all existing schools
        $schools = DB::table('schools')->get();
        foreach ($schools as $school) {
            $defaults = [
                [
                    'school_id' => $school->school_id,
                    'title' => 'Official Student Report Card',
                    'report_code' => 'card_single',
                    'category' => 'Student Report Cards',
                    'description' => 'Comprehensive individual student report card with subject breakdown, GPA, attendance, and official teacher/principal signatures.',
                    'base_type' => 'card_single',
                    'parameters' => json_encode(['level', 'class', 'student', 'period', 'format']),
                    'default_format' => 'view',
                    'is_system' => true,
                    'display_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'school_id' => $school->school_id,
                    'title' => 'Bulk Class Report Cards',
                    'report_code' => 'card_batch',
                    'category' => 'Student Report Cards',
                    'description' => 'Multi-page print-ready document for all students in a class with CSS page breaks (1 page per student) and class rankings.',
                    'base_type' => 'card_batch',
                    'parameters' => json_encode(['level', 'class', 'period', 'format']),
                    'default_format' => 'print',
                    'is_system' => true,
                    'display_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'school_id' => $school->school_id,
                    'title' => 'Performance & Grade Analytics',
                    'report_code' => 'analytics',
                    'category' => 'Performance & Analytics',
                    'description' => 'Visual dashboard with class averages, pass rates, grade distribution histograms (A+ to F), and subject comparisons.',
                    'base_type' => 'analytics',
                    'parameters' => json_encode(['level', 'class', 'period', 'pass_mark', 'format']),
                    'default_format' => 'view',
                    'is_system' => true,
                    'display_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'school_id' => $school->school_id,
                    'title' => 'Master Class Broadsheet',
                    'report_code' => 'broadsheet',
                    'category' => 'Administrative Lists',
                    'description' => 'Consolidated marksheet matrix showing all students and subjects, total scores, rankings, and pass/fail summary.',
                    'base_type' => 'broadsheet',
                    'parameters' => json_encode(['level', 'class', 'period', 'subject', 'format']),
                    'default_format' => 'view',
                    'is_system' => true,
                    'display_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'school_id' => $school->school_id,
                    'title' => 'At-Risk / Remedial Alert List',
                    'report_code' => 'at_risk',
                    'category' => 'Performance & Analytics',
                    'description' => 'Targeted intervention roster identifying students below passing standard (60%) or requiring remedial re-takes.',
                    'base_type' => 'at_risk',
                    'parameters' => json_encode(['level', 'class', 'period', 'pass_mark', 'format']),
                    'default_format' => 'view',
                    'is_system' => true,
                    'display_order' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'school_id' => $school->school_id,
                    'title' => 'Honor Roll & Top Achievers',
                    'report_code' => 'honor_roll',
                    'category' => 'Performance & Analytics',
                    'description' => 'Ranked roster of top-performing students achieving distinction and honors for academic recognition.',
                    'base_type' => 'honor_roll',
                    'parameters' => json_encode(['level', 'class', 'period', 'format']),
                    'default_format' => 'view',
                    'is_system' => true,
                    'display_order' => 6,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('report_templates')->insert($defaults);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
