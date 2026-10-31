<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\SchoolClass;
use App\Models\School;

class ExcelTemplateGeneratorService
{
    /**
     * Generate an Excel template (.xlsx) for a specific class with assigned subjects & roster.
     */
    public function generateTemplate(SchoolClass $class): string
    {
        $school = School::where('school_id', $class->school_id)->first();
        $assignedSubjects = $class->subjectAssignments()->with('subject')->orderBy('display_order')->get();
        $students = $class->students()->where('status', 'ACTIVE')->orderBy('admission_number')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($class->class_code . " Roster", 0, 31));

        // 1. School & Class Title Headers
        $sheet->setCellValue('A1', ($school->school_name ?? 'School') . ' - Official Academic Marksheet');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E293B'));

        $sheet->setCellValue('A2', "Class: {$class->class_name} ({$class->class_code}) | Academic Term: Term 1 Final (2026-2027)");
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));

        // 2. Pillar Column Headers
        // Row 4: Category Headers
        $sheet->setCellValue('A4', 'STUDENT IDENTIFIERS');
        $sheet->mergeCells('A4:B4');
        $sheet->getStyle('A4:B4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle('A4:B4')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A4:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('C4', 'PILLAR 1: ATTENDANCE RECORD');
        $sheet->mergeCells('C4:E4');
        $sheet->getStyle('C4:E4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('DBEAFE');
        $sheet->getStyle('C4:E4')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1D4ED8'));
        $sheet->getStyle('C4:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Row 5: Granular Field Headers
        $sheet->setCellValue('A5', 'Admission No');
        $sheet->setCellValue('B5', 'Full Student Name');
        $sheet->setCellValue('C5', 'Days Enrolled');
        $sheet->setCellValue('D5', 'Days Present');
        $sheet->setCellValue('E5', 'Days Absent');

        $sheet->getStyle('A5:E5')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A5:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Dynamic Subjects (Pillar 2 & 3)
        $colIndex = 6; // Starts at F
        foreach ($assignedSubjects as $idx => $assignment) {
            $subj = $assignment->subject;
            $code = $assignment->subject_number_override ?? $subj->subject_code;
            $maxScore = $assignment->custom_max_score ?? $subj->max_score;
            $cwMax = $assignment->cw_weight;
            $examMax = $assignment->exam_weight;

            $startColStr = Coordinate::stringFromColumnIndex($colIndex);
            $endColStr = Coordinate::stringFromColumnIndex($colIndex + 2);

            // Row 4: Subject Name Header
            $sheet->setCellValue("{$startColStr}4", "{$subj->subject_name} [{$code}] (Max: {$maxScore})");
            $sheet->mergeCells("{$startColStr}4:{$endColStr}4");
            $sheet->getStyle("{$startColStr}4:{$endColStr}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF3C7');
            $sheet->getStyle("{$startColStr}4:{$endColStr}4")->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309'));
            $sheet->getStyle("{$startColStr}4:{$endColStr}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row 5: CW, Exam, Total Headers
            $sheet->setCellValue("{$startColStr}5", "CW ({$cwMax})");
            $examColStr = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$examColStr}5", "Exam ({$examMax})");
            $sheet->setCellValue("{$endColStr}5", "Total ({$maxScore})");

            $sheet->getStyle("{$startColStr}5:{$endColStr}5")->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle("{$startColStr}5:{$endColStr}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $colIndex += 3;
        }

        $highestColStr = Coordinate::stringFromColumnIndex($colIndex - 1);
        $sheet->getStyle("A4:{$highestColStr}5")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // 3. Pre-populate Student Roster
        $currentRow = 6;
        foreach ($students as $student) {
            $sheet->setCellValue("A{$currentRow}", $student->admission_number);
            $sheet->setCellValue("B{$currentRow}", $student->full_name);
            $sheet->setCellValue("C{$currentRow}", 90); // Standard Term Days
            $sheet->setCellValue("D{$currentRow}", 88);
            $sheet->setCellValue("E{$currentRow}", 2);

            // Fill formula for each subject total: =SUM(CW, Exam)
            $scCol = 6;
            foreach ($assignedSubjects as $assignment) {
                $cwC = Coordinate::stringFromColumnIndex($scCol);
                $exC = Coordinate::stringFromColumnIndex($scCol + 1);
                $totC = Coordinate::stringFromColumnIndex($scCol + 2);

                $sheet->setCellValue("{$totC}{$currentRow}", "=SUM({$cwC}{$currentRow}:{$exC}{$currentRow})");
                $scCol += 3;
            }

            // Alternating zebra striping
            if ($currentRow % 2 == 0) {
                $sheet->getStyle("A{$currentRow}:{$highestColStr}{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F8FAFC');
            }

            $currentRow++;
        }

        $sheet->getStyle("A6:{$highestColStr}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

        // Auto-fit column widths
        foreach (range(1, $colIndex - 1) as $c) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Save to private storage temp directory
        $exportDir = storage_path('app/temp_templates');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = "marksheet_template_{$class->class_code}_" . date('Ymd_His') . ".xlsx";
        $filePath = $exportDir . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
}
