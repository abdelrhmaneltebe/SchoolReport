<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SchoolConfigurationController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportStudioController;
use App\Http\Controllers\ExcelIngestionController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\UserManagementController;

// 1. Authentication & Session Portal
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/quick-login/{role}', [AuthController::class, 'quickLogin'])->name('quick_login');

// 2. Super Administrator Hub (Multi-School Provisioning & User System)
Route::prefix('admin')->name('admin.')->group(function () {
    // School Management
    Route::get('/schools', [SchoolManagementController::class, 'index'])->name('schools.index');
    Route::post('/schools', [SchoolManagementController::class, 'store'])->name('schools.store');
    Route::post('/schools/{school}/update', [SchoolManagementController::class, 'update'])->name('schools.update');
    Route::delete('/schools/{school}/logo', [SchoolManagementController::class, 'removeLogo'])->name('schools.logo.delete');
    Route::get('/schools/switch/{school_id}', [SchoolManagementController::class, 'switchSchool'])->name('schools.switch');

    // User System Management
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/update', [UserManagementController::class, 'update'])->name('users.update_post');
    Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

// School Profile, Branding & Logo Management (Accessible to School Admin & Super Admin)
Route::get('/schools/{school_id}/logo', [\App\Http\Controllers\SchoolProfileController::class, 'showLogo'])->name('schools.logo');
Route::get('/school/profile', [\App\Http\Controllers\SchoolProfileController::class, 'profile'])->name('school.profile');
Route::post('/school/profile', [\App\Http\Controllers\SchoolProfileController::class, 'update'])->name('school.profile.update');
Route::post('/school/profile/remove-logo', [\App\Http\Controllers\SchoolProfileController::class, 'removeLogo'])->name('school.profile.remove_logo');

// 0. Public Landing Portal & Institutional Demo
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/request-demo', [HomeController::class, 'requestDemo'])->name('request_demo');

// 3. Core Operational App (Scoped to Active / Individual School)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Pillar 1: Attendance System
Route::get('/configuration/attendance', [SchoolConfigurationController::class, 'attendance'])->name('config.attendance');
Route::post('/configuration/attendance/update', [SchoolConfigurationController::class, 'updateAttendance'])->name('config.attendance.update');

// Pillar 2: Grading System & Templates
Route::get('/configuration/grading', [SchoolConfigurationController::class, 'grading'])->name('config.grading');
Route::post('/configuration/grading/activate/{rule}', [SchoolConfigurationController::class, 'activateTemplate'])->name('config.grading.activate_template');
Route::post('/configuration/grading/update-hierarchy', [SchoolConfigurationController::class, 'updateGradingHierarchy'])->name('config.grading.update_hierarchy');

// Add & Remove Periods & Categories dynamically
Route::post('/configuration/grading/periods/add', [SchoolConfigurationController::class, 'addPeriod'])->name('config.grading.periods.add');
Route::match(['get', 'post', 'delete'], '/configuration/grading/periods/{id}/delete', [SchoolConfigurationController::class, 'deletePeriod'])->name('config.grading.periods.delete');
Route::post('/configuration/grading/categories/add', [SchoolConfigurationController::class, 'addCategory'])->name('config.grading.categories.add');
Route::match(['get', 'post', 'delete'], '/configuration/grading/categories/{id}/delete', [SchoolConfigurationController::class, 'deleteCategory'])->name('config.grading.categories.delete');

// Template Category List (Bank) & Period Integration
Route::post('/configuration/grading/template-categories/add', [SchoolConfigurationController::class, 'addTemplateCategory'])->name('config.grading.template_categories.add');
Route::post('/configuration/grading/template-categories/{id}/update', [SchoolConfigurationController::class, 'updateTemplateCategory'])->name('config.grading.template_categories.update');
Route::match(['get', 'post', 'delete'], '/configuration/grading/template-categories/{id}/delete', [SchoolConfigurationController::class, 'deleteTemplateCategory'])->name('config.grading.template_categories.delete');
Route::post('/configuration/grading/template-categories/use-in-period', [SchoolConfigurationController::class, 'useCategoryInPeriod'])->name('config.grading.template_categories.use_in_period');
Route::post('/configuration/grading/template-categories/apply-to-periods', [SchoolConfigurationController::class, 'applyTemplateCategoriesToPeriods'])->name('config.grading.template_categories.apply_to_periods');

// Apply Grading System into Subjects
Route::post('/configuration/grading/apply-to-subjects', [SchoolConfigurationController::class, 'applyGradingSystemToSubjects'])->name('config.grading.apply_to_subjects');
Route::match(['get', 'post', 'delete'], '/configuration/grading/subjects/{id}/reset-grading', [SchoolConfigurationController::class, 'resetSubjectGrading'])->name('config.grading.subjects.reset');

// School Levels & Educational Structure (Pillar: Levels, Divisions, Classes)
Route::get('/configuration/levels', [SchoolConfigurationController::class, 'levels'])->name('config.levels');
Route::post('/configuration/levels', [SchoolConfigurationController::class, 'storeLevel'])->name('config.levels.store');
Route::post('/configuration/levels/batch', [SchoolConfigurationController::class, 'batchCreateLevels'])->name('config.levels.batch');
Route::post('/configuration/levels/{id}/update', [SchoolConfigurationController::class, 'updateLevel'])->name('config.levels.update');
Route::match(['get', 'post', 'delete'], '/configuration/levels/{id}/delete', [SchoolConfigurationController::class, 'deleteLevel'])->name('config.levels.delete');

// Educational Stages / Divisions
Route::post('/configuration/divisions', [SchoolConfigurationController::class, 'storeDivision'])->name('config.divisions.store');
Route::match(['get', 'post', 'delete'], '/configuration/divisions/{id}/delete', [SchoolConfigurationController::class, 'deleteDivision'])->name('config.divisions.delete');

// Classes under Levels (Batch Profile)
Route::get('/configuration/classes/{id}', [SchoolConfigurationController::class, 'classProfile'])->name('config.classes.profile');
Route::get('/configuration/classes/{id}/gradebook', [SchoolConfigurationController::class, 'classGradebook'])->name('config.classes.gradebook');
Route::post('/configuration/classes/{id}/gradebook/save', [SchoolConfigurationController::class, 'saveBatchGradebook'])->name('config.classes.gradebook.save');
Route::post('/configuration/classes/{id}/gradebook/save-single', [SchoolConfigurationController::class, 'saveSingleStudentGradebook'])->name('config.classes.gradebook.save_single');
Route::get('/configuration/classes/{id}/gradebook/template', [SchoolConfigurationController::class, 'downloadGradebookTemplate'])->name('config.classes.gradebook.template');
Route::post('/configuration/classes/{id}/gradebook/upload', [SchoolConfigurationController::class, 'uploadGradebookSheet'])->name('config.classes.gradebook.upload');
Route::post('/configuration/classes/{id}/update', [SchoolConfigurationController::class, 'updateClass'])->name('config.classes.update');
Route::post('/configuration/classes/{id}/enroll-student', [SchoolConfigurationController::class, 'enrollStudentInClass'])->name('config.classes.enroll_student');
Route::post('/configuration/classes', [SchoolConfigurationController::class, 'storeClass'])->name('config.classes.store');
Route::match(['get', 'post', 'delete'], '/configuration/classes/{id}/delete', [SchoolConfigurationController::class, 'deleteClass'])->name('config.classes.delete');

// Pillar 3: Master Subjects & Curriculum Studio (Levels & Batches)
Route::get('/configuration/class-subjects', [SchoolConfigurationController::class, 'classSubjects'])->name('config.class_subjects');
Route::get('/configuration/class-subjects/{id}', [SchoolConfigurationController::class, 'classSubjectProfile'])->name('config.class_subjects.profile');
Route::get('/configuration/courses-classes/{id}', [SchoolConfigurationController::class, 'classSubjectProfile'])->name('config.courses_classes.profile');
Route::post('/configuration/class-subjects/{id}/update', [SchoolConfigurationController::class, 'updateClassSubject'])->name('config.class_subjects.update');
Route::post('/configuration/class-subjects/{id}/record-grade', [SchoolConfigurationController::class, 'recordClassSubjectGrade'])->name('config.class_subjects.record_grade');
Route::post('/configuration/class-subjects/{id}/enrollments', [SchoolConfigurationController::class, 'updateCourseEnrollments'])->name('config.class_subjects.enrollments.update');
Route::post('/configuration/class-subjects/{id}/enroll-single', [SchoolConfigurationController::class, 'toggleStudentCourseEnrollment'])->name('config.class_subjects.enrollment.toggle');
Route::post('/configuration/class-subjects/assign', [SchoolConfigurationController::class, 'assignSubjectToClass'])->name('config.class_subjects.assign');
Route::post('/configuration/class-subjects/assign-level', [SchoolConfigurationController::class, 'assignSubjectToLevel'])->name('config.class_subjects.assign_level');
Route::match(['get', 'post', 'delete'], '/configuration/class-subjects/{id}/delete', [SchoolConfigurationController::class, 'unassignSubjectFromClass'])->name('config.class_subjects.delete');

// Master Subject Catalog (CRUD)
Route::get('/configuration/subjects/{id}', [SchoolConfigurationController::class, 'subjectProfile'])->name('config.subjects.profile');
Route::post('/configuration/subjects', [SchoolConfigurationController::class, 'storeSubject'])->name('config.subjects.store');
Route::post('/configuration/subjects/{id}/update', [SchoolConfigurationController::class, 'updateSubject'])->name('config.subjects.update');
Route::match(['get', 'post', 'delete'], '/configuration/subjects/{id}/delete', [SchoolConfigurationController::class, 'deleteSubject'])->name('config.subjects.delete');

// Student Directory & Bulk Enrollment Studio
Route::get('/configuration/students', [StudentEnrollmentController::class, 'index'])->name('config.students.index');
Route::post('/configuration/students', [StudentEnrollmentController::class, 'store'])->name('config.students.store');
Route::post('/configuration/students/{id}/update', [StudentEnrollmentController::class, 'update'])->name('config.students.update');
Route::match(['get', 'post', 'delete'], '/configuration/students/{id}/delete', [StudentEnrollmentController::class, 'destroy'])->name('config.students.delete');
Route::get('/configuration/students/template', [StudentEnrollmentController::class, 'downloadTemplate'])->name('config.students.template');
Route::post('/configuration/students/upload', [StudentEnrollmentController::class, 'uploadSheet'])->name('config.students.upload');

// Attendance Register, Daily Roll Call & Summaries
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/attendance/batch-save', [AttendanceController::class, 'saveBatch'])->name('attendance.batch_save');
Route::post('/attendance/quick-update', [AttendanceController::class, 'quickUpdate'])->name('attendance.quick_update');
Route::post('/attendance/mark-all', [AttendanceController::class, 'markAll'])->name('attendance.mark_all');
Route::get('/attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
Route::get('/attendance/export', [AttendanceController::class, 'exportCsv'])->name('attendance.export');

// Universal Reports & Analytics Studio Hub
Route::get('/reports', [ReportStudioController::class, 'index'])->name('reports.generator');
Route::post('/reports/generate', [ReportStudioController::class, 'generate'])->name('reports.generator.dispatch');
Route::post('/reports/templates', [ReportStudioController::class, 'storeTemplate'])->name('reports.templates.store');
Route::put('/reports/templates/{template}', [ReportStudioController::class, 'updateTemplate'])->name('reports.templates.update');
Route::delete('/reports/templates/{template}', [ReportStudioController::class, 'destroyTemplate'])->name('reports.templates.destroy');

// Report Package Deployment, Subreports & Asset Management
Route::get('/reports/templates/{template}/deployment', [ReportStudioController::class, 'getDeployment'])->name('reports.templates.deployment');
Route::post('/reports/templates/{template}/deploy', [ReportStudioController::class, 'deployPackage'])->name('reports.templates.deploy');
Route::post('/reports/templates/{template}/compile', [ReportStudioController::class, 'compileTemplate'])->name('reports.templates.compile');
Route::delete('/reports/templates/{template}/assets', [ReportStudioController::class, 'deleteAsset'])->name('reports.templates.assets.delete');
Route::get('/reports/templates/{template}/download-bundle', [ReportStudioController::class, 'downloadBundle'])->name('reports.templates.download-bundle');

Route::get('/reports/cards/single/{student}', [ReportStudioController::class, 'singleCard'])->name('reports.card.single');
Route::get('/reports/cards/batch/{class}', [ReportStudioController::class, 'batchCards'])->name('reports.card.batch');
Route::get('/reports/analytics/{class}', [ReportStudioController::class, 'classAnalytics'])->name('reports.analytics.class');
Route::get('/reports/export-csv/{class}', [ReportStudioController::class, 'exportCsv'])->name('reports.export.csv');

// Master Broadsheet & JasperReports
Route::get('/reports/broadsheet', [ReportCardController::class, 'broadsheet'])->name('reports.broadsheet');
Route::get('/reports/preview/{student}', [ReportCardController::class, 'previewCard'])->name('reports.preview');
Route::get('/reports/export/{student}', [ReportCardController::class, 'exportJasperPdf'])->name('reports.export.jasper');

// Excel Marksheet Ingestion
Route::get('/ingestion', [ExcelIngestionController::class, 'index'])->name('ingestion.index');
Route::get('/ingestion/template/{class}', [ExcelIngestionController::class, 'downloadTemplate'])->name('ingestion.template');
Route::post('/ingestion/preview', [ExcelIngestionController::class, 'previewUpload'])->name('ingestion.preview');
Route::post('/ingestion/commit/{class}', [ExcelIngestionController::class, 'commitUpload'])->name('ingestion.commit');
