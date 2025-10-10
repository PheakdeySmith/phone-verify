<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TmtCoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = base_path('tmt_coverage.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("TMT coverage CSV file not found at: {$csvFile}");
            return;
        }

        $this->command->info('Starting TMT coverage data import...');

        // Clear existing data
        DB::table('tmt_coverage')->truncate();

        $handle = fopen($csvFile, 'r');
        fgetcsv($handle); // Skip header row

        $batchSize = 100;
        $batch = [];
        $totalRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip rows with empty or invalid prefix data
            if (empty($row[7]) || strlen($row[7]) > 50) {
                continue;
            }

            // Extract the first country code if multiple are provided
            $countryCode = $row[6];
            if (strpos($countryCode, ',') !== false) {
                $codes = explode(',', $countryCode);
                $countryCode = trim($codes[0]);
            }

            // Limit country_code length to prevent errors
            $countryCode = substr($countryCode, 0, 10);

            $batch[] = [
                'iso2' => $row[0],              // iso2
                'network_id' => $row[1],        // network_id
                'network_name' => $row[2],      // full_name
                'mcc' => $row[3],               // mcc
                'mnc' => $row[4],               // mnc
                'country_code' => $countryCode, // cc (country calling code, cleaned)
                'prefix' => substr($row[7], 0, 50), // CC+Prefix (full prefix, limited to 50 chars)
                'live_coverage' => strtoupper($row[8]) === 'TRUE',  // live_coverage
                'rate' => (float) $row[9],      // rates
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('tmt_coverage')->insert($batch);
                $totalRows += count($batch);
                $this->command->info("Inserted {$totalRows} rows...");
                $batch = [];
            }
        }

        // Insert remaining rows
        if (!empty($batch)) {
            DB::table('tmt_coverage')->insert($batch);
            $totalRows += count($batch);
        }

        fclose($handle);

        $this->command->info("TMT coverage import completed. Total rows inserted: {$totalRows}");
    }
}