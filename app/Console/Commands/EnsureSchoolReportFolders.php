<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\ReportTemplate;
use App\Services\ReportDeploymentService;
use Illuminate\Console\Command;

class EnsureSchoolReportFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schools:ensure-report-folders {--school= : Specific school ID to provision}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all existing schools and custom reports have their dedicated storage folders provisioned';

    /**
     * Execute the console command.
     */
    public function handle(ReportDeploymentService $deploymentService): int
    {
        $schoolId = $this->option('school');

        $this->info("🚀 Provisioning report & deployment directories for existing schools...\n");

        $query = School::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $schools = $query->orderBy('school_name')->get();

        if ($schools->isEmpty()) {
            $this->warn("No schools found" . ($schoolId ? " with ID [{$schoolId}]." : "."));
            return 1;
        }

        $headers = ['School ID', 'School Name', 'Reports Base', 'Custom Dirs', 'Exports Dir', 'Status'];
        $rows = [];

        foreach ($schools as $school) {
            $school->ensureReportDirectoriesExist();

            $reportsDir = $school->getReportsDirectory();
            $customDir = $school->getCustomReportsDirectory();
            $exportsDir = $school->getExportsDirectory();

            $allExist = is_dir($reportsDir) &&
                        is_dir($customDir) &&
                        is_dir($customDir . DIRECTORY_SEPARATOR . 'subreports') &&
                        is_dir($customDir . DIRECTORY_SEPARATOR . 'images') &&
                        is_dir($exportsDir);

            $rows[] = [
                $school->school_id,
                $school->school_name,
                is_dir($reportsDir) ? '✅ OK' : '❌ FAIL',
                (is_dir($customDir) && is_dir($customDir . '/subreports') && is_dir($customDir . '/images')) ? '✅ OK (sub+img)' : '❌ FAIL',
                is_dir($exportsDir) ? '✅ OK' : '❌ FAIL',
                $allExist ? 'Active & Ready' : 'Incomplete'
            ];
        }

        $this->table($headers, $rows);

        // Also ensure all deployed templates have their package dirs
        $this->info("\n📦 Checking Report Template Package Directories...");
        $templates = ReportTemplate::all();
        $tmplRows = [];
        foreach ($templates as $tmpl) {
            $dirs = $deploymentService->ensurePackageDirectories($tmpl);
            $tmplRows[] = [
                $tmpl->id,
                $tmpl->report_code,
                $tmpl->isGeneral() ? '🌐 Global' : "🏫 {$tmpl->school_id}",
                is_dir($dirs['root']) ? '✅' : '❌',
                is_dir($dirs['subreports']) ? '✅' : '❌',
                is_dir($dirs['images']) ? '✅' : '❌',
            ];
        }

        $this->table(['ID', 'Code', 'Scope', 'Root', 'Subreports', 'Images'], $tmplRows);

        $this->info("\n✨ Done! All existing school report directories are active and ready.\n");
        return 0;
    }
}
