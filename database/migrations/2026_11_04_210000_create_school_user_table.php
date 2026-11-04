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
        Schema::create('school_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('school_id', 50);
            $table->foreign('school_id')->references('school_id')->on('schools')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'school_id']);
        });

        // Backfill existing user school associations into the pivot table
        if (Schema::hasTable('users') && Schema::hasTable('schools')) {
            $existingUsers = DB::table('users')
                ->whereNotNull('school_id')
                ->where('school_id', '!=', '')
                ->get();

            foreach ($existingUsers as $u) {
                $schoolExists = DB::table('schools')->where('school_id', $u->school_id)->exists();
                if ($schoolExists) {
                    DB::table('school_user')->insertOrIgnore([
                        'user_id' => $u->id,
                        'school_id' => $u->school_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_user');
    }
};
