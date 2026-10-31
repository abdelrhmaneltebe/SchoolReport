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
        Schema::table('master_subjects', function (Blueprint $table) {
            $table->foreignId('grading_rule_id')->nullable()->after('default_credits')
                ->constrained('grading_structure_rules')->nullOnDelete();
            $table->decimal('pass_limit', 6, 2)->nullable()->after('grading_rule_id');
        });

        Schema::table('class_subject_assignments', function (Blueprint $table) {
            $table->foreignId('grading_rule_id')->nullable()->after('include_in_gpa')
                ->constrained('grading_structure_rules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_subject_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grading_rule_id');
        });

        Schema::table('master_subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grading_rule_id');
            $table->dropColumn('pass_limit');
        });
    }
};
