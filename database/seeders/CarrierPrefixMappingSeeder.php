<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarrierPrefixMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the table before seeding to avoid duplicates
        DB::table('carrier_prefix_mappings')->truncate();

        $now = Carbon::now();

        $prefixes = [
            // Cellcard
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '11', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '12', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '14', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '17', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '61', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '76', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '77', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '78', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '85', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '89', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '92', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '95', 'carrier' => 'cellcard'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '99', 'carrier' => 'cellcard'],

            // Metfone
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '31', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '60', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '66', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '67', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '68', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '71', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '88', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '90', 'carrier' => 'metfone'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '97', 'carrier' => 'metfone'],

            // Smart
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '10', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '15', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '16', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '69', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '70', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '81', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '86', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '87', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '93', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '96', 'carrier' => 'smart'],
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '98', 'carrier' => 'smart'],

            // Seatel
            ['country_code' => '855', 'country_iso' => 'KH', 'prefix' => '18', 'carrier' => 'seatel'],
        ];
        
        // Add timestamps to each record
        $data = array_map(function($item) use ($now) {
            return $item + ['created_at' => $now, 'updated_at' => $now];
        }, $prefixes);

        // Insert the data in a single query
        DB::table('carrier_prefix_mappings')->insert($data);
    }
}