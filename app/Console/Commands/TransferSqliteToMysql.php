<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransferSqliteToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:transfer-sqlite-to-mysql {--force : Force the transfer without prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer all data from local SQLite database to the configured remote MySQL database.';

    /**
     * Tables in dependency-safe order.
     *
     * @var array
     */
    protected array $tables = [
        'schools',
        'users',
        'school_divisions',
        'school_grade_levels',
        'school_classes',
        'school_grading_systems',
        'school_attendance_policies',
        'grading_structure_rules',
        'grading_template_categories',
        'academic_grading_periods',
        'grading_categories',
        'master_subjects',
        'class_subject_assignments',
        'students',
        'course_enrollments',
        'student_sub_scores',
        'grade_records',
        'attendance_records',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Starting Database Migration: SQLite -> MySQL");
        $this->line("Source Connection: sqlite_old (" . config('database.connections.sqlite_old.database') . ")");
        $this->line("Target Connection: mysql (" . config('database.connections.mysql.host') . ":" . config('database.connections.mysql.database') . ")");

        $sqlite = DB::connection('sqlite_old');
        $mysql = DB::connection('mysql');

        // Verify connections
        try {
            $sqlite->getPdo();
            $this->info("✓ SQLite connection verified.");
        } catch (\Throwable $e) {
            $this->error("Failed to connect to SQLite: " . $e->getMessage());
            return Command::FAILURE;
        }

        try {
            $mysql->getPdo();
            $this->info("✓ MySQL connection verified.");
        } catch (\Throwable $e) {
            $this->error("Failed to connect to MySQL: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->warn("⚠️  Disabling MySQL Foreign Key Checks for bulk insertion...");
        $mysql->statement('SET FOREIGN_KEY_CHECKS = 0;');

        $summary = [];
        $totalRowsCopied = 0;

        foreach ($this->tables as $table) {
            if (!$sqlite->getSchemaBuilder()->hasTable($table)) {
                $this->line("Table '{$table}' not found in SQLite, skipping.");
                continue;
            }

            if (!$mysql->getSchemaBuilder()->hasTable($table)) {
                $this->warn("Table '{$table}' not found in MySQL, skipping.");
                continue;
            }

            $sourceCount = $sqlite->table($table)->count();

            // Clear target table
            $mysql->table($table)->truncate();

            if ($sourceCount === 0) {
                $summary[] = [
                    'table' => $table,
                    'source' => 0,
                    'target' => 0,
                    'status' => 'EMPTY'
                ];
                $this->line("  - {$table}: 0 rows (empty)");
                continue;
            }

            // Transfer in chunks to conserve memory
            $transferredForTable = 0;
            $sqlite->table($table)->orderBy(
                $this->hasIdColumn($sqlite, $table) ? 'id' : array_keys((array) $sqlite->table($table)->first())[0]
            )->chunk(500, function ($rows) use ($mysql, $table, &$transferredForTable) {
                $insertData = [];
                foreach ($rows as $row) {
                    $insertData[] = (array) $row;
                }
                if (!empty($insertData)) {
                    $mysql->table($table)->insert($insertData);
                    $transferredForTable += count($insertData);
                }
            });

            $targetCount = $mysql->table($table)->count();
            $isMatch = ($sourceCount === $targetCount);
            $totalRowsCopied += $targetCount;

            $summary[] = [
                'table' => $table,
                'source' => $sourceCount,
                'target' => $targetCount,
                'status' => $isMatch ? '✓ MATCH' : '✗ MISMATCH'
            ];

            $statusText = $isMatch ? "<info>✓ {$targetCount} rows</info>" : "<error>✗ mismatch ({$targetCount}/{$sourceCount})</error>";
            $this->line("  - {$table}: {$statusText}");
        }

        $this->newLine();
        $this->warn("🔄 Re-enabling MySQL Foreign Key Checks...");
        $mysql->statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->newLine();
        $this->info("📊 Migration Summary Report:");
        $this->table(
            ['Table Name', 'SQLite (Old)', 'MySQL (New)', 'Status'],
            array_map(fn($row) => [
                $row['table'],
                $row['source'],
                $row['target'],
                $row['status']
            ], $summary)
        );

        $this->info("🎉 Completed transfer of {$totalRowsCopied} total records into MySQL!");
        return Command::SUCCESS;
    }

    protected function hasIdColumn($connection, string $table): bool
    {
        return $connection->getSchemaBuilder()->hasColumn($table, 'id');
    }
}
