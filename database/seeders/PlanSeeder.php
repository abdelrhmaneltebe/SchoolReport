<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\School;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Starter / Basic Plan
        $basic = Plan::updateOrCreate(
            ['code' => 'basic'],
            [
                'name' => 'Starter / Basic Plan',
                'description' => 'Essential academic management, basic homeroom attendance, student profiles, and standard gradebook for smaller schools.',
                'price' => 49.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_students' => 250,
                'max_classes' => 15,
                'max_teachers' => 25,
                'is_active' => true,
                'is_default' => false,
                'display_order' => 1,
                'features' => [
                    'homeroom_attendance',
                    'batch_gradebook',
                    'student_profiles',
                    'bulk_excel_ingestion',
                ],
            ]
        );

        // 2. Professional / Standard Plan (Default)
        $pro = Plan::updateOrCreate(
            ['code' => 'pro'],
            [
                'name' => 'Professional / Standard Plan',
                'description' => 'Complete institutional suite with Subject/Slot Attendance, Truancy Policies, JasperReports Engine, PDF Card Exports, and Custom Branding.',
                'price' => 149.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_students' => 1000,
                'max_classes' => 60,
                'max_teachers' => 100,
                'is_active' => true,
                'is_default' => true,
                'display_order' => 2,
                'features' => [
                    'homeroom_attendance',
                    'slot_attendance',
                    'attendance_truancy_policy',
                    'batch_gradebook',
                    'custom_grading_rules',
                    'student_profiles',
                    'bulk_excel_ingestion',
                    'jasper_report_cards',
                    'broadsheet_reports',
                    'custom_branding',
                ],
            ]
        );

        // 3. Enterprise / Premium Plan
        $enterprise = Plan::updateOrCreate(
            ['code' => 'enterprise'],
            [
                'name' => 'Enterprise / Premium Plan',
                'description' => 'Unlimited capacity, all academic & attendance features, custom Report Studio compiler, priority deployment, and advanced integrations.',
                'price' => 299.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_students' => null, // unlimited
                'max_classes' => null,  // unlimited
                'max_teachers' => null, // unlimited
                'is_active' => true,
                'is_default' => false,
                'display_order' => 3,
                'features' => array_keys(Plan::AVAILABLE_FEATURES),
            ]
        );

        // 4. Assign any unassigned existing schools to the Professional Plan
        School::whereNull('plan_id')->update([
            'plan_id' => $pro->id,
            'subscription_status' => 'ACTIVE',
            'subscription_starts_at' => now(),
        ]);
    }
}
