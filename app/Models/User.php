<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'school_id',
        'status',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Primary / default school tenant.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    /**
     * All schools this user is explicitly assigned to.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(
            School::class,
            'school_user',
            'user_id',
            'school_id',
            'id',
            'school_id'
        )->withTimestamps();
    }

    /**
     * Returns all schools accessible to this user.
     * - Super Admins have global access to all schools.
     * - School Admins / Teachers have access to their assigned schools.
     */
    public function accessibleSchools(): Collection
    {
        if ($this->isSuperAdmin()) {
            return School::query()->orderBy('school_name')->get();
        }

        $assigned = $this->schools()->orderBy('school_name')->get();

        // Fallback for legacy single school_id
        if ($assigned->isEmpty() && !empty($this->school_id)) {
            $fallback = School::where('school_id', $this->school_id)->get();
            if ($fallback->isNotEmpty()) {
                return $fallback;
            }
        }

        return $assigned;
    }

    /**
     * Check if user has permission to access or switch to a given school.
     */
    public function hasAccessToSchool(string|School|null $school): bool
    {
        if (!$school) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $schoolId = is_string($school) ? $school : $school->school_id;

        return $this->accessibleSchools()->pluck('school_id')->contains($schoolId);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'SCHOOL_ADMIN';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'TEACHER';
    }
}
