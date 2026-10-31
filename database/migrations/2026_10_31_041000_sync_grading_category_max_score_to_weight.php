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
        // For categories where max_score was defaulted to 100 and weight > 0, align max_score with category weight
        DB::table('grading_categories')
            ->where('weight', '>', 0)
            ->where('max_score', 100.0)
            ->update([
                'max_score' => DB::raw('weight')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
