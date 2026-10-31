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
     * Display all users.
     */
    public function index()
    {
        $users = User::with('school')->orderBy('id', 'desc')->get();
        $schools = School::orderBy('school_name')->get();

        return view('admin.users.index', compact('users', 'schools'));
    }

    /**
     * Store new system or school user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:SUPER_ADMIN,SCHOOL_ADMIN,TEACHER',
            'school_id' => 'nullable|exists:schools,school_id',
            'phone' => 'nullable|string|max:50'
        ]);

        if ($validated['role'] !== 'SUPER_ADMIN' && empty($validated['school_id'])) {
            return back()->with('error', 'A School must be selected for School Administrators and Teachers.');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'school_id' => $validated['role'] === 'SUPER_ADMIN' ? null : $validated['school_id'],
            'status' => 'ACTIVE',
            'phone' => $validated['phone'] ?? null
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$validated['name']}' ({$validated['role']}) created successfully!");
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

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User account for {$user->name} deleted.");
    }
}
