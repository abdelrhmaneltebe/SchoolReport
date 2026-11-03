<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\MasterSubject;
use App\Models\ClassSubjectAssignment;
use App\Models\GradeRecord;
use App\Models\AcademicGradingPeriod;
use App\Models\SchoolGradingSystem;

class ReportAnalyticsService
{
    /**
     * Build comprehensive report card data for a single student.
     */
    public function buildStudentReportCardData(
        Student $student,
        ?AcademicGradingPeriod $period = null,
        ?School $preloadedSchool = null,
        ?array $preloadedCutoffs = null
    ): array {
        $school = $preloadedSchool ?? (School::where('school_id', $student->school_id)
            ->with(['attendancePolicy', 'gradingSystems'])
            ->first() ?? School::first());

        $student->loadMissing([
            'schoolClass.gradeLevel.division',
            'schoolClass.subjectAssignments.subject.gradingRule.periods.categories',
            'schoolClass.subjectAssignments.courseEnrollments',
            'gradeRecords.assignment.subject',
            'subScores.category',
            'attendanceRecords'
        ]);

        $schoolClass = $student->schoolClass;
        $gradingSystem = $school ? $school->gradingSystems->first() : null;
        $cutoffs = $preloadedCutoffs ?? ($gradingSystem ? $gradingSystem->cutoffs_json : $this->defaultCutoffs());

        // Pre-build enrollment map if class has assignments
        $assignmentSelectiveMap = [];
        $assignmentStudentEnrollmentMap = [];
        if ($schoolClass && $schoolClass->subjectAssignments) {
            foreach ($schoolClass->subjectAssignments as $assign) {
                $hasSelective = $assign->courseEnrollments && $assign->courseEnrollments->isNotEmpty();
                $assignmentSelectiveMap[$assign->id] = $hasSelective;
                if ($hasSelective) {
                    $assignmentStudentEnrollmentMap[$assign->id] = $assign->courseEnrollments
                        ->pluck('student_id')
                        ->flip()
                        ->all();
                }
            }
        }

        $totalInClass = $schoolClass ? ($schoolClass->relationLoaded('students') ? $schoolClass->students->count() : $schoolClass->students()->count()) : 1;

        return $this->compileStudentCard(
            $student,
            $schoolClass,
            $school,
            $cutoffs,
            $assignmentSelectiveMap,
            $assignmentStudentEnrollmentMap,
            $totalInClass,
            $period
        );
    }

    /**
     * Build report cards for an entire class/batch with calculated class ranks (Fully Eager-Loaded).
     */
    public function buildClassReportCardsData(SchoolClass $class, ?AcademicGradingPeriod $period = null): array
    {
        // 1. Batch load School & Grading Systems ONCE
        $school = School::where('school_id', $class->school_id)
            ->with(['attendancePolicy', 'gradingSystems'])
            ->first() ?? School::first();

        $gradingSystem = $school ? $school->gradingSystems->first() : null;
        $cutoffs = $gradingSystem ? $gradingSystem->cutoffs_json : $this->defaultCutoffs();

        // 2. Batch load Class relations ONCE
        $class->loadMissing([
            'gradeLevel.division',
            'subjectAssignments.subject.gradingRule.periods.categories',
            'subjectAssignments.courseEnrollments',
        ]);

        // 3. Batch load all Students with gradeRecords and subScores in ONE batch
        $students = $class->students()
            ->with([
                'gradeRecords.assignment.subject',
                'subScores.category',
                'attendanceRecords'
            ])
            ->orderBy('full_name')
            ->get();

        $totalInClass = $students->count();
        if ($totalInClass === 0) {
            return [];
        }

        // 4. Map selective enrollments in memory for O(1) checks
        $assignmentSelectiveMap = [];
        $assignmentStudentEnrollmentMap = [];
        foreach ($class->subjectAssignments as $assignment) {
            $hasSelective = $assignment->courseEnrollments && $assignment->courseEnrollments->isNotEmpty();
            $assignmentSelectiveMap[$assignment->id] = $hasSelective;
            if ($hasSelective) {
                $assignmentStudentEnrollmentMap[$assignment->id] = $assignment->courseEnrollments
                    ->pluck('student_id')
                    ->flip()
                    ->all();
            }
        }

        $schoolBranding = [
            'name' => $school->school_name ?? 'School of Excellence',
            'code' => $school->school_code ?? 'SCH',
            'academicYear' => '2026-2027',
            'term' => $period ? $period->period_name : 'Annual Cumulative Term',
            'gradingScale' => $school->active_scale_model ?? 'LETTER_GRADE_GPA',
            'cutoffs' => $cutoffs,
            'logoUrl' => $school ? $school->getLogoUrl() : null,
            'logoBase64' => $school ? $school->getLogoBase64() : null,
            'hasLogo' => $school ? $school->hasCustomLogo() : false,
        ];

        $cards = [];
        foreach ($students as $student) {
            // Attach already-loaded schoolClass to avoid any lazy loading
            $student->setRelation('schoolClass', $class);

            $cards[] = $this->compileStudentCard(
                $student,
                $class,
                $school,
                $cutoffs,
                $assignmentSelectiveMap,
                $assignmentStudentEnrollmentMap,
                $totalInClass,
                $period,
                $schoolBranding
            );
        }

        // Compute rankings based on overallPercentage descending
        usort($cards, function ($a, $b) {
            return $b['metrics']['overallPercentage'] <=> $a['metrics']['overallPercentage'];
        });

        $rank = 1;
        foreach ($cards as &$card) {
            $card['metrics']['rankInClass'] = "{$rank} of {$totalInClass}";
            $rank++;
        }
        unset($card);

        return $cards;
    }

