<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/adresser.csv');

        if (!File::exists($csvFile)) {
            $this->command->error('CSV file not found: ' . $csvFile);
            return;
        }

        $this->command->info('Starting to import addresses from CSV...');

        // Set unlimited execution time and memory
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1G');

        // Clear existing addresses if any
        DB::table('addresses')->truncate();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle); // Skip header row

        $batchSize = 500; // Smaller batch size for better memory management
        $addresses = [];
        $count = 0;
        $skipped = 0;
        $totalLines = 0;

        $this->command->info('Processing CSV file...');

        while (($row = fgetcsv($handle)) !== false) {
            $totalLines++;

            // More robust validation
            if (count($row) >= 4 && !empty(trim($row[0])) && !empty(trim($row[1])) && !empty(trim($row[2])) && !empty(trim($row[3]))) {
                $vejnavn = trim($row[0]);
                $husnr = trim($row[1]);
                $postnr = trim($row[2]);
                $postnrnavn = trim($row[3]);

                // Skip if any field is empty after trimming
                if (empty($vejnavn) || empty($husnr) || empty($postnr) || empty($postnrnavn)) {
                    $skipped++;
                    continue;
                }

                // Create full address for searching
                $fullAddress = "{$vejnavn} {$husnr}, {$postnr} {$postnrnavn}";

                $addresses[] = [
                    'vejnavn' => $vejnavn,
                    'husnr' => $husnr,
                    'postnr' => $postnr,
                    'postnrnavn' => $postnrnavn,
                    'full_address' => $fullAddress,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $count++;

                // Insert in batches for better performance
                if (count($addresses) >= $batchSize) {
                    try {
                        DB::table('addresses')->insert($addresses);
                        $addresses = [];

                        // Show progress every 10,000 records
                        if ($count % 10000 === 0) {
                            $this->command->info("Imported {$count} addresses... (Processed {$totalLines} lines, skipped {$skipped})");
                        }
                    } catch (\Exception $e) {
                        $this->command->error("Error inserting batch at count {$count}: " . $e->getMessage());
                        break;
                    }
                }
            } else {
                $skipped++;

                // Debug first few skipped rows
                if ($skipped <= 10) {
                    $this->command->warn("Skipped line {$totalLines}: " . implode(',', $row));
                }
            }

            // Show progress every 100,000 lines processed
            if ($totalLines % 100000 === 0) {
                $this->command->info("Processed {$totalLines} lines, imported {$count}, skipped {$skipped}");
            }
        }

        // Insert remaining addresses
        if (!empty($addresses)) {
            try {
                DB::table('addresses')->insert($addresses);
            } catch (\Exception $e) {
                $this->command->error("Error inserting final batch: " . $e->getMessage());
            }
        }

        fclose($handle);

        $this->command->info("Finished processing CSV!");
        $this->command->info("Total lines processed: {$totalLines}");
        $this->command->info("Successfully imported: {$count} addresses");
        $this->command->info("Skipped invalid rows: {$skipped}");

        // Verify final count in database
        $dbCount = DB::table('addresses')->count();
        $this->command->info("Database contains: {$dbCount} addresses");
    }
}
