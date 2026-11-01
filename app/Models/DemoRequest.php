<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'school_name',
        'phone',
        'role',
        'student_count',
        'notes',
        'status',
    ];
}
