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
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('class_subject_assignment_id')
                ->nullable()
                ->after('session_type')
                ->constrained('class_subject_assignments')
                ->nullOnDelete();

            $table->string('slot_name', 50)
                ->nullable()
                ->after('class_subject_assignment_id');

            $table->index(['school_id', 'attendance_date', 'session_type', 'class_subject_assignment_id'], 'att_sch_date_assign_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['class_subject_assignment_id']);
            $table->dropIndex('att_sch_date_assign_idx');
            $table->dropColumn(['class_subject_assignment_id', 'slot_name']);
        });
    }
};
