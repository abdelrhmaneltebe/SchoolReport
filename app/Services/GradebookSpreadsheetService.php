<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\ClassSubjectAssignment;
use App\Models\AcademicGradingPeriod;
use App\Models\GradingCategory;
use App\Models\StudentSubScore;
use App\Models\GradeRecord;
use App\Models\SchoolGradingSystem;

class GradebookSpreadsheetService
{
    /**
     * Generate an Excel template pre-filled with enrolled students and category columns.
     */
    public function generateTemplate(ClassSubjectAssignment $assignment, ?AcademicGradingPeriod $period, School $school, string $scoreEntryMode = 'weight'): string
    {
        $class = $assignment->schoolClass;
        $subject = $assignment->subject;

        // Resolve students: enrolled in this subject or all class students
        $students = $assignment->enrolledStudents()
            ->wherePivot('enrollment_status', 'ENROLLED')
            ->orderBy('admission_number')
            ->get();

        if ($students->isEmpty() && $class) {
            $students = $class->students()->orderBy('admission_number')->get();
        }

        // Resolve categories for the active period
        $categories = collect();
        if ($period) {
            $categories = $period->categories;
            if ($categories->isEmpty() && $period->subPeriods && $period->subPeriods->isNotEmpty()) {
                $categories = $period->subPeriods->flatMap->categories;
            }
        }

        // If still empty, check effective rule
        if ($categories->isEmpty()) {
            $gradingService = app(GradingCalculationService::class);
            $rule = $gradingService->resolveRuleForSubject($subject, $assignment);
            if ($rule) {
                $firstPeriod = $rule->periods()->with('categories')->first();
                if ($firstPeriod) {
                    $categories = $firstPeriod->categories;
                }
            }
        }

        // Existing sub-scores for pre-filling
        $existingSubScores = collect();
        $existingGradeRecords = collect();
        if ($assignment) {
            $existingSubScores = StudentSubScore::where('class_subject_assignment_id', $assignment->id)
                ->where('grading_period_id', $period ? $period->id : 0)
                ->get()
                ->groupBy('student_id');

            $existingGradeRecords = GradeRecord::where('class_subject_assignment_id', $assignment->id)
                ->get()
                ->keyBy('student_id');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gradebook Sheet');

        // 1. Header Banner
        $periodTitle = $period ? $period->period_name : 'General Grading';
        $scaleNotice = $scoreEntryMode === 'percent' ? 'Scale: Out of 100 (%)' : 'Scale: Category Weight (Max Score)';
        $sheet->setCellValue('A1', ($school->school_name ?? 'School') . " - Gradebook Marksheet ({$scaleNotice})");
        $sheet->setCellValue('A2', "Subject: {$subject->subject_name} ({$subject->subject_code}) | Batch: " . ($class->class_name ?? 'Class') . " | Period: {$periodTitle}");
        $sheet->setCellValue('A3', "Instructions: Enter marks under the category columns ({$scaleNotice}). Admission Number identifies each student. Save and upload back to Gradebook.");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1E293B'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new Color('4F46E5'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // 2. Table Column Headers (Row 5)
        $colIndex = 1;
        $sheet->setCellValue([$colIndex, 5], 'Admission Number *');
        $colIndex++;
        $sheet->setCellValue([$colIndex, 5], 'Student Full Name');
        $colIndex++;

        $categoryColMap = [];
        if ($categories->isNotEmpty()) {
            foreach ($categories as $cat) {
                $catWeight = (float) $cat->weight > 0 ? (float) $cat->weight : (float) $cat->max_score;
                $scaleText = ($scoreEntryMode === 'percent') ? "Out of 100, Weight: {$cat->weight}%" : "Max: {$catWeight}, Weight: {$cat->weight}%";
                $headerText = "{$cat->category_name} [{$scaleText}]";
                $sheet->setCellValue([$colIndex, 5], $headerText);
                $categoryColMap[$cat->id] = $colIndex;
                $colIndex++;
            }
        } else {
            // Default continuous and exam columns
            $sheet->setCellValue([$colIndex, 5], "Classwork (CW) [Max: {$assignment->cw_weight}%]");
            $categoryColMap['cw'] = $colIndex;
            $colIndex++;

            $sheet->setCellValue([$colIndex, 5], "Exam Score [Max: {$assignment->exam_weight}%]");
            $categoryColMap['exam'] = $colIndex;
            $colIndex++;
        }

        $sheet->setCellValue([$colIndex, 5], 'Teacher Remarks');
        $remarksCol = $colIndex;

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

        // Merge banner rows across all table columns
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->mergeCells("A3:{$lastColLetter}3");

        // Style Table Header (Row 5)
        $headerRange = "A5:{$lastColLetter}5";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3730A3']]]
        ]);
        $sheet->getRowDimension(5)->setRowHeight(32);

