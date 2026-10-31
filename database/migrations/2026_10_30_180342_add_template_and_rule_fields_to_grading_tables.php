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
        Schema::table('grading_structure_rules', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('rule_name');
            $table->string('template_key', 50)->nullable()->after('is_template');
            $table->text('description')->nullable()->after('template_key');
        });

        Schema::table('academic_grading_periods', function (Blueprint $table) {
            $table->foreignId('rule_id')->nullable()->after('school_id')->constrained('grading_structure_rules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_grading_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rule_id');
        });

        Schema::table('grading_structure_rules', function (Blueprint $table) {
            $table->dropColumn(['is_template', 'template_key', 'description']);
        });
    }
};
