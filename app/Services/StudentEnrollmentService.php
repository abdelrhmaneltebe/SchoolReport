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

class StudentEnrollmentService
{
    /**
     * Generate an Excel template (.xlsx) for bulk student enrollment.
     */
    public function generateTemplate(School $school, ?SchoolClass $defaultClass = null): string
    {
        $classes = SchoolClass::where('school_id', $school->school_id)
            ->with('gradeLevel')
            ->orderBy('class_name')
            ->get();

        $spreadsheet = new Spreadsheet();

        // 1. MAIN SHEET: Student Enrollment Template
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Student Enrollment');

        // Title Header
        $sheet->setCellValue('A1', ($school->school_name ?? 'School') . ' - Student Enrollment Template');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1E293B'));
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtitle / Instructions
        $sheet->setCellValue('A2', 'Fill in student records below. Admission Number and Full Name are mandatory. Class Name is optional (leave blank for unassigned students).');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new Color('64748B'));
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Column Headers (Row 4)
        $headers = [
            'A4' => 'Admission Number *',
            'B4' => 'Student Full Name *',
            'C4' => 'Class / Batch Name (Optional)',
            'D4' => 'Gender',
            'E4' => 'Status'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3730A3']]]
        ];
        $sheet->getStyle('A4:E4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Sample Rows
        $sampleClass = $defaultClass ? $defaultClass->class_name : ($classes->first()->class_name ?? 'Grade 9-A');
        $sampleRows = [
            ['STD-2026-001', 'Alexander Hamilton', $sampleClass, 'Male', 'ACTIVE'],
            ['STD-2026-002', 'Elizabeth Schuyler', $sampleClass, 'Female', 'ACTIVE'],
            ['STD-2026-003', 'Aaron Burr', $sampleClass, 'Male', 'ACTIVE'],
            ['STD-2026-004', 'Angelica Church', $sampleClass, 'Female', 'ACTIVE'],
            ['STD-2026-005', 'John Laurens', $sampleClass, 'Male', 'ACTIVE'],
        ];

        $rowNum = 5;
        foreach ($sampleRows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row[0]);
            $sheet->setCellValue("B{$rowNum}", $row[1]);
            $sheet->setCellValue("C{$rowNum}", $row[2]);
            $sheet->setCellValue("D{$rowNum}", $row[3]);
            $sheet->setCellValue("E{$rowNum}", $row[4]);

            $sheet->getStyle("A{$rowNum}:E{$rowNum}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}:E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowNum)->setRowHeight(22);
            $rowNum++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);

        // 2. REFERENCE SHEET: Available Classes in School
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Available Classes');

        $refSheet->setCellValue('A1', 'Available Classes / Sections in ' . ($school->school_name ?? 'School'));
        $refSheet->mergeCells('A1:C1');
        $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor(new Color('1E293B'));

        $refSheet->setCellValue('A3', 'Class Name (Exact)');
        $refSheet->setCellValue('B3', 'Grade Level');
        $refSheet->setCellValue('C3', 'Current Students');

        $refSheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0EA5E9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $cRow = 4;
        foreach ($classes as $c) {
            $refSheet->setCellValue("A{$cRow}", $c->class_name);
            $refSheet->setCellValue("B{$cRow}", $c->gradeLevel->grade_name ?? 'Level');
            $refSheet->setCellValue("C{$cRow}", $c->students()->count());
            $refSheet->getStyle("A{$cRow}:C{$cRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $cRow++;
        }

        $refSheet->getColumnDimension('A')->setWidth(28);
        $refSheet->getColumnDimension('B')->setWidth(24);
        $refSheet->getColumnDimension('C')->setWidth(20);

        // Reset active sheet to main
        $spreadsheet->setActiveSheetIndex(0);

        // Export to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'std_enroll_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Import students from an uploaded spreadsheet file (.xlsx, .xls, .csv).
     */
    public function importStudentsFromSpreadsheet(string $filePath, School $school, ?SchoolClass $defaultClass = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // 1. Detect Header Row and Column Indexes
        $headerRow = 1;
        $colMap = [
            'admission_number' => null,
            'full_name' => null,
            'class_name' => null,
            'gender' => null,
            'status' => null
        ];

        // Search first 10 rows for headers
        for ($r = 1; $r <= min(10, $highestRow); $r++) {
            $rowValues = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $val = strtolower(trim((string) $sheet->getCell("{$col}{$r}")->getValue()));
                if (!empty($val)) {
                    $rowValues[$col] = $val;
                }
            }

            foreach ($rowValues as $col => $val) {
                if (in_array($val, ['admission number', 'admission number *', 'admission_number', 'admission no', 'admission_no', 'student id', 'student_id', 'id number', 'id'])) {
                    $colMap['admission_number'] = $col;
                } elseif (in_array($val, ['student full name', 'student full name *', 'full name', 'full name *', 'full_name', 'student name', 'name'])) {
                    $colMap['full_name'] = $col;
                } elseif (in_array($val, ['class / batch name', 'class / batch name *', 'class name', 'class_name', 'class', 'batch', 'section'])) {
                    $colMap['class_name'] = $col;
                } elseif (in_array($val, ['gender', 'sex'])) {
                    $colMap['gender'] = $col;
                } elseif (in_array($val, ['status', 'enrollment status', 'active'])) {
                    $colMap['status'] = $col;
                }
            }

            if ($colMap['admission_number'] && $colMap['full_name']) {
                $headerRow = $r;
                break;
            }
        }

        // Fallback default columns if headers weren't named exactly
        if (!$colMap['admission_number']) $colMap['admission_number'] = 'A';
        if (!$colMap['full_name']) $colMap['full_name'] = 'B';
        if (!$colMap['class_name']) $colMap['class_name'] = 'C';
        if (!$colMap['gender']) $colMap['gender'] = 'D';
        if (!$colMap['status']) $colMap['status'] = 'E';

        // Preload school classes for quick matching
        $classes = SchoolClass::where('school_id', $school->school_id)->get();
        $classByName = [];
        foreach ($classes as $c) {
            $classByName[strtolower(trim($c->class_name))] = $c;
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // 2. Iterate Data Rows
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $admCell = $colMap['admission_number'] . $r;
            $nameCell = $colMap['full_name'] . $r;

            $admissionNo = trim((string) $sheet->getCell($admCell)->getValue());
            $fullName = trim((string) $sheet->getCell($nameCell)->getValue());

            // Skip empty rows
            if (empty($admissionNo) && empty($fullName)) {
                continue;
            }

            // Validation: Admission number and Name required
            if (empty($admissionNo) || empty($fullName)) {
                $skippedCount++;
                $errors[] = "Row {$r}: Skipped due to missing Admission Number or Full Name.";
                continue;
            }

            // Resolve target Class (OPTIONAL)
            $targetClassId = null;
            if ($defaultClass) {
                $targetClassId = $defaultClass->id;
            } else {
                $classNameRaw = trim((string) $sheet->getCell($colMap['class_name'] . $r)->getValue());
                $classNameKey = strtolower($classNameRaw);

                if (!empty($classNameKey) && isset($classByName[$classNameKey])) {
                    $targetClassId = $classByName[$classNameKey]->id;
                } elseif (!empty($classNameKey)) {
                    // Try partial match
                    foreach ($classByName as $k => $cObj) {
                        if (str_contains($k, $classNameKey) || str_contains($classNameKey, $k)) {
                            $targetClassId = $cObj->id;
                            break;
                        }
                    }

                    // If specified but could not match, keep targetClassId null and record notice
                    if (!$targetClassId) {
                        $errors[] = "Row {$r} ({$fullName}): Class '{$classNameRaw}' was not found. Enrolled as Unassigned.";
                    }
                }
                // If classNameRaw is empty, targetClassId remains null (Unassigned)
            }

            // Extract Gender
            $gender = 'Other';
            if ($colMap['gender']) {
                $genderRaw = strtolower(trim((string) $sheet->getCell($colMap['gender'] . $r)->getValue()));
                if (in_array($genderRaw, ['m', 'male', 'boy'])) {
                    $gender = 'Male';
                } elseif (in_array($genderRaw, ['f', 'female', 'girl'])) {
                    $gender = 'Female';
                }
            }

            // Extract Status
            $status = 'ACTIVE';
            if ($colMap['status']) {
                $statusRaw = strtoupper(trim((string) $sheet->getCell($colMap['status'] . $r)->getValue()));
                if (in_array($statusRaw, ['INACTIVE', 'SUSPENDED', 'ALUMNI', 'WITHDRAWN'])) {
                    $status = $statusRaw;
                }
            }

            // 3. Upsert Student Record
            $existing = Student::where('school_id', $school->school_id)
                ->where('admission_number', $admissionNo)
                ->first();

            if ($existing) {
                $existing->update([
                    'class_id' => $targetClassId,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'status' => $status
                ]);
                $updatedCount++;
            } else {
                Student::create([
                    'school_id' => $school->school_id,
                    'class_id' => $targetClassId,
                    'admission_number' => $admissionNo,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'status' => $status
                ]);
                $createdCount++;
            }
        }

        return [
            'success' => true,
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'total' => $createdCount + $updatedCount,
            'errors' => $errors
        ];
    }
}
