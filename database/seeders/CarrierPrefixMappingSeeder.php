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
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '11', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '12', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '14', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '17', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '61', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '76', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '77', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '78', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '85', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '89', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '92', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '95', 'carrier_keyword' => 'cellcard'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '99', 'carrier_keyword' => 'cellcard'],

            // Metfone
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '31', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '60', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '66', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '67', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '68', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '71', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '88', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '90', 'carrier_keyword' => 'metfone'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '97', 'carrier_keyword' => 'metfone'],

            // Smart
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '10', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '15', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '16', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '69', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '70', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '81', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '86', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '87', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '93', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '96', 'carrier_keyword' => 'smart'],
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '98', 'carrier_keyword' => 'smart'],

            // Seatel
            ['country_code' => '855', 'iso2' => 'KH', 'prefix' => '18', 'carrier_keyword' => 'seatel'],

            // USA - Verizon (country_code: 1)
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '201', 'carrier_keyword' => 'verizon'],
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '202', 'carrier_keyword' => 'verizon'],
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '203', 'carrier_keyword' => 'verizon'],

            // USA - AT&T
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '212', 'carrier_keyword' => 'att'],
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '213', 'carrier_keyword' => 'att'],
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '214', 'carrier_keyword' => 'att'],

            // USA - T-Mobile
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '310', 'carrier_keyword' => 'tmobile'],
            ['country_code' => '1', 'iso2' => 'US', 'prefix' => '311', 'carrier_keyword' => 'tmobile'],

            // UK - EE (country_code: 44)
            ['country_code' => '44', 'iso2' => 'GB', 'prefix' => '207', 'carrier_keyword' => 'ee'],
            ['country_code' => '44', 'iso2' => 'GB', 'prefix' => '208', 'carrier_keyword' => 'ee'],

            // UK - Vodafone
            ['country_code' => '44', 'iso2' => 'GB', 'prefix' => '77', 'carrier_keyword' => 'vodafone'],
            ['country_code' => '44', 'iso2' => 'GB', 'prefix' => '78', 'carrier_keyword' => 'vodafone'],
        ];
        
        // Add timestamps to each record
        $data = array_map(function($item) use ($now) {
            return $item + ['created_at' => $now, 'updated_at' => $now];
        }, $prefixes);

        // Insert the data in a single query
        DB::table('carrier_prefix_mappings')->insert($data);
    }
}