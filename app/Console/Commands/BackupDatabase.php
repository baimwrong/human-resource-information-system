<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'hris:backup-db {--retention-days=14 : Number of days to keep backup files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a secure snapshot backup of the HRIS database and prune obsolete backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting HRIS database automated backup...');

        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $connection = config('database.default');
        $backupFile = "{$backupDir}/backup_{$connection}_{$timestamp}.sql";

        $success = false;

        if ($connection === 'pgsql') {
            $success = $this->backupPostgres($backupFile);
        } elseif ($connection === 'sqlite') {
            $success = $this->backupSqlite($backupFile);
        }

        // Fallback portable data snapshot if native utility is unavailable
        if (! $success) {
            $backupFile = "{$backupDir}/backup_snapshot_{$connection}_{$timestamp}.json";
            $success = $this->backupPortableJson($backupFile);
        }

        if ($success && File::exists($backupFile)) {
            $sizeKb = round(File::size($backupFile) / 1024, 2);
            $this->info("Backup created successfully: {$backupFile} ({$sizeKb} KB)");
            Log::info("HRIS automated backup created: {$backupFile} ({$sizeKb} KB)");
        } else {
            $this->error('Database backup encountered an error.');
            Log::error('HRIS automated backup failed.');
            return Command::FAILURE;
        }

        // Prune obsolete backups
        $retentionDays = (int)$this->option('retention-days');
        $this->pruneOldBackups($backupDir, $retentionDays);

        return Command::SUCCESS;
    }

    protected function backupPostgres(string $backupFile): bool
    {
        $dbHost = config('database.connections.pgsql.host', '127.0.0.1');
        $dbPort = config('database.connections.pgsql.port', '5432');
        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPass = config('database.connections.pgsql.password');

        $command = "pg_dump -h {$dbHost} -p {$dbPort} -U {$dbUser} -F p -b -v -f \"{$backupFile}\" {$dbName}";

        try {
            $result = Process::env(['PGPASSWORD' => $dbPass])->run($command);
            return $result->successful();
        } catch (\Throwable $e) {
            $this->warn('Native pg_dump not available in environment: ' . $e->getMessage());
            return false;
        }
    }

    protected function backupSqlite(string $backupFile): bool
    {
        $database = config('database.connections.sqlite.database');
        if ($database === ':memory:') {
            return false;
        }

        if (File::exists($database)) {
            return File::copy($database, $backupFile);
        }

        return false;
    }

    protected function backupPortableJson(string $backupFile): bool
    {
        try {
            $tables = [
                'departments',
                'designations',
                'shifts',
                'users',
                'employees',
                'salary_structures',
                'attendances',
                'leave_types',
                'leave_balances',
                'leave_requests',
                'payroll_batches',
                'payslips',
                'performance_reviews',
                'announcements',
                'settings',
            ];

            $snapshot = [
                'metadata' => [
                    'generated_at' => Carbon::now()->toIso8601String(),
                    'app_version' => config('app.version', '1.0.0'),
                    'environment' => config('app.env'),
                ],
                'data' => [],
            ];

            foreach ($tables as $table) {
                try {
                    $rows = DB::table($table)->get()->toArray();
                    $snapshot['data'][$table] = $rows;
                } catch (\Throwable) {
                    // Ignore missing optional tables
                }
            }

            File::put($backupFile, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            $this->error('Portable backup failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function pruneOldBackups(string $backupDir, int $retentionDays): void
    {
        $files = File::files($backupDir);
        $cutoff = Carbon::now()->subDays($retentionDays);
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($file->getMTime());
            if ($lastModified->lt($cutoff)) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} backup file(s) older than {$retentionDays} days.");
        }
    }
}
