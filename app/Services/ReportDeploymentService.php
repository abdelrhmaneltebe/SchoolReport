<?php

namespace App\Services;

use App\Models\ReportTemplate;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use ZipArchive;

class ReportDeploymentService
{
    protected string $runnerJarPath;

    public function __construct()
    {
        $this->runnerJarPath = base_path('reporting-engine/bin/jasper-runner.jar');
    }

    /**
     * Ensure storage directories for this template exist.
     */
    public function ensurePackageDirectories(ReportTemplate $template): array
    {
        $baseDir = $template->getDeploymentDirectory();
        $subDir = $template->getSubreportsDirectory();
        $imgDir = $template->getAssetsDirectory();

        if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
        if (!is_dir($subDir)) mkdir($subDir, 0755, true);
        if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);

        // Store relative path in model if not set
        if (!$template->deployment_path) {
            $relPath = str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $baseDir);
            $template->deployment_path = str_replace('\\', '/', $relPath);
            $template->save();
        }

        return [
            'root' => $baseDir,
            'subreports' => $subDir,
            'images' => $imgDir,
        ];
    }

    /**
     * Deploy a package from ZIP archive or individual file uploads.
     */
    public function deployPackage(ReportTemplate $template, array $inputs): array
    {
        $dirs = $this->ensurePackageDirectories($template);
        $rootDir = $dirs['root'];
        $subDir = $dirs['subreports'];
        $imgDir = $dirs['images'];

        $deployedMain = null;
        $deployedSubreports = [];
        $deployedAssets = [];

        // 1. Handle ZIP Archive extraction
        if (!empty($inputs['zip_package']) && $inputs['zip_package'] instanceof UploadedFile) {
            $zip = new ZipArchive();
            $zipPath = $inputs['zip_package']->getRealPath();

            if ($zip->open($zipPath) === true) {
                // Extract directly into deployment root
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $fileinfo = pathinfo($filename);
                    $ext = strtolower($fileinfo['extension'] ?? '');

                    // Ignore macOS resource forks or hidden files
                    if (str_starts_with($filename, '__MACOSX/') || str_starts_with($fileinfo['basename'] ?? '', '.')) {
                        continue;
                    }

                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
                        $targetPath = $imgDir . DIRECTORY_SEPARATOR . $fileinfo['basename'];
                        copy("zip://{$zipPath}#{$filename}", $targetPath);
                        $deployedAssets[] = $fileinfo['basename'];
                    } elseif (in_array($ext, ['jrxml', 'jasper'])) {
                        // If file or folder contains 'subreport', put in subreports
                        if (str_contains(strtolower($filename), 'subreport') || str_contains(strtolower($filename), 'sub_')) {
                            $targetPath = $subDir . DIRECTORY_SEPARATOR . $fileinfo['basename'];
                            copy("zip://{$zipPath}#{$filename}", $targetPath);
                            $deployedSubreports[] = $fileinfo['basename'];
                        } else {
                            $targetPath = $rootDir . DIRECTORY_SEPARATOR . $fileinfo['basename'];
                            copy("zip://{$zipPath}#{$filename}", $targetPath);
                            if (!$deployedMain && $ext === 'jrxml') {
                                $deployedMain = $fileinfo['basename'];
                            }
                        }
                    }
                }
                $zip->close();
            }
        }

        // 2. Handle Individual Main Report File upload
        if (!empty($inputs['template_file']) && $inputs['template_file'] instanceof UploadedFile) {
            $file = $inputs['template_file'];
            $name = $file->getClientOriginalName();
            $file->move($rootDir, $name);
            $deployedMain = $name;
        }

        // 3. Handle Individual Subreports upload
        if (!empty($inputs['subreport_files']) && is_array($inputs['subreport_files'])) {
            foreach ($inputs['subreport_files'] as $sub) {
                if ($sub instanceof UploadedFile) {
                    $name = $sub->getClientOriginalName();
                    $sub->move($subDir, $name);
                    $deployedSubreports[] = $name;
                }
            }
        }

        // 4. Handle Individual Images / Media Assets upload
        if (!empty($inputs['asset_files']) && is_array($inputs['asset_files'])) {
            foreach ($inputs['asset_files'] as $asset) {
                if ($asset instanceof UploadedFile) {
                    $name = $asset->getClientOriginalName();
                    $asset->move($imgDir, $name);
                    $deployedAssets[] = $name;
                }
            }
        }

        // Re-scan directory to capture all files
        $scanned = $this->scanDirectoryFiles($template);

        if ($scanned['main_file']) {
            $template->template_file = $scanned['main_file'];
        }
        $template->subreports = array_values(array_unique(array_merge($template->subreports ?? [], $scanned['subreports'])));
        $template->assets = array_values(array_unique(array_merge($template->assets ?? [], $scanned['assets'])));

        // Automatically compile all jrxml files
        $compilationResults = $this->compileAll($template);
        $template->is_compiled = !empty($compilationResults['compiled']);
        $template->save();

        return [
            'template' => $template,
            'main_file' => $template->template_file,
            'subreports' => $template->subreports,
            'assets' => $template->assets,
            'compilation' => $compilationResults,
        ];
    }

    /**
     * Scan deployed files in template directory.
     */
    public function scanDirectoryFiles(ReportTemplate $template): array
    {
        $dirs = $this->ensurePackageDirectories($template);
        $rootDir = $dirs['root'];
        $subDir = $dirs['subreports'];
        $imgDir = $dirs['images'];

        $mainFile = $template->template_file;
        if (!$mainFile || !file_exists($rootDir . DIRECTORY_SEPARATOR . $mainFile)) {
            $candidates = glob($rootDir . DIRECTORY_SEPARATOR . '*.jrxml');
            if (!empty($candidates)) {
                $mainFile = basename($candidates[0]);
            }
        }

        $subreports = [];
        if (is_dir($subDir)) {
            foreach (glob($subDir . DIRECTORY_SEPARATOR . '*.{jrxml,jasper}', GLOB_BRACE) as $sf) {
                $subreports[] = basename($sf);
            }
        }

        $assets = [];
        if (is_dir($imgDir)) {
            foreach (glob($imgDir . DIRECTORY_SEPARATOR . '*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE) as $af) {
                $assets[] = basename($af);
            }
        }

        return [
            'main_file' => $mainFile,
            'subreports' => array_values(array_unique($subreports)),
            'assets' => array_values(array_unique($assets)),
        ];
    }

    /**
     * Get structured metadata and thumbnail previews for all deployed assets.
     */
    public function getDeployedAssetsMetadata(ReportTemplate $template): array
    {
        $dirs = $this->ensurePackageDirectories($template);
        $rootDir = $dirs['root'];
        $subDir = $dirs['subreports'];
        $imgDir = $dirs['images'];

        // Main template details
        $mainDetails = null;
        if ($template->template_file && file_exists($rootDir . DIRECTORY_SEPARATOR . $template->template_file)) {
            $mainPath = $rootDir . DIRECTORY_SEPARATOR . $template->template_file;
            $jasperPath = preg_replace('/\.jrxml$/i', '.jasper', $mainPath);
            $mainDetails = [
                'filename' => $template->template_file,
                'filesize' => $this->formatFileSize(filesize($mainPath)),
                'updated_at' => date('Y-m-d H:i:s', filemtime($mainPath)),
                'is_compiled' => file_exists($jasperPath) && filemtime($jasperPath) >= filemtime($mainPath),
                'compiled_file' => file_exists($jasperPath) ? basename($jasperPath) : null,
            ];
        }

        // Subreports
        $subreports = [];
        if (is_dir($subDir)) {
            $jrxmlSubreports = glob($subDir . DIRECTORY_SEPARATOR . '*.jrxml');
            foreach ($jrxmlSubreports as $s) {
                $bName = basename($s);
                $jPath = preg_replace('/\.jrxml$/i', '.jasper', $s);
                $subreports[] = [
                    'filename' => $bName,
                    'filesize' => $this->formatFileSize(filesize($s)),
                    'updated_at' => date('Y-m-d H:i:s', filemtime($s)),
                    'is_compiled' => file_exists($jPath) && filemtime($jPath) >= filemtime($s),
                    'compiled_file' => file_exists($jPath) ? basename($jPath) : null,
                ];
            }
        }

        // Images with base64 data URIs for immediate UI preview
        $images = [];
        if (is_dir($imgDir)) {
            $imgFiles = glob($imgDir . DIRECTORY_SEPARATOR . '*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);
            foreach ($imgFiles as $img) {
                $bName = basename($img);
                $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'gif' => 'image/gif',
                    default => 'application/octet-stream',
                };

                $dataUri = null;
                $dimStr = '';
                if (filesize($img) < 3 * 1024 * 1024) { // Only base64 encode under 3MB
                    $dataUri = "data:{$mime};base64," . base64_encode(file_get_contents($img));
                    $dims = @getimagesize($img);
                    if ($dims) {
                        $dimStr = "{$dims[0]}×{$dims[1]} px";
                    }
                }

                // Suggest Jasper parameter mapping
                $paramHint = 'IMAGE_DIR + "' . $bName . '"';
                if (str_contains(strtolower($bName), 'logo')) {
                    $paramHint = '$P{P_LOGO_PATH}';
                } elseif (str_contains(strtolower($bName), 'sign')) {
                    $paramHint = '$P{P_PRINCIPAL_SIGNATURE}';
                } elseif (str_contains(strtolower($bName), 'stamp') || str_contains(strtolower($bName), 'seal')) {
                    $paramHint = '$P{P_STAMP_PATH}';
                }

                $images[] = [
                    'filename' => $bName,
                    'filesize' => $this->formatFileSize(filesize($img)),
                    'dimensions' => $dimStr,
                    'data_uri' => $dataUri,
                    'param_hint' => $paramHint,
                    'updated_at' => date('Y-m-d H:i:s', filemtime($img)),
                ];
            }
        }

        return [
            'directory' => $rootDir,
            'main' => $mainDetails,
            'subreports' => $subreports,
            'images' => $images,
            'total_files' => ($mainDetails ? 1 : 0) + count($subreports) + count($images),
        ];
    }

    /**
     * Compile a single JRXML file to .jasper via Java runner.
     */
    public function compileJrxmlFile(string $jrxmlPath): bool
    {
        if (!file_exists($jrxmlPath)) {
            return false;
        }

        $dummyOutput = storage_path('app/temp_' . uniqid('compile_') . '.pdf');

        $process = new Process([
            'java',
            '-jar',
            $this->runnerJarPath,
            '--template', $jrxmlPath,
            '--output', $dummyOutput,
            '--format', 'pdf'
        ]);

        $process->setTimeout(60);
        $process->run();

        if (file_exists($dummyOutput)) {
            @unlink($dummyOutput);
        }

        $jasperPath = preg_replace('/\.jrxml$/i', '.jasper', $jrxmlPath);
        return file_exists($jasperPath);
    }

    /**
     * Compile all JRXML files (main + subreports) in the template package.
     */
    public function compileAll(ReportTemplate $template): array
    {
        $dirs = $this->ensurePackageDirectories($template);
        $compiled = [];
        $failed = [];

        // Compile main jrxml
        if ($template->template_file) {
            $mainPath = $dirs['root'] . DIRECTORY_SEPARATOR . $template->template_file;
            if (file_exists($mainPath) && str_ends_with(strtolower($mainPath), '.jrxml')) {
                if ($this->compileJrxmlFile($mainPath)) {
                    $compiled[] = $template->template_file;
                } else {
                    $failed[] = $template->template_file;
                }
            }
        }

        // Compile all subreports
        if (is_dir($dirs['subreports'])) {
            foreach (glob($dirs['subreports'] . DIRECTORY_SEPARATOR . '*.jrxml') as $sub) {
                if ($this->compileJrxmlFile($sub)) {
                    $compiled[] = 'subreports/' . basename($sub);
                } else {
                    $failed[] = 'subreports/' . basename($sub);
                }
            }
        }

        return [
            'compiled' => $compiled,
            'failed' => $failed,
            'success' => empty($failed),
        ];
    }

    /**
     * Delete an individual asset or subreport file.
     */
    public function deleteAsset(ReportTemplate $template, string $fileName, string $category): bool
    {
        $dirs = $this->ensurePackageDirectories($template);
        $targetDir = match($category) {
            'images' => $dirs['images'],
            'subreports' => $dirs['subreports'],
            default => $dirs['root'],
        };

        $filePath = $targetDir . DIRECTORY_SEPARATOR . basename($fileName);
        if (file_exists($filePath)) {
            @unlink($filePath);
            // Also unlink matching .jasper if deleting a .jrxml
            if (str_ends_with(strtolower($fileName), '.jrxml')) {
                $jPath = preg_replace('/\.jrxml$/i', '.jasper', $filePath);
                if (file_exists($jPath)) @unlink($jPath);
            }

            // Sync model array
            $this->scanDirectoryFiles($template);
            return true;
        }

        return false;
    }

    /**
     * Download entire report deployment package as a ZIP archive.
     */
    public function createBundleZip(ReportTemplate $template): string
    {
        $dirs = $this->ensurePackageDirectories($template);
        $zipName = "ReportPackage_{$template->report_code}_" . date('Ymd_His') . ".zip";
        $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . 'temp_' . $zipName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add root files (.jrxml, .jasper)
            foreach (glob($dirs['root'] . DIRECTORY_SEPARATOR . '*.{jrxml,jasper}', GLOB_BRACE) as $f) {
                $zip->addFile($f, basename($f));
            }

            // Add subreports
            if (is_dir($dirs['subreports'])) {
                foreach (glob($dirs['subreports'] . DIRECTORY_SEPARATOR . '*.{jrxml,jasper}', GLOB_BRACE) as $f) {
                    $zip->addFile($f, 'subreports/' . basename($f));
                }
            }

            // Add images
            if (is_dir($dirs['images'])) {
                foreach (glob($dirs['images'] . DIRECTORY_SEPARATOR . '*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE) as $f) {
                    $zip->addFile($f, 'images/' . basename($f));
                }
            }

            $zip->close();
        }

        return $zipPath;
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
