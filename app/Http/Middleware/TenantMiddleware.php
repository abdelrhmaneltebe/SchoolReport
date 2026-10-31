<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\School;
use Illuminate\Support\Facades\View;

class TenantMiddleware
{
    /**
     * Handle multi-tenant isolation and school context resolution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            if ($user->isSuperAdmin()) {
                // Super Admin can switch between schools or specify ?school_id=...
                $schoolId = $request->get('school_id', session('active_school_id'));
                $school = School::where('school_id', $schoolId)->first() ?? School::first();

                if ($school) {
                    session(['active_school_id' => $school->school_id]);
                }
            } else {
                // Individual School Admin & Teachers are strictly isolated to their own school_id
                $school = School::where('school_id', $user->school_id)->first();
            }

            // Share globally with all Blade views (cached for 60 seconds to eliminate remote DB latency)
            View::share('currentSchool', $school);
            $allSchools = cache()->remember('tenant_all_schools_list', 60, function () {
                return School::orderBy('school_name')->get();
            });
            View::share('allSchools', $allSchools);
        }

        return $next($request);
    }
}
