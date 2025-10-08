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

        $batchSize = 1000;
        $batch = [];
        $totalRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $batch[] = [
                'iso2' => $row[0],
                'network_id' => $row[1],
                'network_name' => $row[2],
                'mcc' => $row[3],
                'mnc' => $row[4],
                'country_code' => $row[5],
                'prefix' => $row[6],
                'live_coverage' => strtoupper($row[7]) === 'TRUE',
                'rate' => (float) $row[8],
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