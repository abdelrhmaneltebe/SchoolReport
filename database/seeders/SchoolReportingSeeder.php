<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\SchoolAttendancePolicy;
use App\Models\SchoolGradingSystem;
use App\Models\SchoolDivision;
use App\Models\SchoolGradeLevel;
use App\Models\MasterSubject;
use App\Models\SchoolClass;
use App\Models\ClassSubjectAssignment;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;

class SchoolReportingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. School
        $school = School::create([
            'school_id' => 'SCH-GV-01',
            'school_name' => 'Green Valley International Academy',
            'school_code' => 'GV',
            'active_scale_model' => 'PERCENTAGE_100',
            'is_active' => true
        ]);

        // 2. Attendance Policy (Pillar 1: School-Wide)
        SchoolAttendancePolicy::create([
            'school_id' => $school->school_id,
            'tracking_mode' => 'DAILY_HOMEROOM',
            'min_attendance_rate' => 85.00,
            'chronic_truancy_days' => 3,
            'tardies_per_absence' => 3
        ]);

        // 3. Grading Systems (Pillar 2: Base + High School Override)
        SchoolGradingSystem::create([
            'school_id' => $school->school_id,
            'scope_type' => 'SCHOOL_BASE',
            'grading_model' => 'LETTER_GRADE_GPA',
            'default_cw_weight' => 40.00,
            'default_exam_weight' => 60.00,
            'passing_score' => 60.00,
            'cutoffs_json' => [
                ['grade' => 'A+', 'min' => 95, 'max' => 100, 'gpa' => 4.00, 'label' => 'High Honors'],
                ['grade' => 'A',  'min' => 90, 'max' => 94.9, 'gpa' => 3.80, 'label' => 'Honors'],
                ['grade' => 'B+', 'min' => 85, 'max' => 89.9, 'gpa' => 3.30, 'label' => 'Very Good'],
                ['grade' => 'B',  'min' => 80, 'max' => 84.9, 'gpa' => 3.00, 'label' => 'Good'],
                ['grade' => 'C',  'min' => 70, 'max' => 79.9, 'gpa' => 2.00, 'label' => 'Satisfactory'],
                ['grade' => 'F',  'min' => 0,  'max' => 69.9, 'gpa' => 0.00, 'label' => 'Academic Warning']
            ]
        ]);

        // 4. Divisions & Grade Levels
        $divHigh = SchoolDivision::create([
            'school_id' => $school->school_id,
            'division_name' => 'High School',
            'display_order' => 1
        ]);

        $grade10 = SchoolGradeLevel::create([
            'school_id' => $school->school_id,
            'division_id' => $divHigh->id,
            'grade_name' => 'Grade 10',
            'grade_code' => 'G10',
            'display_order' => 1
        ]);

        $grade11 = SchoolGradeLevel::create([
            'school_id' => $school->school_id,
            'division_id' => $divHigh->id,
            'grade_name' => 'Grade 11',
            'grade_code' => 'G11',
            'display_order' => 2
        ]);

        // 5. Master Subjects Catalog (School-Defined Names, Numbers & Max Scores)
        $subMath = MasterSubject::create([
            'school_id' => $school->school_id,
            'subject_name' => 'Mathematics (Advanced)',
            'subject_code' => '101',
            'department' => 'STEM',
            'max_score' => 100.00,
            'default_credits' => 4.00
        ]);

        $subPhys = MasterSubject::create([
            'school_id' => $school->school_id,
            'subject_name' => 'Physics Theory & Lab',
            'subject_code' => '102',
            'department' => 'STEM',
            'max_score' => 100.00,
            'default_credits' => 3.00
        ]);

        $subRel = MasterSubject::create([
            'school_id' => $school->school_id,
            'subject_name' => 'Religious & Moral Studies',
            'subject_code' => '201',
            'department' => 'Humanities',
            'max_score' => 50.00, // Custom Max 50
            'default_credits' => 1.50
        ]);

        $subFr = MasterSubject::create([
            'school_id' => $school->school_id,
            'subject_name' => 'Langue Française (Option)',
            'subject_code' => '301',
            'department' => 'Languages',
            'max_score' => 20.00, // Custom Max 20
            'default_credits' => 2.00
        ]);

        $subEng = MasterSubject::create([
            'school_id' => $school->school_id,
            'subject_name' => 'English Literature',
            'subject_code' => '103',
            'department' => 'Languages',
            'max_score' => 100.00,
            'default_credits' => 3.00
        ]);

        // 6. Classes
        $class10A = SchoolClass::create([
            'school_id' => $school->school_id,
            'grade_id' => $grade10->id,
            'class_name' => 'Class 10-A',
            'homeroom_teacher' => 'Dr. Marcus Vance',
            'max_capacity' => 30
        ]);

        // 7. Class Subject Assignments (Pillar 3: Assigned separately to Class 10-A)
        $assignMath = ClassSubjectAssignment::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'subject_id' => $subMath->id,
            'assigned_teacher' => 'Dr. Marcus Vance',
            'credit_hours' => 4.00,
            'cw_weight' => 40.00,
            'exam_weight' => 60.00,
            'display_order' => 1
        ]);

        $assignPhys = ClassSubjectAssignment::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'subject_id' => $subPhys->id,
            'assigned_teacher' => 'Dr. Marcus Vance',
            'credit_hours' => 3.00,
            'cw_weight' => 40.00,
            'exam_weight' => 60.00,
            'display_order' => 2
        ]);

        $assignRel = ClassSubjectAssignment::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'subject_id' => $subRel->id,
            'assigned_teacher' => 'Sarah Jenkins',
            'custom_max_score' => 50.00,
            'credit_hours' => 1.50,
            'cw_weight' => 20.00,
            'exam_weight' => 30.00,
            'display_order' => 3
        ]);

        $assignFr = ClassSubjectAssignment::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'subject_id' => $subFr->id,
            'assigned_teacher' => 'Madame Dubois',
            'custom_max_score' => 20.00,
            'credit_hours' => 2.00,
            'cw_weight' => 8.00,
            'exam_weight' => 12.00,
            'display_order' => 4
        ]);

        // 8. Students in Class 10-A
        $st1 = Student::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'admission_number' => 'GV-10-001',
            'full_name' => 'Alexander Hayes',
            'gender' => 'Male'
        ]);

        $st2 = Student::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'admission_number' => 'GV-10-002',
            'full_name' => 'Beatrice Vance',
            'gender' => 'Female'
        ]);

        $st3 = Student::create([
            'school_id' => $school->school_id,
            'class_id' => $class10A->id,
            'admission_number' => 'GV-10-014',
            'full_name' => 'Marcus Thorne',
            'gender' => 'Male'
        ]);

        // 9. Grade Records for Alexander Hayes
        GradeRecord::create([
            'school_id' => $school->school_id,
            'student_id' => $st1->id,
            'class_subject_assignment_id' => $assignMath->id,
            'cw_score' => 38.00,
            'exam_score' => 58.00,
            'total_score' => 96.00,
            'letter_grade' => 'A+',
            'gpa_point' => 4.00,
            'teacher_remarks' => 'Outstanding analytical rigor.'
        ]);

        GradeRecord::create([
            'school_id' => $school->school_id,
            'student_id' => $st1->id,
            'class_subject_assignment_id' => $assignPhys->id,
            'cw_score' => 39.00,
            'exam_score' => 56.00,
            'total_score' => 95.00,
            'letter_grade' => 'A+',
            'gpa_point' => 4.00,
            'teacher_remarks' => 'Excellent laboratory work.'
        ]);

        GradeRecord::create([
            'school_id' => $school->school_id,
            'student_id' => $st1->id,
            'class_subject_assignment_id' => $assignRel->id,
            'cw_score' => 19.00,
            'exam_score' => 27.00,
            'total_score' => 46.00, // 46 / 50 (92%)
            'letter_grade' => 'A',
            'gpa_point' => 3.80,
            'teacher_remarks' => 'Thoughtful ethical reflections.'
        ]);

        GradeRecord::create([
            'school_id' => $school->school_id,
            'student_id' => $st1->id,
            'class_subject_assignment_id' => $assignFr->id,
            'cw_score' => 7.50,
            'exam_score' => 10.50,
            'total_score' => 18.00, // 18 / 20 (90%)
            'letter_grade' => 'A',
            'gpa_point' => 3.80,
            'teacher_remarks' => 'Très bonne maîtrise de la langue.'
        ]);
    }
}