        // 3. Pre-fill Student Rows (Row 6+)
        $rowNum = 6;
        foreach ($students as $std) {
            $sheet->setCellValue([1, $rowNum], $std->admission_number);
            $sheet->setCellValue([2, $rowNum], $std->full_name);

            $stdSubScores = $existingSubScores->get($std->id, collect())->keyBy('grading_category_id');
            $gradeRecord = $existingGradeRecords->get($std->id);

            if ($categories->isNotEmpty()) {
                foreach ($categories as $cat) {
                    $cIndex = $categoryColMap[$cat->id] ?? null;
                    if ($cIndex) {
                        $sub = $stdSubScores->get($cat->id);
                        if ($sub && $sub->raw_score !== null) {
                            $sheet->setCellValue([$cIndex, $rowNum], $sub->raw_score);
                        }
                    }
                }
            } else {
                if ($gradeRecord) {
                    if ($gradeRecord->cw_score !== null) {
                        $sheet->setCellValue([$categoryColMap['cw'], $rowNum], $gradeRecord->cw_score);
                    }
                    if ($gradeRecord->exam_score !== null) {
                        $sheet->setCellValue([$categoryColMap['exam'], $rowNum], $gradeRecord->exam_score);
                    }
                }
            }

            if ($gradeRecord && !empty($gradeRecord->teacher_remarks)) {
                $sheet->setCellValue([$remarksCol, $rowNum], $gradeRecord->teacher_remarks);
            }

            // Cell formatting
            $rowRange = "A{$rowNum}:{$lastColLetter}{$rowNum}";
            $sheet->getStyle($rowRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}:{$lastColLetter}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowNum)->setRowHeight(24);

            $rowNum++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(30);
        for ($c = 3; $c < $remarksCol; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setWidth(26);
        }
        $sheet->getColumnDimension($lastColLetter)->setWidth(28);

        // Export to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'gradebook_sheet_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Import grades from an uploaded spreadsheet file (.xlsx, .xls, .csv).
     */
    public function importGradesFromSpreadsheet(
        string $filePath,
        ClassSubjectAssignment $assignment,
        ?AcademicGradingPeriod $period,
        ?int $targetCategoryId,
        School $school,
        string $scoreEntryMode = 'weight'
    ): array {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // 1. Available Categories in this Period
        $categories = collect();
        if ($period) {
            $categories = $period->categories;
            if ($categories->isEmpty() && $period->subPeriods && $period->subPeriods->isNotEmpty()) {
                $categories = $period->subPeriods->flatMap->categories;
            }
        }
        if ($categories->isEmpty()) {
            $gradingService = app(GradingCalculationService::class);
            $rule = $gradingService->resolveRuleForSubject($assignment->subject, $assignment);
            if ($rule) {
                $categories = $rule->categories;
                if ($categories->isEmpty()) {
                    $firstPeriod = $rule->periods()->with('categories')->first();
                    if ($firstPeriod) $categories = $firstPeriod->categories;
                }
            }
        }

        $targetCategory = $targetCategoryId ? GradingCategory::find($targetCategoryId) : null;

        // 2. Detect Header Row & Identify Columns
        $headerRow = 1;
        $admissionCol = null;
        $nameCol = null;
        $remarksCol = null;
        $categoryColMappings = []; // [ columnIndex => GradingCategory ]

        for ($r = 1; $r <= min(12, $highestRow); $r++) {
            $rowHeaders = [];
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $rawVal = trim((string) $sheet->getCell("{$colLetter}{$r}")->getValue());
                if (!empty($rawVal)) {
                    $rowHeaders[$c] = strtolower($rawVal);
                }
            }

            foreach ($rowHeaders as $c => $val) {
                if (in_array($val, ['admission number', 'admission number *', 'admission_number', 'admission no', 'admission_no', 'adm no', 'adm #', 'student id', 'student_id', 'id'])) {
                    $admissionCol = $c;
                } elseif (in_array($val, ['student full name', 'full name', 'student name', 'name'])) {
                    $nameCol = $c;
                } elseif (in_array($val, ['teacher remarks', 'remarks', 'comment', 'notes'])) {
                    $remarksCol = $c;
                }
            }

            if ($admissionCol || $nameCol) {
                $headerRow = $r;
                break;
            }
        }

        // Fallbacks if not detected by name
        if (!$admissionCol && !$nameCol) {
            $admissionCol = 1;
            $nameCol = 2;
            $headerRow = 5;
        } elseif (!$admissionCol) {
            $admissionCol = 1;
        }

