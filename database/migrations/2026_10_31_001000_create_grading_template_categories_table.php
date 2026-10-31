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
        Schema::create('grading_template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 50);
            $table->foreignId('rule_id')->constrained('grading_structure_rules')->onDelete('cascade');
            $table->string('category_name', 100);
            $table->string('category_type', 30)->default('CUSTOM');
            $table->decimal('default_weight', 5, 2)->default(20.00);
            $table->decimal('default_max_score', 6, 2)->default(100.00);
            $table->json('sub_categories_json')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
        });

        Schema::table('grading_categories', function (Blueprint $table) {
            $table->foreignId('template_category_id')->nullable()->after('parent_category_id')->constrained('grading_template_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_category_id');
        });

        Schema::dropIfExists('grading_template_categories');
    }
};
