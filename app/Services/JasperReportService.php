<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class JasperReportService
{
    protected string $runnerPath;
    protected string $templatesPath;

    public function __construct()
    {
        $this->runnerPath = base_path('reporting-engine/bin/jasper-runner.jar');
        $this->templatesPath = base_path('reporting-engine/templates');
    }

    /**
     * Check whether the JasperReports execution environment (Java 17 + proc_open) is available.
     */
    public function isAvailable(): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $disabled = explode(',', (string)ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        if (in_array('proc_open', $disabled)) {
            return false;
        }

        try {
            $process = new Process(['java', '-version']);
            $process->setTimeout(3);
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compile and export a report via Java 17 JasperReports runner.
     *
     * @param string $templatePath e.g. "official_report_card_a4.jrxml" or full absolute path
     * @param array $parameters Map of P_* parameters
     * @param array $jsonData Student records & subject grades dataset
     * @param string $outputPath Target PDF destination
     * @return string Output file path
     */
    public function generateReport(string $templatePath, array $parameters, array $jsonData, string $outputPath): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                "JasperReports requires Java 17 and PHP proc_open. On shared web hosting where Java is restricted, use the high-resolution Print / PDF mode for instant report card generation."
            );
        }

        $jrxmlFile = file_exists($templatePath)
            ? $templatePath
            : $this->templatesPath . DIRECTORY_SEPARATOR . $templatePath;

        if (!file_exists($jrxmlFile)) {
            throw new \InvalidArgumentException("Jasper template not found: {$jrxmlFile}");
        }

        // Auto-detect if .jrxml was modified and recompile .jasper binary on the fly
        $candidateJrxml = str_ends_with($jrxmlFile, '.jasper')
            ? substr($jrxmlFile, 0, -7) . '.jrxml'
            : (str_ends_with($jrxmlFile, '.jrxml') ? $jrxmlFile : $jrxmlFile . '.jrxml');

        $candidateJasper = str_ends_with($jrxmlFile, '.jasper')
            ? $jrxmlFile
            : (str_ends_with($jrxmlFile, '.jrxml') ? substr($jrxmlFile, 0, -6) . '.jasper' : $jrxmlFile . '.jasper');

        if (file_exists($candidateJrxml)) {
            if (!file_exists($candidateJasper) || filemtime($candidateJrxml) > filemtime($candidateJasper)) {
                app(ReportDeploymentService::class)->compileJrxmlFile($candidateJrxml);
            }
            if (file_exists($candidateJasper)) {
                $jrxmlFile = $candidateJasper;
            }
        }

        // Auto-inject SUBREPORT_DIR and IMAGE_DIR if adjacent directories exist
        $parentDir = dirname($jrxmlFile);
        $subDir = $parentDir . DIRECTORY_SEPARATOR . 'subreports' . DIRECTORY_SEPARATOR;
        $imgDir = $parentDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

        if (is_dir($subDir) && !isset($parameters['SUBREPORT_DIR'])) {
            $parameters['SUBREPORT_DIR'] = $subDir;
            $parameters['P_SUBREPORT_DIR'] = $subDir;
        }

        if (is_dir($imgDir) && !isset($parameters['IMAGE_DIR'])) {
            $parameters['IMAGE_DIR'] = $imgDir;
            $parameters['P_IMAGE_DIR'] = $imgDir;

            // Auto-detect logo and signature paths if not explicitly defined
            if (empty($parameters['P_LOGO_PATH'])) {
                $schoolId = $parameters['school_id'] ?? $parameters['P_SCHOOL_ID'] ?? session('active_school_id') ?? null;
                if ($schoolId) {
                    $school = \App\Models\School::where('school_id', $schoolId)->first();
                    if ($school && $school->getLogoDiskPath()) {
                        $parameters['P_LOGO_PATH'] = $school->getLogoDiskPath();
                    }
                }
                if (empty($parameters['P_LOGO_PATH'])) {
                    $logoCandidates = glob($imgDir . '*{logo,crest,emblem}*.{png,jpg,jpeg,svg,webp}', GLOB_BRACE);
                    if (!empty($logoCandidates)) {
                        $parameters['P_LOGO_PATH'] = $logoCandidates[0];
                    }
                }
            }

            if (empty($parameters['P_PRINCIPAL_SIGNATURE'])) {
                $sigCandidates = glob($imgDir . '*{sign,sig,principal}*.{png,jpg,jpeg}', GLOB_BRACE);
                if (!empty($sigCandidates)) {
                    $parameters['P_PRINCIPAL_SIGNATURE'] = $sigCandidates[0];
                }
            }
        }

        // Ensure target directory exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Normalize any Windows backslashes in path parameters to forward slashes for cross-platform Java compatibility
        foreach ($parameters as $key => $val) {
            if (is_string($val) && str_contains($val, '\\')) {
                $parameters[$key] = str_replace('\\', '/', $val);
            }
        }

        // Write temporary JSON data payload & parameters payload to avoid shell escape issues
        $tempDataFile = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_' . uniqid('jasper_') . '.json');
        $tempParamsFile = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_params_' . uniqid('jasper_') . '.json');
        
        file_put_contents($tempDataFile, json_encode($jsonData));
        file_put_contents($tempParamsFile, json_encode($parameters));

        // Build runner command: java -jar jasper-runner.jar --template ... --params ... [--data ... | --db-url ...]
        $cmd = [
            'java',
            '-Djava.net.preferIPv4Stack=true',
            '-jar',
            $this->runnerPath,
            '--template', $jrxmlFile,
            '--params', $tempParamsFile,
            '--output', $outputPath,
            '--format', 'pdf',
        ];

        if (!empty($jsonData)) {
            // When structured JSON dataset is provided by PHP, prioritize it directly to avoid unnecessary remote DB network calls
            $cmd[] = '--data';
            $cmd[] = $tempDataFile;
        } else {
            // Fallback to direct JDBC connection only when no precomputed data payload is supplied
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');
            $dbName = config('database.connections.mysql.database', 'u118583162_school_reports');
            $dbUser = config('database.connections.mysql.username', 'root');
            $dbPass = config('database.connections.mysql.password', '');
            $dbUrl = "jdbc:mysql://{$dbHost}:{$dbPort}/{$dbName}?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC&characterEncoding=UTF-8";

            $cmd[] = '--db-url';
            $cmd[] = $dbUrl;
            $cmd[] = '--db-user';
            $cmd[] = (string)$dbUser;
            $cmd[] = '--db-pass';
            $cmd[] = (string)$dbPass;
        }

        $process = new Process($cmd);

        $process->setTimeout(300);

        try {
            $process->run();
        } finally {
            // Clean temp payloads
            if (file_exists($tempDataFile)) {
                @unlink($tempDataFile);
            }
            if (file_exists($tempParamsFile)) {
                @unlink($tempParamsFile);
            }
        }

        if (!$process->isSuccessful()) {
            Log::error("JasperReports Execution Error: " . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        return $outputPath;
    }
}
