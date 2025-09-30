<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NetworkPrefix;
use Illuminate\Support\Facades\DB;

class NetworkPrefixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        NetworkPrefix::truncate();

        // Test data for Cambodia Cellcard Mobile prefixes
        $prefixes = [
            [
                'prefix' => '85592',
                'min_length' => 11,
                'max_length' => 11,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true, // 1 = true for live coverage
            ],
            [
                'prefix' => '85589',
                'min_length' => 11,
                'max_length' => 11,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true,
            ],
            [
                'prefix' => '85585',
                'min_length' => 11,
                'max_length' => 11,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true,
            ],
            [
                'prefix' => '85517',
                'min_length' => 11,
                'max_length' => 11,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true,
            ],
            [
                'prefix' => '85514',
                'min_length' => 11,
                'max_length' => 11,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true,
            ],
            [
                'prefix' => '85512',
                'min_length' => 11,
                'max_length' => 12,
                'country_name' => 'Cambodia',
                'network_name' => 'KH Cellcard Mobile 1',
                'mcc' => '456',
                'mnc' => '01',
                'live_coverage' => true,
            ],
        ];

        // Insert the data
        foreach ($prefixes as $prefix) {
            NetworkPrefix::create($prefix);
        }

        $this->command->info('Network prefixes seeded successfully!');
        $this->command->info('Added ' . count($prefixes) . ' Cambodia Cellcard Mobile prefixes');
    }
}