        // 3. Map Category Columns
        if ($targetCategory) {
            // Mode A: User explicitly picked a specific category in the upload modal
            // Look for first column after student info that holds scores
            $foundScoreCol = null;
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                if ($c == $admissionCol || $c == $nameCol || $c == $remarksCol) continue;
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $headerVal = strtolower(trim((string) $sheet->getCell("{$colLetter}{$headerRow}")->getValue()));
                if (
                    stripos($headerVal, strtolower($targetCategory->category_name)) !== false ||
                    in_array($headerVal, ['score', 'grade', 'mark', 'raw score', 'marks', 'total'])
                ) {
                    $foundScoreCol = $c;
                    break;
                }
            }
            // If no header matched specifically, take the first column that isn't admission or name
            if (!$foundScoreCol) {
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    if ($c != $admissionCol && $c != $nameCol && $c != $remarksCol) {
                        $foundScoreCol = $c;
                        break;
                    }
                }
            }
            if ($foundScoreCol) {
                $categoryColMappings[$foundScoreCol] = $targetCategory;
            }
        } else {
            // Mode B: Auto-detect all category columns from headers
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                if ($c == $admissionCol || $c == $nameCol || $c == $remarksCol) continue;
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $headerVal = strtolower(trim((string) $sheet->getCell("{$colLetter}{$headerRow}")->getValue()));

                // Match against categories in this period
                foreach ($categories as $cat) {
                    $catName = strtolower(trim($cat->category_name));
                    if (stripos($headerVal, $catName) !== false || $headerVal === $catName) {
                        $categoryColMappings[$c] = $cat;
                        break;
                    }
                }

                // Generic fallback matching for standard categories if not matched yet
                if (!isset($categoryColMappings[$c])) {
                    foreach ($categories as $cat) {
                        $catName = strtolower(trim($cat->category_name));
                        // Check common keywords
                        if (
                            (stripos($headerVal, 'homework') !== false && stripos($catName, 'homework') !== false) ||
                            (stripos($headerVal, 'quiz') !== false && stripos($catName, 'quiz') !== false) ||
                            (stripos($headerVal, 'midterm') !== false && stripos($catName, 'midterm') !== false) ||
                            (stripos($headerVal, 'final') !== false && stripos($catName, 'final') !== false) ||
                            (stripos($headerVal, 'exam') !== false && stripos($catName, 'exam') !== false) ||
                            (stripos($headerVal, 'project') !== false && stripos($catName, 'project') !== false)
                        ) {
                            $categoryColMappings[$c] = $cat;
                            break;
                        }
                    }
                }
            }
        }

        // If no categories mapped but categories exist, and user didn't pick target category, map first available category
        if (empty($categoryColMappings) && $categories->isNotEmpty()) {
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                if ($c != $admissionCol && $c != $nameCol && $c != $remarksCol) {
                    $categoryColMappings[$c] = $categories->first();
                    break;
                }
            }
        }

        // 4. Preload Students in Class / Assignment
        $classStudents = Student::where('school_id', $school->school_id)
            ->where('class_id', $assignment->class_id)
            ->get();

        $studentsByAdmission = [];
        $studentsByName = [];
        foreach ($classStudents as $std) {
            $studentsByAdmission[strtolower(trim($std->admission_number))] = $std;
            $studentsByName[strtolower(trim($std->full_name))] = $std;
        }

        // Services & Grading System
        $gradingService = app(GradingCalculationService::class);
        $gradingSystem = SchoolGradingSystem::where('school_id', $school->school_id)->first();

        $importedStudents = 0;
        $updatedCategories = [];
        $errors = [];
        $warnings = [];

        // 5. Read Data Rows
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $admLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($admissionCol);
            $admVal = trim((string) $sheet->getCell("{$admLetter}{$r}")->getValue());

            $nameLetter = $nameCol ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nameCol) : null;
            $nameVal = $nameLetter ? trim((string) $sheet->getCell("{$nameLetter}{$r}")->getValue()) : '';

            if (empty($admVal) && empty($nameVal)) {
                continue; // Skip blank row
            }

            // Find Student
            $student = null;
            if (!empty($admVal) && isset($studentsByAdmission[strtolower($admVal)])) {
                $student = $studentsByAdmission[strtolower($admVal)];
            } elseif (!empty($nameVal) && isset($studentsByName[strtolower($nameVal)])) {
                $student = $studentsByName[strtolower($nameVal)];
            }

            if (!$student) {
                $warnings[] = "Row {$r}: Student '{$admVal}' - '{$nameVal}' not found in this class section.";
                continue;
            }

            $hasStudentGradeUpdated = false;

            // Process Category Sub-Scores
            foreach ($categoryColMappings as $colIdx => $cat) {
                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $cellVal = $sheet->getCell("{$cLetter}{$r}")->getValue();

                if ($cellVal === null || trim((string)$cellVal) === '') {
                    continue; // Skip empty score cell
                }

                $rawScore = (float) $cellVal;
                $catWeight = (float) $cat->weight > 0 ? (float) $cat->weight : (float) ($cat->max_score ?? 100.0);

                $clampedScore = max(0.0, $rawScore);

                if ($scoreEntryMode === 'percent') {
                    $weightedScore = ($clampedScore / 100.0) * $catWeight;
                    $storedMax = 100.0;
                } else {
                    $clampedScore = min($clampedScore, $catWeight);
                    $weightedScore = $clampedScore;
                    $storedMax = $catWeight;
                }

                StudentSubScore::updateOrCreate(
                    [
                        'school_id' => $school->school_id,
                        'student_id' => $student->id,
                        'class_subject_assignment_id' => $assignment->id,
                        'grading_period_id' => $period ? $period->id : 0,
                        'grading_category_id' => $cat->id,
                    ],
                    [
                        'raw_score' => $clampedScore,
                        'max_score' => $storedMax,
                        'weighted_score' => round($weightedScore, 2),
                    ]
                );

                $updatedCategories[$cat->id] = $scoreEntryMode === 'percent'
                    ? "{$cat->category_name} (Out of 100, Weight {$cat->weight}%)"
                    : "{$cat->category_name} (Max {$cat->max_score})";
                $hasStudentGradeUpdated = true;
            }

            // Optional Remarks
            $remarksText = null;
            if ($remarksCol) {
                $remLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($remarksCol);
                $remVal = trim((string) $sheet->getCell("{$remLetter}{$r}")->getValue());
                if (!empty($remVal)) {
                    $remarksText = $remVal;
                }
            }

            // If flat CW / Exam without categories was uploaded
            if (empty($categoryColMappings)) {
                // Look for CW or Exam
                $cwCol = null;
                $examCol = null;
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    if ($c == $admissionCol || $c == $nameCol) continue;
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $h = strtolower(trim((string) $sheet->getCell("{$colLetter}{$headerRow}")->getValue()));
                    if (stripos($h, 'exam') !== false) $examCol = $c;
                    elseif (stripos($h, 'cw') !== false || stripos($h, 'class') !== false) $cwCol = $c;
                }

                $cw = $cwCol ? (float) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cwCol) . $r)->getValue() : 0.0;
                $exam = $examCol ? (float) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($examCol) . $r)->getValue() : 0.0;
                $total = $cw + $exam;

                $gradeInfo = $gradingSystem ? $gradingService->calculateGradeAndGpa($total, $gradingSystem) : ['letter_grade' => null, 'gpa' => null];

                GradeRecord::updateOrCreate(
                    [
                        'school_id' => $school->school_id,
                        'class_subject_assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'cw_score' => $cw,
                        'exam_score' => $exam,
                        'total_score' => $total,
                        'letter_grade' => $gradeInfo['letter_grade'],
                        'gpa_point' => $gradeInfo['gpa'],
                        'teacher_remarks' => $remarksText,
                        'academic_cycle' => '2026-2027',
                        'term_name' => $period ? $period->period_name : 'Term 1'
                    ]
                );
                $hasStudentGradeUpdated = true;
                $updatedCategories['direct'] = 'Classwork & Exam Marks';
            } else {
                // Recalculate full student total score across all recorded sub-scores
                $allSubScores = StudentSubScore::where('class_subject_assignment_id', $assignment->id)
                    ->where('grading_period_id', $period ? $period->id : 0)
                    ->where('student_id', $student->id)
                    ->with('category')
                    ->get();

                $calculatedTotal = $allSubScores->sum('weighted_score');
                $cwScore = 0.0;
                $examScore = 0.0;

                foreach ($allSubScores as $sub) {
                    $catName = strtolower($sub->category->category_name ?? '');
                    if (stripos($catName, 'exam') !== false || stripos($catName, 'terminal') !== false) {
                        $examScore += (float) $sub->weighted_score;
                    } else {
                        $cwScore += (float) $sub->weighted_score;
                    }
                }

                $gradeInfo = $gradingSystem 
                    ? $gradingService->calculateGradeAndGpa($calculatedTotal, $gradingSystem) 
                    : ['letter_grade' => null, 'gpa' => null];

                $gradeRecord = GradeRecord::updateOrCreate(
                    [
                        'school_id' => $school->school_id,
                        'class_subject_assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'cw_score' => round($cwScore, 1),
                        'exam_score' => round($examScore, 1),
                        'total_score' => round($calculatedTotal, 1),
                        'letter_grade' => $gradeInfo['letter_grade'],
                        'gpa_point' => $gradeInfo['gpa'],
                        'teacher_remarks' => $remarksText ?? null,
                        'academic_cycle' => '2026-2027',
                        'term_name' => $period ? $period->period_name : 'Term 1'
                    ]
                );
            }

            if ($hasStudentGradeUpdated) {
                $importedStudents++;
            }
        }

        return [
            'success' => $importedStudents > 0,
            'imported_students' => $importedStudents,
            'updated_categories' => array_values($updatedCategories),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
