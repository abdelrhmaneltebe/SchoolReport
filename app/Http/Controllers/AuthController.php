<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show login portal.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoSuperAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $demoSchoolAdmin = User::where('role', 'SCHOOL_ADMIN')->first();

        return view('auth.login', compact('demoSuperAdmin', 'demoSchoolAdmin'));
    }

    /**
     * Authenticate user credentials.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Set default tenant context based on accessible schools
            $accessibleSchools = $user->accessibleSchools();
            if ($accessibleSchools->isNotEmpty()) {
                session(['active_school_id' => $accessibleSchools->first()->school_id]);
            } else {
                session(['active_school_id' => null]);
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Convenient 1-click switch login for pairing review.
     */
    public function quickLogin($role)
    {
        $user = User::where('role', $role)->first();
        if ($user) {
            Auth::login($user);
            request()->session()->regenerate();
            $accessibleSchools = $user->accessibleSchools();
            if ($accessibleSchools->isNotEmpty()) {
                session(['active_school_id' => $accessibleSchools->first()->school_id]);
            }
            return redirect()->route('dashboard')
                ->with('success', "Signed in as {$user->name} ({$user->role})");
        }

        return redirect()->route('login')->with('error', 'Demo user not found.');
    }

    /**
     * Log out of the system.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
