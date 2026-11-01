<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolProfileController extends Controller
{
    /**
     * Serve the school logo image or a dynamic SVG crest fallback.
     */
    public function showLogo(string $schoolId)
    {
        $school = School::where('school_id', $schoolId)->orWhere('school_code', $schoolId)->first();

        if ($school && $school->hasCustomLogo()) {
            $path = $school->getLogoDiskPath();
            if ($path && file_exists($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'svg' => 'image/svg+xml',
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };
                return response()->file($path, [
                    'Content-Type' => $mime,
                    'Cache-Control' => 'no-cache, private, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]);
            }
        }

        // Return a crisp SVG crest monogram as fallback
        $name = $school ? $school->school_name : 'School';
        $initials = $this->extractInitials($name);
        $code = $school ? $school->school_code : 'SCH';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#1e3a8a" />
            <stop offset="100%" stop-color="#3b82f6" />
        </linearGradient>
        <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
            <feDropShadow dx="0" dy="4" stdDeviation="6" flood-opacity="0.18"/>
        </filter>
    </defs>
    <rect width="190" height="190" x="5" y="5" rx="35" fill="url(#grad)" filter="url(#shadow)"/>
    <rect width="180" height="180" x="10" y="10" rx="30" fill="none" stroke="#60a5fa" stroke-width="2" stroke-opacity="0.4"/>
    <text x="100" y="115" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="54" font-weight="800" fill="#ffffff" text-anchor="middle" letter-spacing="1">{$initials}</text>
    <text x="100" y="148" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="14" font-weight="700" fill="#93c5fd" text-anchor="middle" letter-spacing="2">{$code}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Display School Profile & Logo settings view.
     */
    public function profile(Request $request)
    {
        $activeSchool = $this->resolveActiveSchool($request);
        $activeSchool->loadCount(['students', 'classes', 'users']);

        $allSchools = auth()->check() && auth()->user()->isSuperAdmin()
            ? School::orderBy('school_name')->get()
            : collect([$activeSchool]);

        return view('school.profile', compact('activeSchool', 'allSchools'));
    }

    /**
     * Update school profile details and upload/replace logo.
     */
    public function update(Request $request)
    {
        $schoolId = $request->input('school_id');
        $school = $this->resolveSchoolForUpdate($request, $schoolId);

        $validated = $request->validate([
            'school_name' => 'required|string|max:150',
            'principal_name' => 'nullable|string|max:150',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $school->school_name = $validated['school_name'];
        $school->principal_name = $validated['principal_name'] ?? $school->principal_name;
        $school->contact_email = $validated['contact_email'] ?? $school->contact_email;
        $school->contact_phone = $validated['contact_phone'] ?? $school->contact_phone;
        $school->address = $validated['address'] ?? $school->address;
        $school->city = $validated['city'] ?? $school->city;
        $school->country = $validated['country'] ?? $school->country;

        if ($request->hasFile('logo')) {
            $school->saveLogo($request->file('logo'));
        }

        $school->save();

        return redirect()->back()->with('success', "School profile and logo for '{$school->school_name}' updated successfully! All report cards and institutional headers will now reflect these updates.");
    }

    /**
     * Remove custom logo and revert to default.
     */
    public function removeLogo(Request $request)
    {
        $schoolId = $request->input('school_id');
        $school = $this->resolveSchoolForUpdate($request, $schoolId);

        $school->deleteLogo();

        return redirect()->back()->with('success', "Custom logo for '{$school->school_name}' removed. Reverted to default monogram.");
    }

    /**
     * Resolve active school tenant.
     */
    protected function resolveActiveSchool(Request $request): School
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            $activeId = session('active_school_id');
            if ($activeId) {
                $school = School::where('school_id', $activeId)->first();
                if ($school) return $school;
            }
        } elseif ($user && $user->school_id) {
            $school = School::where('school_id', $user->school_id)->first();
            if ($school) return $school;
        }

        return School::first() ?? School::create([
            'school_id' => 'SCH-DEFAULT',
            'school_name' => 'Default Institution',
            'school_code' => 'DEF',
        ]);
    }

    /**
     * Resolve school and authorize permission to edit.
     */
    protected function resolveSchoolForUpdate(Request $request, ?string $schoolId): School
    {
        $user = auth()->user();

        if (!$schoolId) {
            $school = $this->resolveActiveSchool($request);
        } else {
            $school = School::where('school_id', $schoolId)->firstOrFail();
        }

        // Authorization check
        if ($user) {
            if ($user->isSuperAdmin()) {
                return $school;
            }
            if ($user->isSchoolAdmin() && $user->school_id === $school->school_id) {
                return $school;
            }
            abort(403, 'Unauthorized. You do not have permission to modify settings for this school.');
        }

        // Guest / Demo mode fallback
        return $school;
    }

    /**
     * Extract 2-letter uppercase monogram initials from school name.
     */
    protected function extractInitials(string $name): string
    {
        $words = preg_split('/[\s_-]+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
                if (mb_strlen($initials) >= 2) break;
            }
        }
        return mb_strlen($initials) > 0 ? $initials : 'SR';
    }
}
