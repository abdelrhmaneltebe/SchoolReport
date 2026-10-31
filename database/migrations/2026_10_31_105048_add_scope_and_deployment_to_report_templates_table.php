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
        Schema::table('report_templates', function (Blueprint $table) {
            $table->string('school_id', 50)->nullable()->change();
            $table->boolean('is_global')->default(false)->after('school_id')->index();
            $table->string('template_file', 255)->nullable()->after('base_type');
            $table->json('subreports')->nullable()->after('template_file');
            $table->json('assets')->nullable()->after('subreports');
            $table->string('deployment_path', 255)->nullable()->after('assets');
            $table->boolean('is_compiled')->default(false)->after('deployment_path');
        });

        // Set system default reports as global (general) reports
        DB::table('report_templates')
            ->where('is_system', true)
            ->update([
                'is_global' => true,
                'template_file' => 'official_report_card_a4.jrxml',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropColumn([
                'is_global',
                'template_file',
                'subreports',
                'assets',
                'deployment_path',
                'is_compiled',
            ]);
        });
    }
};
