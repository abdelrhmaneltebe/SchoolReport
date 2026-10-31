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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('SCHOOL_ADMIN')->after('email'); // SUPER_ADMIN, SCHOOL_ADMIN, TEACHER
            $table->string('school_id', 50)->nullable()->after('role')->index();
            $table->string('status', 20)->default('ACTIVE')->after('school_id');
            $table->string('phone', 50)->nullable()->after('status');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->string('principal_name', 150)->nullable()->after('principal_signature_path');
            $table->string('contact_email', 150)->nullable()->after('principal_name');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
            $table->text('address')->nullable()->after('contact_phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('country', 100)->default('United States')->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['principal_name', 'contact_email', 'contact_phone', 'address', 'city', 'country']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'school_id', 'status', 'phone']);
        });
    }
};
