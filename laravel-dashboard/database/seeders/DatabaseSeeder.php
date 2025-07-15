<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🔍 Checking for backup SQL files...');

        // Try to import latest backup first
        $this->importLatestBackup();

        // Create default user if it doesn't exist
        $this->command->info('👤 Creating default user...');
        if (!User::where('email', 'mr.lukas.schmidt@gmail.com')->exists()) {
            User::factory()->create([
                'name' => 'Lukas Schmidt',
                'email' => 'mr.lukas.schmidt@gmail.com',
                'password' => bcrypt('password'),
            ]);
            $this->command->info('✅ User created successfully!');
        } else {
            $this->command->info('👤 User already exists, skipping...');
        }

        $this->command->info('✅ Database seeding completed!');
    }

    /**
     * Import the latest SQL backup file from /scrapJob/backups
     */
    private function importLatestBackup(): void
    {
        // Try multiple possible paths for the backups folder
        $possiblePaths = [
            '/var/www/backups',           // If mounted as volume
            '/backups',                   // If mounted at root
            base_path('../backups'),      // Standard relative path
            '/app/backups',              // If in app directory
        ];

        $backupPath = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $backupPath = $path;
                break;
            }
        }

        if (!$backupPath) {
            $this->command->warn("📁 Backup folder not found. Tried paths:");
            foreach ($possiblePaths as $path) {
                $this->command->warn("   • {$path}");
            }
            $this->command->info("💡 To use backups, mount your backup folder to one of these paths in docker-compose.yml");
            return;
        }

        // Find all SQL backup files matching the pattern: linkedin_jobs_backup_*.sql
        $backupFiles = collect(File::files($backupPath))
            ->filter(fn($file) => preg_match('/linkedin_jobs_backup_(\d{8}_\d{6})\.sql$/', $file->getFilename(), $matches))
            ->map(function ($file) {
                // Extract timestamp from filename
                preg_match('/linkedin_jobs_backup_(\d{8}_\d{6})\.sql$/', $file->getFilename(), $matches);
                $timestamp = $matches[1];

                return [
                    'file' => $file,
                    'timestamp' => $timestamp,
                    'parsed_date' => Carbon::createFromFormat('Ymd_His', $timestamp),
                    'path' => $file->getPathname(),
                    'name' => $file->getFilename(),
                ];
            })
            ->sortByDesc('parsed_date'); // Sort by date descending (newest first)

        if ($backupFiles->isEmpty()) {
            $this->command->warn('📄 No backup SQL files found matching pattern: linkedin_jobs_backup_YYYYMMDD_HHMMSS.sql');
            return;
        }

        $latestBackup = $backupFiles->first();
        $this->command->info("📥 Found {$backupFiles->count()} backup file(s). Using latest: {$latestBackup['name']}");
        $this->command->info("📅 Backup date: {$latestBackup['parsed_date']->format('Y-m-d H:i:s')}");

        try {
            // Read and execute SQL file
            $this->command->info('⚡ Importing SQL backup...');
            $this->importSqlFile($latestBackup['path']);
            $this->command->info('✅ SQL backup imported successfully!');

            // Show some stats
            $this->showImportStats();

        } catch (\Exception $e) {
            $this->command->error("❌ Failed to import backup: {$e->getMessage()}");
            $this->command->warn("💡 You can manually import with: mysql -u laravel -p linkedin_jobs < {$latestBackup['path']}");
        }
    }

    /**
     * Import SQL file by executing it line by line
     */
    private function importSqlFile(string $filePath): void
    {
        $sql = File::get($filePath);

        // Remove MySQL dump comments and split into statements
        $sql = preg_replace('/\/\*!\d+.*?\*\/;?/', '', $sql);
        $sql = preg_replace('/--.*$/m', '', $sql);

        // Split by semicolons but be careful with quoted strings
        $statements = collect(explode(';', $sql))
            ->map(fn($stmt) => trim($stmt))
            ->filter(fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '/*') && !str_starts_with($stmt, '--'));

        $this->command->info("📊 Processing {$statements->count()} SQL statements...");

        // Disable foreign key checks during import
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $counters = $this->processStatements($statements);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✅ Processed {$counters['success']} data statements, skipped {$counters['skip']} schema statements");
    }

    /**
     * Process SQL statements and return counters
     */
    private function processStatements($statements): array
    {
        $successCount = 0;
        $skipCount = 0;

        foreach ($statements as $statement) {
            if (!trim($statement)) {
                continue;
            }

            if ($this->shouldSkipStatement($statement)) {
                $skipCount++;
                continue;
            }

            try {
                if ($this->isDataStatement($statement)) {
                    DB::statement($statement);
                    $successCount++;
                } else {
                    $skipCount++;
                }
            } catch (\Exception $e) {
                if (!$this->isExpectedError($e)) {
                    $this->command->warn("⚠️  SQL Warning: " . substr($e->getMessage(), 0, 100) . '...');
                }
                $skipCount++;
            }
        }

        return ['success' => $successCount, 'skip' => $skipCount];
    }

    /**
     * Check if statement should be skipped
     */
    private function shouldSkipStatement(string $statement): bool
    {
        return preg_match('/^\s*(DROP TABLE|CREATE TABLE)/i', $statement);
    }

    /**
     * Check if statement is a data operation
     */
    private function isDataStatement(string $statement): bool
    {
        return preg_match('/^\s*(INSERT|UPDATE|DELETE|SET|LOCK|UNLOCK)/i', $statement);
    }

    /**
     * Check if error is expected and can be ignored
     */
    private function isExpectedError(\Exception $e): bool
    {
        return str_contains($e->getMessage(), 'already exists') ||
               str_contains($e->getMessage(), "doesn't exist");
    }

    /**
     * Show statistics about imported data
     */
    private function showImportStats(): void
    {
        try {
            $stats = [
                'companies' => DB::table('companies')->count(),
                'job_postings' => DB::table('job_postings')->count(),
                'job_queue' => DB::table('job_queue')->count(),
                'job_ratings' => DB::table('job_ratings')->count(),
            ];

            $this->command->info('📈 Import Statistics:');
            foreach ($stats as $table => $count) {
                $this->command->line("   • {$table}: {$count} records");
            }
        } catch (\Exception $e) {
            $this->command->warn('📊 Could not fetch import statistics');
        }
    }
}
