<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Services\SchoolProvisioningService;

class SchoolManagementController extends Controller
{
    protected SchoolProvisioningService $provisioningService;

    public function __construct(SchoolProvisioningService $provisioningService)
    {
        $this->provisioningService = $provisioningService;
    }

    /**
     * Display all schools overview for Super Admin.
     */
    public function index()
    {
        $schools = School::withCount(['students', 'classes', 'users'])
            ->with('adminUser')
            ->orderBy('id', 'desc')
            ->get();

        $activeSchoolId = session('active_school_id', $schools->first()->school_id ?? null);

        return view('admin.schools.index', compact('schools', 'activeSchoolId'));
    }

    /**
     * Provision and create a new individual school with complete pillars & admin user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:150',
            'school_code' => 'required|string|max:20|unique:schools,school_code',
            'principal_name' => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'active_scale_model' => 'required|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
            'admin_name' => 'required|string|max:150',
            'admin_email' => 'required|email|max:150|unique:users,email',
            'admin_password' => 'required|string|min:6'
        ]);

        $schoolData = [
            'school_name' => $validated['school_name'],
            'school_code' => $validated['school_code'],
            'principal_name' => $validated['principal_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'active_scale_model' => $validated['active_scale_model'] ?? 'PERCENTAGE_100',
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? 'Default City'
        ];

        $adminData = [
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => $validated['admin_password'],
            'phone' => $validated['contact_phone'] ?? null
        ];

        $school = $this->provisioningService->provisionSchool($schoolData, $adminData);

        if ($request->hasFile('logo')) {
            $school->saveLogo($request->file('logo'));
        }

        // Auto-switch to newly created school
        session(['active_school_id' => $school->school_id]);

        return redirect()->route('admin.schools.index')
            ->with('success', "Individual School '{$school->school_name}' ({$school->school_code}) successfully created and initialized with Attendance, Grading Templates, and Admin User ({$validated['admin_email']})!");
    }

    /**
     * Update school profile & logo.
     */
    public function update(Request $request, $schoolId)
    {
        $school = School::where('school_id', $schoolId)->firstOrFail();

        $validated = $request->validate([
            'school_name' => 'required|string|max:150',
            'school_code' => 'required|string|max:20|unique:schools,school_code,' . $school->id,
            'principal_name' => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'active_scale_model' => 'required|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $school->school_name = $validated['school_name'];
        $school->school_code = $validated['school_code'];
        $school->principal_name = $validated['principal_name'] ?? $school->principal_name;
        $school->contact_email = $validated['contact_email'] ?? $school->contact_email;
        $school->contact_phone = $validated['contact_phone'] ?? $school->contact_phone;
        $school->active_scale_model = $validated['active_scale_model'] ?? $school->active_scale_model;
        $school->address = $validated['address'] ?? $school->address;
        $school->city = $validated['city'] ?? $school->city;

        if ($request->hasFile('logo')) {
            $school->saveLogo($request->file('logo'));
        }

        $school->save();

        return redirect()->back()
            ->with('success', "School '{$school->school_name}' profile and logo updated successfully!");
    }

    /**
     * Delete custom logo for a school.
     */
    public function removeLogo($schoolId)
    {
        $school = School::where('school_id', $schoolId)->firstOrFail();
        $school->deleteLogo();

        return redirect()->back()
            ->with('success', "Custom logo for '{$school->school_name}' removed.");
    }

    /**
     * Switch active school context (Super Admin across all schools, School Admin across assigned schools).
     */
    public function switchSchool(Request $request, $schoolId)
    {
        $school = School::where('school_id', $schoolId)->firstOrFail();

        // Enforce Multi-School Access Control
        if (!auth()->user()->hasAccessToSchool($school->school_id)) {
            abort(403, "Access Denied: You do not have administrative access to {$school->school_name}.");
        }

        session(['active_school_id' => $school->school_id]);

        return redirect()->back()->with('success', "Switched workspace to {$school->school_name} ({$school->school_code})");
    }
}
