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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly, custom
            $table->unsignedInteger('max_students')->nullable(); // null = unlimited
            $table->unsignedInteger('max_classes')->nullable(); // null = unlimited
            $table->unsignedInteger('max_teachers')->nullable(); // null = unlimited
            $table->json('features')->nullable(); // JSON array of feature slugs
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('school_id')->constrained('plans')->nullOnDelete();
            $table->string('subscription_status', 30)->default('ACTIVE')->after('plan_id'); // ACTIVE, TRIAL, EXPIRED, SUSPENDED
            $table->timestamp('subscription_starts_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_starts_at');
            $table->json('custom_features')->nullable()->after('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'subscription_status',
                'subscription_starts_at',
                'subscription_expires_at',
                'custom_features',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};
