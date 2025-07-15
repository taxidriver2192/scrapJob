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
        $this->command->info('🔍 Starting database seeding...');

        // Create default user first
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

        // Import backup data (INSERT statements only)
        $this->importLatestBackup();

        // Seed addresses from CSV (run this last as it takes a long time)
        $this->command->info('📍 Seeding addresses...');
        $this->call(AddressSeeder::class);

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
        $this->command->info('⚡ Importing SQL backup using direct MySQL import...');

        try {
            // Use direct MySQL import via command line for better handling of complex SQL
            $host = config('database.connections.mysql.host');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $database = config('database.connections.mysql.database');

            // Build the mysql command with proper password handling
            $command = sprintf(
                'mysql -h%s -u%s -p%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            // Execute the command and capture output
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode === 0) {
                $this->command->info('✅ SQL backup imported successfully via direct MySQL import!');
            } else {
                throw new \RuntimeException('MySQL import failed with return code ' . $returnCode . ': ' . implode("\n", $output));
            }

        } catch (\Exception $e) {
            $this->command->warn("⚠️  Direct import failed: {$e->getMessage()}");
            $this->command->info("🔄 Falling back to Laravel DB import...");
            $this->importSqlFileViaLaravel($filePath);
        }
    }

    /**
     * Fallback: Import SQL file using Laravel's DB facade with proper transaction handling
     * Only executes INSERT statements, skips CREATE TABLE statements
     */
    private function importSqlFileViaLaravel(string $filePath): void
    {
        $this->command->info('📂 Reading SQL backup file...');
        $sql = File::get($filePath);

        // Remove MySQL dump comments and settings
        $sql = preg_replace('/\/\*!\d+.*?\*\//s', '', $sql);
        $sql = preg_replace('/--.*$/m', '', $sql);

        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function($statement) {
                return !empty($statement);
            }
        );

        // Filter to only include INSERT statements for tables that exist
        $insertStatements = array_filter($statements, function($statement) {
            $statement = trim($statement);
            // Only allow INSERT statements
            if (!preg_match('/^INSERT\s+INTO\s+`?([^`\s]+)`?/i', $statement, $matches)) {
                return false;
            }

            $tableName = $matches[1];

            // List of tables that exist in your current database schema
            $allowedTables = [
                'companies',
                'job_postings',
                'job_queue',
                'job_ratings',
                'users',
                'addresses'
            ];

            return in_array($tableName, $allowedTables);
        });

        if (empty($insertStatements)) {
            $this->command->warn('📄 No INSERT statements found in the backup file.');
            return;
        }

        $this->command->info("📊 Found " . count($insertStatements) . " INSERT statements to execute...");

        // Execute as a single transaction using Laravel's unprepared method
        try {
            DB::beginTransaction();

            // Disable foreign key checks to handle dependency order issues
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $this->command->info('⚡ Executing INSERT statements...');

            $successCount = 0;
            $skipCount = 0;

            foreach ($insertStatements as $index => $statement) {
                try {
                    // Add semicolon if missing
                    $statement = rtrim($statement, ';') . ';';

                    DB::unprepared($statement);
                    $successCount++;

                    // Show progress every 10 statements
                    if (($index + 1) % 10 === 0) {
                        $this->command->info("   Executed " . ($index + 1) . " of " . count($insertStatements) . " statements...");
                    }
                } catch (\Exception $e) {
                    // Skip statements that fail (e.g., duplicate entries)
                    $skipCount++;
                    $this->command->warn("   Skipped statement " . ($index + 1) . ": " . substr($e->getMessage(), 0, 100) . "...");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();
            $this->command->info("✅ SQL data imported successfully! Executed: {$successCount}, Skipped: {$skipCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Laravel import failed: {$e->getMessage()}");

            // Provide helpful debugging information
            $this->command->warn("💡 This might be due to:");
            $this->command->warn("   • Data integrity constraint violations");
            $this->command->warn("   • Character encoding issues");
            $this->command->warn("   • Foreign key dependency issues");

            throw $e;
        }
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
