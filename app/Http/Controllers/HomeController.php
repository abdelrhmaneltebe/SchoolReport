<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\School;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\User;
use App\Models\DemoRequest;

class HomeController extends Controller
{
    /**
     * Display public landing & product showcase page.
     */
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $schoolsCount = School::count();
        $studentsCount = Student::count();
        $subjectsCount = MasterSubject::count();

        $demoSuperAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $demoSchoolAdmin = User::where('role', 'SCHOOL_ADMIN')->first();

        return view('home', compact(
            'schoolsCount',
            'studentsCount',
            'subjectsCount',
            'demoSuperAdmin',
            'demoSchoolAdmin'
        ));
    }

    /**
     * Store an inbound institutional demo request.
     */
    public function requestDemo(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'school_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'nullable|string|max:100',
            'student_count' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (Schema::hasTable('demo_requests')) {
            DemoRequest::create($validated);
        }

        return redirect()->to(url('/#demo'))->with(
            'demo_success',
            "Thank you, {$validated['full_name']}! Your demo request for {$validated['school_name']} has been submitted. Our academic solutions team will reach out within 24 hours to schedule your personalized walkthrough."
        );
    }
}
