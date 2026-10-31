<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\SchoolClass;
use App\Models\ClassSubjectAssignment;

class ExcelIngestionService
{
    /**
     * Parse uploaded class spreadsheet and extract student attendance + subject marks.
     */
    public function parseClassSpreadsheet(string $filePath, SchoolClass $class): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $assignedSubjects = ClassSubjectAssignment::where('class_id', $class->id)
            ->with('subject')
            ->orderBy('display_order')
            ->get();

        $parsedRows = [];
        $anomalies = [];

        // Traversal starting from data row (e.g. Row 6)
        for ($row = 6; $row <= $highestRow; $row++) {
            $admissionNo = trim((string) $sheet->getCell("A{$row}")->getValue());
            $studentName = trim((string) $sheet->getCell("B{$row}")->getValue());

            if (empty($admissionNo)) continue;

            $enrolledDays = (int) $sheet->getCell("C{$row}")->getValue();
            $presentDays = (int) $sheet->getCell("D{$row}")->getValue();
            $absentDays = (int) $sheet->getCell("E{$row}")->getValue();

            // Validate attendance sum
            if (($presentDays + $absentDays) !== $enrolledDays && $enrolledDays > 0) {
                $anomalies[] = "Row {$row} ({$studentName}): Attendance sum mismatch ({$presentDays} Present + {$absentDays} Absent != {$enrolledDays} Enrolled).";
            }

            // Extract subject marks dynamically based on assigned subjects
            $subjectScores = [];
            $colIndex = 6; // Column F starts first subject
            foreach ($assignedSubjects as $assignment) {
                $cwCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $examCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                
                $cwScore = (float) $sheet->getCell("{$cwCol}{$row}")->getValue();
                $examScore = (float) $sheet->getCell("{$examCol}{$row}")->getValue();
                $maxScore = $assignment->custom_max_score ?? $assignment->subject->max_score;

                // Check bounds
                if ($cwScore > $assignment->cw_weight || $examScore > $assignment->exam_weight) {
                    $anomalies[] = "Row {$row} ({$studentName}) in {$assignment->subject->subject_name}: Score exceeds component weight.";
                }

                $totalScore = $cwScore + $examScore;
                if ($totalScore > $maxScore) {
                    $anomalies[] = "Row {$row} ({$studentName}) in {$assignment->subject->subject_name}: Total score ({$totalScore}) exceeds maximum ({$maxScore}).";
                }

                $subjectScores[] = [
                    'assignment_id' => $assignment->id,
                    'subject_id' => $assignment->subject_id,
                    'subject_code' => $assignment->subject_number_override ?? $assignment->subject->subject_code,
                    'subject_name' => $assignment->subject->subject_name,
                    'cw' => $cwScore,
                    'exam' => $examScore,
                    'total' => $totalScore,
                    'max' => $maxScore,
                    'credit_hours' => $assignment->credit_hours
                ];

                $colIndex += 3; // Step past CW, Exam, Total columns
            }

            $parsedRows[] = [
                'admission_no' => $admissionNo,
                'name' => $studentName,
                'attendance' => [
                    'enrolled' => $enrolledDays,
                    'present' => $presentDays,
                    'absent' => $absentDays,
                    'rate' => $enrolledDays > 0 ? round(($presentDays / $enrolledDays) * 100, 1) : 0
                ],
                'scores' => $subjectScores
            ];
        }

        return [
            'valid_rows' => $parsedRows,
            'anomalies' => $anomalies
        ];
    }
}
