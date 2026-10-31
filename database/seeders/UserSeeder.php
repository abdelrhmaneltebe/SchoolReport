<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();

        // 1. Super Administrator (System-Wide Access & School Creator)
        User::updateOrCreate(
            ['email' => 'admin@schoolreport.com'],
            [
                'name' => 'Dr. Alexander Vance (Ministry Director)',
                'password' => Hash::make('password123'),
                'role' => 'SUPER_ADMIN',
                'school_id' => null,
                'status' => 'ACTIVE',
                'phone' => '+1 (555) 019-2834'
            ]
        );

        // 2. School Administrator (Bound to Green Valley International Academy)
        if ($school) {
            User::updateOrCreate(
                ['email' => 'registrar@greenvalley.edu'],
                [
                    'name' => 'Sarah Al-Ghamdi (Academic Registrar)',
                    'password' => Hash::make('password123'),
                    'role' => 'SCHOOL_ADMIN',
                    'school_id' => $school->school_id,
                    'status' => 'ACTIVE',
                    'phone' => '+1 (555) 342-8819'
                ]
            );

            // 3. Faculty / Teacher
            User::updateOrCreate(
                ['email' => 'teacher@greenvalley.edu'],
                [
                    'name' => 'Prof. Marcus Sterling (Head Faculty)',
                    'password' => Hash::make('password123'),
                    'role' => 'TEACHER',
                    'school_id' => $school->school_id,
                    'status' => 'ACTIVE',
                    'phone' => '+1 (555) 882-9901'
                ]
            );
        }
    }
}
