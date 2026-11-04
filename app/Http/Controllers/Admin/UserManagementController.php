<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Display all users with multi-school assignments.
     */
    public function index()
    {
        $users = User::with(['school', 'schools'])->orderBy('id', 'desc')->get();
        $schools = School::orderBy('school_name')->get();

        return view('admin.users.index', compact('users', 'schools'));
    }

    /**
     * Store new system or school user with multi-school assignment support.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:SUPER_ADMIN,SCHOOL_ADMIN,TEACHER',
            'school_ids' => 'nullable|array',
            'school_ids.*' => 'exists:schools,school_id',
            'school_id' => 'nullable|exists:schools,school_id',
            'phone' => 'nullable|string|max:50'
        ]);

        $schoolIds = $request->input('school_ids', []);
        if (empty($schoolIds) && !empty($request->input('school_id'))) {
            $schoolIds = [$request->input('school_id')];
        }

        if ($validated['role'] !== 'SUPER_ADMIN' && empty($schoolIds)) {
            return back()->with('error', 'At least one School must be selected for School Administrators and Teachers.')->withInput();
        }

        $primarySchoolId = ($validated['role'] === 'SUPER_ADMIN') ? null : ($schoolIds[0] ?? null);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'school_id' => $primarySchoolId,
            'status' => 'ACTIVE',
            'phone' => $validated['phone'] ?? null
        ]);

        if ($validated['role'] !== 'SUPER_ADMIN' && !empty($schoolIds)) {
            $user->schools()->sync($schoolIds);
        }

        $assignedCount = count($schoolIds);
        $schoolsText = $validated['role'] === 'SUPER_ADMIN' ? 'all schools (Super Admin)' : "{$assignedCount} assigned school(s)";

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$validated['name']}' ({$validated['role']}) created with access to {$schoolsText}!");
    }

    /**
     * Update existing user details, roles, and assigned schools.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role' => 'required|in:SUPER_ADMIN,SCHOOL_ADMIN,TEACHER',
            'password' => 'nullable|string|min:6',
            'school_ids' => 'nullable|array',
            'school_ids.*' => 'exists:schools,school_id',
            'school_id' => 'nullable|exists:schools,school_id',
            'phone' => 'nullable|string|max:50',
            'status' => 'nullable|in:ACTIVE,INACTIVE'
        ]);

        $schoolIds = $request->input('school_ids', []);
        if (empty($schoolIds) && !empty($request->input('school_id'))) {
            $schoolIds = [$request->input('school_id')];
        }

        if ($validated['role'] !== 'SUPER_ADMIN' && empty($schoolIds)) {
            return back()->with('error', 'At least one School must be selected for School Administrators and Teachers.')->withInput();
        }

        $primarySchoolId = ($validated['role'] === 'SUPER_ADMIN') ? null : ($schoolIds[0] ?? null);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? $user->status,
            'school_id' => $primarySchoolId,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        if ($validated['role'] === 'SUPER_ADMIN') {
            $user->schools()->detach();
        } else {
            $user->schools()->sync($schoolIds);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' updated successfully!");
    }

    /**
     * Delete user account.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own logged-in account.');
        }

        $user->schools()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User account for {$user->name} deleted.");
    }
}
