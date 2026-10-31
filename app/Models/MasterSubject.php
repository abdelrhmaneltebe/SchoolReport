<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterSubject extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'school_id');
    }

    public function assignments()
    {
        return $this->hasMany(ClassSubjectAssignment::class, 'subject_id');
    }

    public function gradingRule()
    {
        return $this->belongsTo(GradingStructureRule::class, 'grading_rule_id');
    }
}
