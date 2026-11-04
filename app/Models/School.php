<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The "booted" method of the model.
     * Automatically provisions report directories whenever a school is created.
     */
    protected static function booted()
    {
        static::created(function (School $school) {
            $school->ensureReportDirectoriesExist();
        });
    }

    /**
     * Get the base reports directory for this school.
     */
    public function getReportsDirectory(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'schools' . DIRECTORY_SEPARATOR . $this->school_id . DIRECTORY_SEPARATOR . 'reports');
    }

    /**
     * Get the export output directory for this school.
     */
    public function getExportsDirectory(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'schools' . DIRECTORY_SEPARATOR . $this->school_id . DIRECTORY_SEPARATOR . 'exports');
    }

    /**
     * Get customized reports directory for this school.
     */
    public function getCustomReportsDirectory(): string
    {
        return $this->getReportsDirectory() . DIRECTORY_SEPARATOR . 'custom';
    }

    /**
     * Ensure all necessary reports, subreports, images, and export directories exist.
     */
    public function ensureReportDirectoriesExist(): void
    {
        $baseReports = $this->getReportsDirectory();
        $customDir = $this->getCustomReportsDirectory();
        $subreportsDir = $customDir . DIRECTORY_SEPARATOR . 'subreports';
        $imagesDir = $customDir . DIRECTORY_SEPARATOR . 'images';
        $exportsDir = $this->getExportsDirectory();

        $dirs = [
            $baseReports,
            $customDir,
            $subreportsDir,
            $imagesDir,
            $exportsDir,
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Get absolute filesystem path to the school's logo if it exists.
     */
    public function getLogoDiskPath(): ?string
    {
        if ($this->logo_path) {
            // Check absolute or storage-relative path
            if (file_exists($this->logo_path)) {
                return $this->logo_path;
            }
            $storageCandidate = storage_path('app' . DIRECTORY_SEPARATOR . ltrim($this->logo_path, '/\\'));
            if (file_exists($storageCandidate)) {
                return $storageCandidate;
            }
            $publicCandidate = public_path(ltrim($this->logo_path, '/\\'));
            if (file_exists($publicCandidate)) {
                return $publicCandidate;
            }
        }

        // Check default report images directory for logo file
        $customImagesDir = $this->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'images';
        if (is_dir($customImagesDir)) {
            $candidates = glob($customImagesDir . DIRECTORY_SEPARATOR . 'logo*.{png,jpg,jpeg,svg,webp}', GLOB_BRACE);
            if (!empty($candidates)) {
                return $candidates[0];
            }
        }

        return null;
    }

    /**
     * Check if a custom logo is uploaded for this school.
     */
    public function hasCustomLogo(): bool
    {
        return $this->getLogoDiskPath() !== null;
    }

    /**
     * Get URL to view/serve the school logo with automatic cache-busting.
     */
    public function getLogoUrl(): string
    {
        $diskPath = $this->getLogoDiskPath();
        $version = $diskPath && file_exists($diskPath) ? filemtime($diskPath) : ($this->updated_at ? $this->updated_at->timestamp : time());
        return route('schools.logo', $this->school_id) . '?v=' . $version;
    }

    /**
     * Get base64 Data URI for inline report embedding (e.g. batch printing).
     */
    public function getLogoBase64(): ?string
    {
        $path = $this->getLogoDiskPath();
        if (!$path || !file_exists($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    /**
     * Save an uploaded logo file to school's storage and reports directories.
     */
    public function saveLogo(\Illuminate\Http\UploadedFile $file): string
    {
        $this->ensureReportDirectoriesExist();

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = 'logo_' . time() . '.' . $ext;

        // Save in school's custom report images directory
        $imagesDir = $this->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'images';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }

        // Remove old logo files in this directory
        foreach (glob($imagesDir . DIRECTORY_SEPARATOR . 'logo*.*') as $old) {
            if (is_file($old)) @unlink($old);
        }

        $targetPath = $imagesDir . DIRECTORY_SEPARATOR . $filename;
        $file->move($imagesDir, $filename);

        // Also create a static 'logo.{ext}' link/copy for standard Jasper convention
        $standardPath = $imagesDir . DIRECTORY_SEPARATOR . 'logo.' . $ext;
        if (!file_exists($standardPath) || $standardPath !== $targetPath) {
            @copy($targetPath, $standardPath);
        }

        // Relative path to store in DB
        $relPath = 'schools/' . $this->school_id . '/reports/custom/images/' . $filename;
        $this->logo_path = $relPath;
        $this->save();

        return $targetPath;
    }

    /**
     * Delete custom logo and revert to default.
     */
    public function deleteLogo(): void
    {
        $path = $this->getLogoDiskPath();
        if ($path && file_exists($path)) {
            @unlink($path);
        }

        $imagesDir = $this->getCustomReportsDirectory() . DIRECTORY_SEPARATOR . 'images';
        if (is_dir($imagesDir)) {
            foreach (glob($imagesDir . DIRECTORY_SEPARATOR . 'logo*.*') as $f) {
                if (is_file($f)) @unlink($f);
            }
        }

        $this->logo_path = null;
        $this->save();
    }

    public function attendancePolicy()
    {
        return $this->hasOne(SchoolAttendancePolicy::class, 'school_id', 'school_id');
    }

    public function gradingSystems()
    {
        return $this->hasMany(SchoolGradingSystem::class, 'school_id', 'school_id');
    }

    public function divisions()
    {
        return $this->hasMany(SchoolDivision::class, 'school_id', 'school_id')->orderBy('display_order');
    }

    public function gradeLevels()
    {
        return $this->hasMany(SchoolGradeLevel::class, 'school_id', 'school_id')->orderBy('display_order');
    }

    public function masterSubjects()
    {
        return $this->hasMany(MasterSubject::class, 'school_id', 'school_id');
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'school_id', 'school_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'school_id', 'school_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'school_id', 'school_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'school_user',
            'school_id',
            'user_id',
            'school_id',
            'id'
        )->withTimestamps();
    }

    public function adminUser()
    {
        return $this->hasOne(User::class, 'school_id', 'school_id')->where('role', 'SCHOOL_ADMIN');
    }
}