    /**
     * Pure in-memory compilation of a single student's report card data (Zero DB queries).
     */
    protected function compileStudentCard(
        Student $student,
        ?SchoolClass $schoolClass,
        ?School $school,
        array $cutoffs,
        array $assignmentSelectiveMap,
        array $assignmentStudentEnrollmentMap,
        int $totalInClass,
        ?AcademicGradingPeriod $period = null,
        ?array $precomputedSchoolBranding = null
    ): array {
        $subjectRows = [];
        $totalCredits = 0.0;
        $totalWeightedPoints = 0.0;
        $totalRawScore = 0.0;
        $totalMaxScore = 0.0;
        $passedSubjectsCount = 0;
        $totalSubjectsCount = 0;

        if ($schoolClass && $schoolClass->subjectAssignments) {
            foreach ($schoolClass->subjectAssignments as $assignment) {
                // Check if student is enrolled in this course (in-memory lookup)
                if (!empty($assignmentSelectiveMap[$assignment->id])) {
                    if (!isset($assignmentStudentEnrollmentMap[$assignment->id][$student->id])) {
                        continue;
                    }
                }

                $totalSubjectsCount++;
                $record = $student->gradeRecords ? $student->gradeRecords->firstWhere('class_subject_assignment_id', $assignment->id) : null;
                $subject = $assignment->subject;
                $credits = (float)($assignment->default_credits ?? $subject->default_credits ?? 1.0);
                $passMark = (float)($assignment->passing_mark ?? $subject->pass_limit ?? 60.0);

                $rawScore = $record && $record->total_score !== null ? (float)$record->total_score : null;
                $maxScore = 100.0; // standard scale total
                $letterGrade = $record ? $record->letter_grade : null;
                $gpa = $record ? (float)($record->gpa_point ?? $record->gpa) : null;
                $isPassed = $rawScore !== null ? ($rawScore >= $passMark) : null;

                if ($rawScore !== null && ($letterGrade === null || $gpa === null)) {
                    $gradeInfo = $this->calculateGradeFromCutoffs($rawScore, $cutoffs);
                    $letterGrade = $gradeInfo['grade'];
                    $gpa = $gradeInfo['gpa'];
                }

                if ($isPassed) {
                    $passedSubjectsCount++;
                }

                if ($rawScore !== null) {
                    $totalRawScore += $rawScore;
                    $totalMaxScore += $maxScore;
                    $totalCredits += $credits;
                    $totalWeightedPoints += ($gpa ?? 0.0) * $credits;
                }

                // Sub-scores breakdown (Continuous Assessment vs Terminal Exam)
                $subScoresData = [];
                $matchingSubScores = $student->subScores ? $student->subScores->where('class_subject_assignment_id', $assignment->id) : collect();
                foreach ($matchingSubScores as $sub) {
                    $subScoresData[] = [
                        'categoryName' => $sub->category->category_name ?? 'Assessment',
                        'weight' => $sub->category->weight ?? null,
                        'rawScore' => (float)$sub->raw_score,
                        'maxScore' => (float)$sub->max_score,
                        'weightedScore' => (float)$sub->weighted_score,
                    ];
                }

                $subjectRows[] = [
                    'assignmentId' => $assignment->id,
                    'subjectCode' => $assignment->subject_number_override ?? $subject->subject_code ?? 'SUBJ',
                    'subjectName' => $subject->subject_name ?? 'Subject',
                    'teacherName' => $assignment->assigned_teacher ?? 'Faculty Member',
                    'credits' => $credits,
                    'passMark' => $passMark,
                    'rawScore' => $rawScore,
                    'maxScore' => $maxScore,
                    'letterGrade' => $letterGrade ?? '-',
                    'gpa' => $gpa !== null ? number_format($gpa, 2) : '-',
                    'isPassed' => $isPassed,
                    'teacherRemarks' => $record ? ($record->teacher_remarks ?? $this->defaultRemark($rawScore)) : 'Pending Assessment',
                    'subScores' => $subScoresData,
                ];
            }
        }

        // Summary Calculations
        $overallPercentage = $totalMaxScore > 0 ? round(($totalRawScore / $totalMaxScore) * 100.0, 1) : 0.0;
        $cumulativeGpa = $totalCredits > 0 ? round($totalWeightedPoints / $totalCredits, 2) : 0.0;
        $overallGradeInfo = $this->calculateGradeFromCutoffs($overallPercentage, $cutoffs);

        // Academic Standing / Honors
        $honorStatus = 'Good Standing';
        if ($overallPercentage >= 95.0) {
            $honorStatus = "Principal's High Honor List";
        } elseif ($overallPercentage >= 90.0) {
            $honorStatus = 'Dean\'s Honor Roll';
        } elseif ($overallPercentage >= 80.0) {
            $honorStatus = 'Academic Merit';
        } elseif ($overallPercentage < 60.0 && $totalRawScore > 0) {
            $honorStatus = 'Academic Intervention / Remedial Required';
        }

        // Attendance Profile (Real dynamic data from attendance_records or baseline fallback)
        $records = $student->attendanceRecords;
        if ($records && $records->isNotEmpty()) {
            $policy = $school ? $school->attendancePolicy : null;
            $tardiesPerAbsence = max(1, $policy?->tardies_per_absence ?? 3);
            $minAttendanceRate = (float)($policy?->min_attendance_rate ?? 85.0);
            $chronicTruancyDays = (int)($policy?->chronic_truancy_days ?? 3);

            $attendanceDays = $records->count();
            $presentDays = $records->where('status', 'PRESENT')->count();
            $tardyDays = $records->where('status', 'TARDY')->count();
            $unexcusedDays = $records->where('status', 'UNEXCUSED')->count();
            $excusedDays = $records->where('status', 'EXCUSED')->count();
            $absentDays = $unexcusedDays + $excusedDays;

            $equivalentFromTardies = (int)floor($tardyDays / $tardiesPerAbsence);
            $effectiveAbsences = $unexcusedDays + $equivalentFromTardies;

            $attendanceRate = $attendanceDays > 0
                ? round((($presentDays + $tardyDays) / $attendanceDays) * 100.0, 1)
                : 100.0;

            $isWarning = $attendanceRate < $minAttendanceRate || $effectiveAbsences >= $chronicTruancyDays;
            $attendanceStatus = $isWarning ? 'Attendance Warning' : 'Regular';
        } else {
            $attendanceDays = 90;
            $presentDays = 87;
            $absentDays = 3;
            $attendanceRate = round(($presentDays / $attendanceDays) * 100.0, 1);
            $attendanceStatus = 'Regular';
        }

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admissionNumber' => $student->admission_number,
                'gender' => $student->gender ?? 'Other',
                'status' => $student->status ?? 'ACTIVE',
                'levelName' => $schoolClass && $schoolClass->gradeLevel ? $schoolClass->gradeLevel->grade_name : 'General',
                'levelCode' => $schoolClass && $schoolClass->gradeLevel ? $schoolClass->gradeLevel->grade_code : 'LVL',
                'className' => $schoolClass ? $schoolClass->class_name : 'Unassigned',
                'divisionName' => $schoolClass && $schoolClass->gradeLevel && $schoolClass->gradeLevel->division 
                    ? $schoolClass->gradeLevel->division->division_name 
                    : 'Standard Stage',
            ],
            'school' => $precomputedSchoolBranding ?? [
                'name' => $school->school_name ?? 'School of Excellence',
                'code' => $school->school_code ?? 'SCH',
                'academicYear' => '2026-2027',
                'term' => $period ? $period->period_name : 'Annual Cumulative Term',
                'gradingScale' => $school->active_scale_model ?? 'LETTER_GRADE_GPA',
                'cutoffs' => $cutoffs,
                'logoUrl' => $school ? $school->getLogoUrl() : null,
                'logoBase64' => $school ? $school->getLogoBase64() : null,
                'hasLogo' => $school ? $school->hasCustomLogo() : false,
            ],
            'metrics' => [
                'totalSubjects' => $totalSubjectsCount,
                'passedSubjects' => $passedSubjectsCount,
                'failedSubjects' => $totalSubjectsCount - $passedSubjectsCount,
                'overallPercentage' => $overallPercentage,
                'cumulativeGpa' => $cumulativeGpa,
                'letterGrade' => $overallGradeInfo['grade'],
                'honorStatus' => $honorStatus,
                'rankInClass' => "1 of {$totalInClass}",
                'totalStudentsInClass' => $totalInClass,
            ],
            'attendance' => [
                'enrolledDays' => $attendanceDays,
                'presentDays' => $presentDays,
                'absentDays' => $absentDays,
                'attendanceRate' => $attendanceRate,
                'status' => $attendanceStatus,
            ],
            'subjectRows' => $subjectRows,
        ];
    }

    /**
     * Build specialized Trimester Report Card Data for Sun of Glory International School (SGIS).
     */
    public function buildSgisTrimesterCardData(Student $student): array
    {
        $school = School::where('school_id', $student->school_id)->first() ?? School::where('school_id', 'SCH-SGIS-GAM')->first();
        $student->loadMissing([
            'schoolClass.gradeLevel.division',
            'schoolClass.subjectAssignments.subject',
            'gradeRecords.assignment.subject',
            'subScores.category'
        ]);

        $schoolClass = $student->schoolClass;
        $gradeName = $schoolClass && $schoolClass->gradeLevel ? $schoolClass->gradeLevel->grade_code : 'G10';
        $className = $schoolClass ? $schoolClass->class_name : 'G10 - C';

        // Find Trimester periods for SGIS
        $periods = AcademicGradingPeriod::where('school_id', $school ? $school->school_id : 'SCH-SGIS-GAM')
            ->where(function ($q) {
                $q->where('period_name', 'like', '%Trimester%')
                  ->orWhere('period_name', 'like', '%Term%');
            })
            ->orderBy('period_name')
            ->get();

        $t1Period = $periods->first(fn($p) => str_contains($p->period_name, '1')) ?? $periods->get(0);
        $t2Period = $periods->first(fn($p) => str_contains($p->period_name, '2')) ?? $periods->get(1);
        $t3Period = $periods->first(fn($p) => str_contains($p->period_name, '3')) ?? $periods->get(2);

        $subjectRows = [];
        $totalCredits = 0.0;
        $weightedGpaPoints = 0.0;
        $summerCoursesList = [];

        $assignments = $schoolClass ? $schoolClass->subjectAssignments : collect();

        if ($assignments->isNotEmpty()) {
            foreach ($assignments as $assign) {
                $subjName = $assign->subject->subject_name ?? 'Subject';
                $credit = (float)($assign->credit_hours ?? $assign->credits ?? $assign->subject->default_credits ?? 1.0);
                if ($credit <= 0.0) $credit = 1.0;

                // T1 score
                $t1Sub = $t1Period ? $student->subScores->where('class_subject_assignment_id', $assign->id)->where('grading_period_id', $t1Period->id)->first() : null;
                $t1Score = $t1Sub ? (float)$t1Sub->raw_score : null;
                if ($t1Score === null) {
                    $record = $student->gradeRecords->where('class_subject_assignment_id', $assign->id)->first();
                    $t1Score = $record ? (float)$record->total_score : 0.0;
                }

                // T2 score
                $t2Sub = $t2Period ? $student->subScores->where('class_subject_assignment_id', $assign->id)->where('grading_period_id', $t2Period->id)->first() : null;
                $t2Score = $t2Sub ? (float)$t2Sub->raw_score : 0.0;

                // T3 score
                $t3Sub = $t3Period ? $student->subScores->where('class_subject_assignment_id', $assign->id)->where('grading_period_id', $t3Period->id)->first() : null;
                $t3Score = $t3Sub ? (float)$t3Sub->raw_score : 0.0;

                $t1Info = $this->sgisGradeAndGpa($t1Score);
                $t2Info = $this->sgisGradeAndGpa($t2Score);
                $t3Info = $this->sgisGradeAndGpa($t3Score);

                $cumPercent = round(($t1Score + $t2Score + $t3Score) / 3.0);
                $cumInfo = $this->sgisGradeAndGpa($cumPercent);

                $totalCredits += $credit;
                $weightedGpaPoints += ($cumInfo['gpa'] * $credit);

                if ($cumInfo['grade'] === 'F' || $cumPercent < 60.0) {
                    $summerCoursesList[] = "{$subjName}({$gradeName})";
                }

                $subjectRows[] = [
                    'subjectName' => $subjName,
                    't1Percent' => $t1Score > 0 ? (floor($t1Score) == $t1Score ? number_format($t1Score, 0) : number_format($t1Score, 2)) : '0',
                    't1Grade' => $t1Info['grade'],
                    't2Percent' => $t2Score > 0 ? (floor($t2Score) == $t2Score ? number_format($t2Score, 0) : number_format($t2Score, 2)) : '0',
                    't2Grade' => $t2Info['grade'],
                    't3Percent' => $t3Score > 0 ? (floor($t3Score) == $t3Score ? number_format($t3Score, 0) : number_format($t3Score, 2)) : '0',
                    't3Grade' => $t3Info['grade'],
                    'cumulativePercent' => (string)(int)$cumPercent,
                    'cumulativeGrade' => $cumInfo['grade'],
                    'credit' => number_format($credit, 1),
                    'gpaPoint' => $cumInfo['gpa'],
                ];
            }
        } else {
            // Default sample rows matching official SGIS report
            $defaults = [
                ['English Language Arts', 90.81, 1.0],
                ['Algebra 2', 51.49, 1.0],
                ['Biology', 30.8, 1.0],
                ['World History', 45.29, 1.0],
                ['German', 52.0, 1.0],
                ['Computer', 90.0, 0.5],
                ['Art and Design', 0.0, 0.5],
                ['Physical Education', 100.0, 0.5],
            ];

            foreach ($defaults as $d) {
                $subjName = $d[0];
                $t1Score = (float)$d[1];
                $credit = (float)$d[2];
                $t2Score = 0.0;
                $t3Score = 0.0;

                $t1Info = $this->sgisGradeAndGpa($t1Score);
                $t2Info = $this->sgisGradeAndGpa($t2Score);
                $t3Info = $this->sgisGradeAndGpa($t3Score);

                $cumPercent = round(($t1Score + $t2Score + $t3Score) / 3.0);
                $cumInfo = $this->sgisGradeAndGpa($cumPercent);

                $totalCredits += $credit;
                $weightedGpaPoints += ($cumInfo['gpa'] * $credit);

                if ($cumInfo['grade'] === 'F' || $cumPercent < 60.0) {
                    $summerCoursesList[] = "{$subjName}({$gradeName})";
                }

                $subjectRows[] = [
                    'subjectName' => $subjName,
                    't1Percent' => $t1Score > 0 ? (floor($t1Score) == $t1Score ? number_format($t1Score, 0) : number_format($t1Score, 2)) : '0',
                    't1Grade' => $t1Info['grade'],
                    't2Percent' => '0',
                    't2Grade' => 'F',
                    't3Percent' => '0',
                    't3Grade' => 'F',
                    'cumulativePercent' => (string)(int)$cumPercent,
                    'cumulativeGrade' => $cumInfo['grade'],
                    'credit' => number_format($credit, 1),
                    'gpaPoint' => $cumInfo['gpa'],
                ];
            }
        }

        sort($summerCoursesList);
        $summerCoursesString = implode(', ', $summerCoursesList);
        $overallGpa = $totalCredits > 0 ? round($weightedGpaPoints / $totalCredits, 2) : 0.0;

        $logoDiskPath = $school ? $school->getLogoDiskPath() : null;
        if (!$logoDiskPath || !file_exists($logoDiskPath)) {
            $customDir = storage_path('app/schools/SCH-SGIS-GAM/reports/custom/images');
            $logoDiskPath = file_exists($customDir . '/logo.png') 
                ? $customDir . '/logo.png' 
                : base_path('reporting-engine/templates/images/logo.png');
        }

        $logoPath = $logoDiskPath ? str_replace('\\', '/', $logoDiskPath) : '';
        $logoBase64 = $school ? $school->getLogoBase64() : null;
        if (!$logoBase64 && $logoDiskPath && file_exists($logoDiskPath)) {
            $logoBase64 = 'data:image/' . pathinfo($logoDiskPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoDiskPath));
        }

        $customDir = storage_path('app/schools/SCH-SGIS-GAM/reports/custom/images');
        $cogniaDiskPath = file_exists($customDir . '/cognia_seal.png') 
            ? $customDir . '/cognia_seal.png' 
            : base_path('reporting-engine/templates/images/cognia_seal.png');
        $footerDiskPath = file_exists($customDir . '/footer_address.png') 
            ? $customDir . '/footer_address.png' 
            : base_path('reporting-engine/templates/images/footer_address.png');

        $cogniaPath = str_replace('\\', '/', $cogniaDiskPath);
        $footerPath = str_replace('\\', '/', $footerDiskPath);

        $cogniaBase64 = file_exists($cogniaDiskPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($cogniaDiskPath)) : null;
        $footerBase64 = file_exists($footerDiskPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerDiskPath)) : null;

        $customJrxml = storage_path('app/schools/SCH-SGIS-GAM/reports/custom/sgis_report_card_a4.jrxml');
        if (!file_exists($customJrxml)) {
            $customJrxml = base_path('reporting-engine/templates/sgis_report_card_a4.jrxml');
        }
        $jrxmlDefaults = $this->parseJrxmlDefaultValues($customJrxml);

        $schoolName = $jrxmlDefaults['P_SCHOOL_NAME'] ?? ($school ? $school->school_name : 'SUN OF GLORY INTERNATIONAL SCHOOL');
        $reportTitle = $jrxmlDefaults['P_REPORT_TITLE'] ?? 'Report Card 2025/2026';
        $legalRep = $jrxmlDefaults['P_LEGAL_REP_NAME'] ?? 'Mohamed Tayseer Mattar';
        $director = $jrxmlDefaults['P_DIRECTOR_NAME'] ?? 'Ragia Al Akhal';

        // SGIS Attendance Calculations (T1, T2, T3)
        $attendanceRecords = $student->attendanceRecords ?? collect();
        $t1Absences = 0;
        $t2Absences = 0;
        $t3Absences = 0;

        $policy = $school ? $school->attendancePolicy : null;
        $tardiesPerAbsence = max(1, $policy?->tardies_per_absence ?? 3);

        if ($attendanceRecords->isNotEmpty()) {
            foreach (['t1' => $t1Period, 't2' => $t2Period, 't3' => $t3Period] as $key => $periodObj) {
                if ($periodObj && $periodObj->start_date && $periodObj->end_date && $periodObj->start_date != $periodObj->end_date) {
                    $periodRecs = $attendanceRecords->filter(function($r) use ($periodObj) {
                        return $r->attendance_date >= $periodObj->start_date && $r->attendance_date <= $periodObj->end_date;
                    });
                } else {
                    $periodRecs = collect();
                }

                $unexcused = $periodRecs->where('status', 'UNEXCUSED')->count();
                $tardy = $periodRecs->where('status', 'TARDY')->count();
                $totalAbs = $unexcused + (int)floor($tardy / $tardiesPerAbsence);

                if ($key === 't1') $t1Absences = $totalAbs;
                if ($key === 't2') $t2Absences = $totalAbs;
                if ($key === 't3') $t3Absences = $totalAbs;
            }

            // If periods have overlapping/null dates or yielded 0, but attendance records exist, assign total to t1
            if ($t1Absences === 0 && $t2Absences === 0 && $t3Absences === 0) {
                $unexcusedTotal = $attendanceRecords->where('status', 'UNEXCUSED')->count();
                $tardyTotal = $attendanceRecords->where('status', 'TARDY')->count();
                $t1Absences = $unexcusedTotal + (int)floor($tardyTotal / $tardiesPerAbsence);
            }
        }

        return [
            'school' => [
                'name' => $schoolName,
                'code' => $school ? $school->school_code : 'SGIS',
                'academicYear' => '2025/2026',
                'reportTitle' => $reportTitle,
                'legalRep' => $legalRep,
                'director' => $director,
                'logoPath' => $logoPath,
                'cogniaPath' => $cogniaPath,
                'footerPath' => $footerPath,
                'logoBase64' => $logoBase64,
                'cogniaBase64' => $cogniaBase64,
                'footerBase64' => $footerBase64,
                'logoUrl' => $school ? $school->getLogoUrl() : asset('images/default_logo.png'),
                'hasLogo' => $school ? $school->hasCustomLogo() : false,
            ],
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admissionNumber' => $student->admission_number,
                'className' => $className,
                'gradeName' => $gradeName,
                'date' => now()->format('d/m/Y'),
            ],
            'subjectRows' => $subjectRows,
            'metrics' => [
                'totalCredits' => number_format($totalCredits, 1),
                'gpa' => $overallGpa > 0 ? number_format($overallGpa, 2) : '0',
                'summerCourses' => $summerCoursesString,
            ],
            'attendance' => [
                't1' => $t1Absences,
                't2' => $t2Absences,
                't3' => $t3Absences,
            ],
        ];
    }

    /**
     * Resolve Cognia / SGIS official grading scale (Letter grade + GPA points).
     */
    public function sgisGradeAndGpa(float $percentage): array
    {
        $rounded = (int)round($percentage);
        if ($rounded >= 97) return ['grade' => 'A+', 'gpa' => 4.0];
        if ($rounded >= 93) return ['grade' => 'A',  'gpa' => 4.0];
        if ($rounded >= 90) return ['grade' => 'A-', 'gpa' => 3.7];
        if ($rounded >= 87) return ['grade' => 'B+', 'gpa' => 3.3];
        if ($rounded >= 83) return ['grade' => 'B',  'gpa' => 3.0];
        if ($rounded >= 80) return ['grade' => 'B-', 'gpa' => 2.7];
        if ($rounded >= 77) return ['grade' => 'C+', 'gpa' => 2.3];
        if ($rounded >= 73) return ['grade' => 'C',  'gpa' => 2.0];
        if ($rounded >= 70) return ['grade' => 'C-', 'gpa' => 1.7];
        if ($rounded >= 67) return ['grade' => 'D+', 'gpa' => 1.3];
        if ($rounded >= 63) return ['grade' => 'D',  'gpa' => 1.0];
        if ($rounded >= 60) return ['grade' => 'D-', 'gpa' => 0.7];
        return ['grade' => 'F', 'gpa' => 0.0];
    }

    /**
     * Parse any custom parameter default values from a JRXML template.
     */
    public function parseJrxmlDefaultValues(string $jrxmlPath): array
    {
        $defaults = [];
        if (!file_exists($jrxmlPath)) {
            return $defaults;
        }

        $content = file_get_contents($jrxmlPath);
        if (preg_match_all('/<parameter\s+name="([^"]+)"[^>]*>\s*<defaultValueExpression><!\[CDATA\["?(.*?)"?\]\]><\/defaultValueExpression>/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $defaults[$m[1]] = trim($m[2], '"');
            }
        }

        return $defaults;
    }

    /**
     * Build multi-dimensional analytics for a class.
     */
    public function buildClassAnalytics(SchoolClass $class, ?AcademicGradingPeriod $period = null): array
    {
        $cards = $this->buildClassReportCardsData($class, $period);
        $totalStudents = count($cards);

        if ($totalStudents === 0) {
            return [
                'class' => $class,
                'totalStudents' => 0,
                'averagePercentage' => 0.0,
                'highestScore' => 0.0,
                'lowestScore' => 0.0,
                'passRate' => 0.0,
                'topPerformer' => null,
                'subjectAnalytics' => [],
                'gradeDistribution' => ['A+' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
                'atRiskStudents' => [],
                'topPerformers' => [],
            ];
        }

        $overallScores = array_column(array_column($cards, 'metrics'), 'overallPercentage');
        $validScores = array_filter($overallScores, fn($s) => $s > 0);

        $avgScore = count($validScores) > 0 ? round(array_sum($validScores) / count($validScores), 1) : 0.0;
        $highestScore = count($validScores) > 0 ? max($validScores) : 0.0;
        $lowestScore = count($validScores) > 0 ? min($validScores) : 0.0;

        $passedCount = 0;
        $gradeDistribution = ['A+' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
        $atRiskStudents = [];

        foreach ($cards as $card) {
            $pct = $card['metrics']['overallPercentage'];
            $grade = $card['metrics']['letterGrade'];
            if (isset($gradeDistribution[$grade])) {
                $gradeDistribution[$grade]++;
            } else {
                $gradeDistribution['F']++;
            }

            if ($pct >= 60.0) {
                $passedCount++;
            } else {
                $atRiskStudents[] = [
                    'student' => $card['student'],
                    'percentage' => $pct,
                    'failedCount' => $card['metrics']['failedSubjects'],
                    'failedSubjectsCount' => $card['metrics']['failedSubjects'],
                    'reason' => $pct < 60.0 ? 'Overall percentage below passing standard (60%)' : 'Failed one or more subjects',
                    'gpa' => $card['metrics']['cumulativeGpa'],
                ];
            }
        }

        $passRate = $totalStudents > 0 ? round(($passedCount / $totalStudents) * 100.0, 1) : 0.0;

        // Subject comparison aggregation
        $subjectMap = [];
        foreach ($cards as $card) {
            foreach ($card['subjectRows'] as $sub) {
                $subId = $sub['assignmentId'];
                if (!isset($subjectMap[$subId])) {
                    $subjectMap[$subId] = [
                        'code' => $sub['subjectCode'],
                        'name' => $sub['subjectName'],
                        'teacher' => $sub['teacherName'],
                        'passMark' => $sub['passMark'],
                        'scores' => [],
                        'grades' => ['A+' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
                    ];
                }
                if ($sub['rawScore'] !== null) {
                    $subjectMap[$subId]['scores'][] = $sub['rawScore'];
                    $lg = $sub['letterGrade'];
                    if (isset($subjectMap[$subId]['grades'][$lg])) {
                        $subjectMap[$subId]['grades'][$lg]++;
                    }
                }
            }
        }

        $subjectAnalytics = [];
        foreach ($subjectMap as $id => $data) {
            $sc = $data['scores'];
            $cnt = count($sc);
            $subAvg = $cnt > 0 ? round(array_sum($sc) / $cnt, 1) : 0.0;
            $subPassCount = count(array_filter($sc, fn($s) => $s >= $data['passMark']));
            $subPassRate = $cnt > 0 ? round(($subPassCount / $cnt) * 100.0, 1) : 0.0;

            $subjectAnalytics[] = [
                'id' => $id,
                'code' => $data['code'],
                'name' => $data['name'],
                'teacher' => $data['teacher'],
                'passMark' => $data['passMark'],
                'enrolled' => $cnt,
                'average' => $subAvg,
                'highest' => $cnt > 0 ? max($sc) : 0.0,
                'lowest' => $cnt > 0 ? min($sc) : 0.0,
                'passRate' => $subPassRate,
                'grades' => $data['grades'],
            ];
        }

        return [
            'class' => $class,
            'totalStudents' => $totalStudents,
            'averagePercentage' => $avgScore,
            'highestScore' => $highestScore,
            'lowestScore' => $lowestScore,
            'passRate' => $passRate,
            'gradeDistribution' => $gradeDistribution,
            'subjectAnalytics' => $subjectAnalytics,
            'atRiskStudents' => $atRiskStudents,
            'topPerformers' => array_slice($cards, 0, 5),
        ];
    }

    /**
     * Generate CSV content for Master Broadsheet.
     */
    public function generateBroadsheetCsv(SchoolClass $class, ?AcademicGradingPeriod $period = null): string
    {
        $cards = $this->buildClassReportCardsData($class, $period);
        $subjectNames = [];

        if (!empty($cards) && !empty($cards[0]['subjectRows'])) {
            foreach ($cards[0]['subjectRows'] as $row) {
                $subjectNames[] = $row['subjectName'];
            }
        }

        $output = fopen('php://temp', 'r+');

        // CSV Header
        $header = ['Rank', 'Admission Number', 'Student Name', 'Gender', 'Level', 'Class'];
        foreach ($subjectNames as $sn) {
            $header[] = "{$sn} (Score)";
            $header[] = "{$sn} (Grade)";
        }
        $header[] = 'Total Score';
        $header[] = 'Overall Percentage';
        $header[] = 'GPA';
        $header[] = 'Letter Grade';
        $header[] = 'Attendance Rate';
        $header[] = 'Status';

        fputcsv($output, $header);

        foreach ($cards as $card) {
            $row = [
                $card['metrics']['rankInClass'],
                $card['student']['admissionNumber'],
                $card['student']['name'],
                $card['student']['gender'],
                $card['student']['levelName'],
                $card['student']['className'],
            ];

            foreach ($card['subjectRows'] as $sub) {
                $row[] = $sub['rawScore'] !== null ? $sub['rawScore'] : '-';
                $row[] = $sub['letterGrade'];
            }

            $row[] = $card['metrics']['overallPercentage'];
            $row[] = $card['metrics']['overallPercentage'] . '%';
            $row[] = $card['metrics']['cumulativeGpa'];
            $row[] = $card['metrics']['letterGrade'];
            $row[] = $card['attendance']['attendanceRate'] . '%';
            $row[] = $card['metrics']['overallPercentage'] >= 60.0 ? 'PASSED' : 'REMEDIAL';

            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    protected function calculateGradeFromCutoffs(float $score, array $cutoffs): array
    {
        foreach ($cutoffs as $c) {
            $min = (float)($c['min'] ?? 0);
            $max = (float)($c['max'] ?? 100);
            if ($score >= $min && $score <= $max) {
                return [
                    'grade' => $c['grade'] ?? 'A',
                    'gpa' => (float)($c['gpa'] ?? 4.0),
                    'label' => $c['label'] ?? 'Passed',
                ];
            }
        }

        return ['grade' => 'F', 'gpa' => 0.0, 'label' => 'Fail'];
    }

    protected function defaultCutoffs(): array
    {
        return [
            ['grade' => 'A+', 'min' => 95, 'max' => 100, 'gpa' => 4.0, 'label' => 'Distinction'],
            ['grade' => 'A',  'min' => 90, 'max' => 94.99, 'gpa' => 3.8, 'label' => 'Excellent'],
            ['grade' => 'B',  'min' => 80, 'max' => 89.99, 'gpa' => 3.0, 'label' => 'Good'],
            ['grade' => 'C',  'min' => 70, 'max' => 79.99, 'gpa' => 2.0, 'label' => 'Satisfactory'],
            ['grade' => 'D',  'min' => 60, 'max' => 69.99, 'gpa' => 1.0, 'label' => 'Pass'],
            ['grade' => 'F',  'min' => 0,  'max' => 59.99, 'gpa' => 0.0, 'label' => 'Fail'],
        ];
    }

    protected function defaultRemark(?float $score): string
    {
        if ($score === null) return 'Pending Evaluation';
        if ($score >= 95.0) return 'Exceptional mastery of core concepts';
        if ($score >= 90.0) return 'Consistently outstanding performance';
        if ($score >= 80.0) return 'Commendable effort and analytical skill';
        if ($score >= 70.0) return 'Satisfactory progress; room for improvement';
        if ($score >= 60.0) return 'Pass mark achieved; extra practice suggested';
        return 'Needs targeted academic support and remediation';
    }
}
