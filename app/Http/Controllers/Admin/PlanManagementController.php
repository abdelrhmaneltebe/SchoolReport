<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Support\Str;

class PlanManagementController extends Controller
{
    /**
     * Display all subscription plans and assigned schools.
     */
    public function index()
    {
        $plans = Plan::withCount('schools')
            ->ordered()
            ->get();

        $schools = School::with('plan')
            ->orderBy('school_name')
            ->get();

        $availableFeatures = Plan::AVAILABLE_FEATURES;

        // Group features by category for organized checkboxes
        $categorizedFeatures = [];
        foreach ($availableFeatures as $key => $feat) {
            $cat = $feat['category'] ?? 'General';
            $categorizedFeatures[$cat][$key] = $feat;
        }

        return view('admin.plans.index', compact('plans', 'schools', 'availableFeatures', 'categorizedFeatures'));
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50|unique:plans,code',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'required|in:monthly,yearly,custom',
            'max_students' => 'nullable|integer|min:0',
            'max_classes' => 'nullable|integer|min:0',
            'max_teachers' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|in:' . implode(',', array_keys(Plan::AVAILABLE_FEATURES)),
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $code = !empty($validated['code']) 
            ? Str::slug($validated['code']) 
            : Str::slug($validated['name']);

        // Check unique code fallback
        $baseCode = $code;
        $counter = 1;
        while (Plan::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter++;
        }

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan = Plan::create([
            'name' => $validated['name'],
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'USD',
            'billing_cycle' => $validated['billing_cycle'],
            'max_students' => $validated['max_students'] ?? null,
            'max_classes' => $validated['max_classes'] ?? null,
            'max_teachers' => $validated['max_teachers'] ?? null,
            'features' => $request->input('features', []),
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $isDefault,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' created successfully with " . count($plan->features ?? []) . " enabled features!");
    }

    /**
     * Update an existing subscription plan.
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:plans,code,' . $plan->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'required|in:monthly,yearly,custom',
            'max_students' => 'nullable|integer|min:0',
            'max_classes' => 'nullable|integer|min:0',
            'max_teachers' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|in:' . implode(',', array_keys(Plan::AVAILABLE_FEATURES)),
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault && !$plan->is_default) {
            Plan::where('is_default', true)->where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        $plan->update([
            'name' => $validated['name'],
            'code' => Str::slug($validated['code']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'USD',
            'billing_cycle' => $validated['billing_cycle'],
            'max_students' => $validated['max_students'] ?? null,
            'max_classes' => $validated['max_classes'] ?? null,
            'max_teachers' => $validated['max_teachers'] ?? null,
            'features' => $request->input('features', []),
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $isDefault,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' successfully updated!");
    }

    /**
     * Delete a subscription plan.
     */
    public function destroy($id)
    {
        $plan = Plan::withCount('schools')->findOrFail($id);

        if ($plan->schools_count > 0) {
            return redirect()->route('admin.plans.index')
                ->with('error', "Cannot delete plan '{$plan->name}' because {$plan->schools_count} school(s) are currently subscribed to it. Please reassign those schools first.");
        }

        $planName = $plan->name;
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$planName}' deleted successfully.");
    }

    /**
     * Assign a school to a plan and update subscription status.
     */
    public function assignSchool(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,school_id',
            'plan_id' => 'required|exists:plans,id',
            'subscription_status' => 'required|in:ACTIVE,TRIAL,EXPIRED,SUSPENDED',
            'custom_features' => 'nullable|array',
            'custom_features.*' => 'string|in:' . implode(',', array_keys(Plan::AVAILABLE_FEATURES)),
            'subscription_expires_at' => 'nullable|date',
        ]);

        $school = School::where('school_id', $validated['school_id'])->firstOrFail();
        $plan = Plan::findOrFail($validated['plan_id']);

        $school->update([
            'plan_id' => $plan->id,
            'subscription_status' => $validated['subscription_status'],
            'custom_features' => $request->input('custom_features', []),
            'subscription_expires_at' => $validated['subscription_expires_at'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', "School '{$school->school_name}' is now assigned to '{$plan->name}' ({$validated['subscription_status']})!");
    }
}
