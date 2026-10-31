<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\MasterSubject;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->check() && !auth()->user()->isSuperAdmin() 
            ? auth()->user()->school_id 
            : ($request->get('school_id', session('active_school_id', 'SCH-GV-01')));

        $activeSchool = School::where('school_id', $schoolId)
            ->with(['attendancePolicy', 'gradingSystems', 'classes.subjectAssignments.subject'])
            ->first() ?? School::first();

        session(['active_school_id' => $activeSchool->school_id]);
        $schools = School::all();

        $studentsCount = Student::where('school_id', $activeSchool->school_id)->count();
        $classesCount = SchoolClass::where('school_id', $activeSchool->school_id)->count();
        $subjectsCount = MasterSubject::where('school_id', $activeSchool->school_id)->count();
        $levelsCount = \App\Models\SchoolGradeLevel::where('school_id', $activeSchool->school_id)->count();

        // Sample attendance rate calculation
        $attendancePolicy = $activeSchool->attendancePolicy;

        return view('dashboard.index', compact(
            'schools',
            'activeSchool',
            'studentsCount',
            'classesCount',
            'subjectsCount',
            'levelsCount',
            'attendancePolicy'
        ));
    }
}
