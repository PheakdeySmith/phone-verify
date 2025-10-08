<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IpqsCoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting IPQS coverage data import...');

        // Clear existing data
        DB::table('ipqs_coverage')->truncate();

        $coverageData = [
            // UAE - OVERLAPPING with TMT (TMT: 0.000945, IPQS: higher cost)
            ['country' => 'AE', 'operator_id' => 'AE_ETI_001', 'carrier_name' => 'Emirates Telecom-ETISALAT', 'cc' => '971', 'number_prefix' => '97150', 'support_provider' => true, 'price' => 0.001200], // TMT: 0.000945
            ['country' => 'AE', 'operator_id' => 'AE_ETI_002', 'carrier_name' => 'Emirates Telecom-ETISALAT', 'cc' => '971', 'number_prefix' => '971501', 'support_provider' => true, 'price' => 0.001250], // TMT: 0.000945
            ['country' => 'AE', 'operator_id' => 'AE_DU_001', 'carrier_name' => 'Emirates Integrated Telecom-DU', 'cc' => '971', 'number_prefix' => '97152', 'support_provider' => true, 'price' => 0.0001100], // TMT: 0.000945 (IPQS cheaper)
            ['country' => 'AE', 'operator_id' => 'AE_DU_002', 'carrier_name' => 'Emirates Integrated Telecom-DU', 'cc' => '971', 'number_prefix' => '97155', 'support_provider' => true, 'price' => 0.0001000], // TMT: 0.000945 (IPQS cheaper)

            // UK - OVERLAPPING with TMT (TMT: 0.000945, IPQS: higher cost)
            ['country' => 'GB', 'operator_id' => 'GB_24_001', 'carrier_name' => '24 Seven Communications', 'cc' => '44', 'number_prefix' => '4474066', 'support_provider' => true, 'price' => 0.001200], // TMT: 0.000945
            ['country' => 'GB', 'operator_id' => 'GB_24_002', 'carrier_name' => '24 Seven Communications', 'cc' => '44', 'number_prefix' => '4477003', 'support_provider' => true, 'price' => 0.001300], // TMT: 0.000945
            ['country' => 'GB', 'operator_id' => 'GB_CLD_001', 'carrier_name' => 'Cloud9 Communications', 'cc' => '44', 'number_prefix' => '4474409', 'support_provider' => true, 'price' => 0.001100], // TMT: 0.000945
            ['country' => 'GB', 'operator_id' => 'GB_EE_001', 'carrier_name' => 'EE Limited', 'cc' => '44', 'number_prefix' => '447', 'support_provider' => true, 'price' => 0.001500],
            ['country' => 'GB', 'operator_id' => 'GB_O2_001', 'carrier_name' => 'Telefonica O2 UK', 'cc' => '44', 'number_prefix' => '4477', 'support_provider' => true, 'price' => 0.001450],

            // Australia - OVERLAPPING with TMT (TMT: 0.000525, IPQS: much higher cost)
            ['country' => 'AU', 'operator_id' => 'AU_PIV_001', 'carrier_name' => 'Pivotel Satellite', 'cc' => '61', 'number_prefix' => '6142', 'support_provider' => true, 'price' => 0.002000], // TMT: 0.000525 (TMT much cheaper)
            ['country' => 'AU', 'operator_id' => 'AU_PIV_002', 'carrier_name' => 'Pivotel Satellite', 'cc' => '61', 'number_prefix' => '61436', 'support_provider' => true, 'price' => 0.001900], // TMT: 0.000525 (TMT much cheaper)
            ['country' => 'AU', 'operator_id' => 'AU_PIV_003', 'carrier_name' => 'Pivotel Satellite', 'cc' => '61', 'number_prefix' => '61455', 'support_provider' => true, 'price' => 0.001800], // TMT: 0.000525 (TMT much cheaper)
            ['country' => 'AU', 'operator_id' => 'AU_TEL_001', 'carrier_name' => 'Telstra Corporation', 'cc' => '61', 'number_prefix' => '614', 'support_provider' => true, 'price' => 0.001800],
            ['country' => 'AU', 'operator_id' => 'AU_OPT_001', 'carrier_name' => 'Optus', 'cc' => '61', 'number_prefix' => '6141', 'support_provider' => true, 'price' => 0.001750],

            // United States
            ['country' => 'US', 'operator_id' => 'US_VZ_001', 'carrier_name' => 'Verizon Wireless', 'cc' => '1', 'number_prefix' => '1201', 'support_provider' => true, 'price' => 0.001200],
            ['country' => 'US', 'operator_id' => 'US_VZ_002', 'carrier_name' => 'Verizon Wireless', 'cc' => '1', 'number_prefix' => '1202', 'support_provider' => true, 'price' => 0.001200],
            ['country' => 'US', 'operator_id' => 'US_ATT_001', 'carrier_name' => 'AT&T Mobility', 'cc' => '1', 'number_prefix' => '1212', 'support_provider' => true, 'price' => 0.001150],
            ['country' => 'US', 'operator_id' => 'US_TMO_001', 'carrier_name' => 'T-Mobile USA', 'cc' => '1', 'number_prefix' => '1213', 'support_provider' => true, 'price' => 0.001100],
            ['country' => 'US', 'operator_id' => 'US_SPR_001', 'carrier_name' => 'Sprint Corporation', 'cc' => '1', 'number_prefix' => '1214', 'support_provider' => true, 'price' => 0.001180],

            // New Zealand (from your example)
            ['country' => 'NZ', 'operator_id' => 'NZ_SPK_001', 'carrier_name' => 'Spark', 'cc' => '64', 'number_prefix' => '643', 'support_provider' => true, 'price' => 0.002000],
            ['country' => 'NZ', 'operator_id' => 'NZ_SPK_002', 'carrier_name' => 'Spark', 'cc' => '64', 'number_prefix' => '6433', 'support_provider' => true, 'price' => 0.002000],
            ['country' => 'NZ', 'operator_id' => 'NZ_VOD_001', 'carrier_name' => 'Vodafone New Zealand', 'cc' => '64', 'number_prefix' => '6421', 'support_provider' => true, 'price' => 0.001950],

            // Canada
            ['country' => 'CA', 'operator_id' => 'CA_ROG_001', 'carrier_name' => 'Rogers Communications', 'cc' => '1', 'number_prefix' => '1416', 'support_provider' => true, 'price' => 0.001300],
            ['country' => 'CA', 'operator_id' => 'CA_BELL_001', 'carrier_name' => 'Bell Mobility', 'cc' => '1', 'number_prefix' => '1514', 'support_provider' => true, 'price' => 0.001250],
            ['country' => 'CA', 'operator_id' => 'CA_TEL_001', 'carrier_name' => 'TELUS Mobility', 'cc' => '1', 'number_prefix' => '1604', 'support_provider' => true, 'price' => 0.001280],

            // Germany
            ['country' => 'DE', 'operator_id' => 'DE_DT_001', 'carrier_name' => 'Deutsche Telekom', 'cc' => '49', 'number_prefix' => '4915', 'support_provider' => true, 'price' => 0.001600],
            ['country' => 'DE', 'operator_id' => 'DE_VOD_001', 'carrier_name' => 'Vodafone Germany', 'cc' => '49', 'number_prefix' => '4916', 'support_provider' => true, 'price' => 0.001580],
            ['country' => 'DE', 'operator_id' => 'DE_O2_001', 'carrier_name' => 'Telefonica O2 Germany', 'cc' => '49', 'number_prefix' => '4917', 'support_provider' => true, 'price' => 0.001620],

            // France
            ['country' => 'FR', 'operator_id' => 'FR_ORA_001', 'carrier_name' => 'Orange France', 'cc' => '33', 'number_prefix' => '336', 'support_provider' => true, 'price' => 0.001700],
            ['country' => 'FR', 'operator_id' => 'FR_SFR_001', 'carrier_name' => 'SFR', 'cc' => '33', 'number_prefix' => '337', 'support_provider' => true, 'price' => 0.001680],

            // Singapore
            ['country' => 'SG', 'operator_id' => 'SG_STH_001', 'carrier_name' => 'StarHub', 'cc' => '65', 'number_prefix' => '658', 'support_provider' => true, 'price' => 0.002200],
            ['country' => 'SG', 'operator_id' => 'SG_SING_001', 'carrier_name' => 'Singtel', 'cc' => '65', 'number_prefix' => '659', 'support_provider' => true, 'price' => 0.002150],

            // Japan
            ['country' => 'JP', 'operator_id' => 'JP_NTT_001', 'carrier_name' => 'NTT DoCoMo', 'cc' => '81', 'number_prefix' => '8180', 'support_provider' => true, 'price' => 0.002500],
            ['country' => 'JP', 'operator_id' => 'JP_SOFT_001', 'carrier_name' => 'SoftBank', 'cc' => '81', 'number_prefix' => '8190', 'support_provider' => true, 'price' => 0.002450],

            // India (higher volume, lower cost)
            ['country' => 'IN', 'operator_id' => 'IN_JIO_001', 'carrier_name' => 'Reliance Jio', 'cc' => '91', 'number_prefix' => '917', 'support_provider' => true, 'price' => 0.000800],
            ['country' => 'IN', 'operator_id' => 'IN_AIR_001', 'carrier_name' => 'Bharti Airtel', 'cc' => '91', 'number_prefix' => '918', 'support_provider' => true, 'price' => 0.000750],
            ['country' => 'IN', 'operator_id' => 'IN_VI_001', 'carrier_name' => 'Vodafone Idea', 'cc' => '91', 'number_prefix' => '919', 'support_provider' => true, 'price' => 0.000780],

            // UAE
            ['country' => 'AE', 'operator_id' => 'AE_ETI_001', 'carrier_name' => 'Emirates Telecom-ETISALAT', 'cc' => '971', 'number_prefix' => '9715', 'support_provider' => true, 'price' => 0.001900],
            ['country' => 'AE', 'operator_id' => 'AE_DU_001', 'carrier_name' => 'Emirates Integrated Telecom-DU', 'cc' => '971', 'number_prefix' => '9710', 'support_provider' => true, 'price' => 0.001880],

            // Some unsupported regions for testing
            ['country' => 'XX', 'operator_id' => 'XX_TEST_001', 'carrier_name' => 'Test Carrier Unsupported', 'cc' => '999', 'number_prefix' => '9991', 'support_provider' => false, 'price' => 0.000000],
        ];

        $batchSize = 50;
        $batches = array_chunk($coverageData, $batchSize);
        $totalRows = 0;

        foreach ($batches as $batch) {
            $insertData = array_map(fn($row) => array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]), $batch);

            DB::table('ipqs_coverage')->insert($insertData);
            $totalRows += count($insertData);
            $this->command->info("Inserted {$totalRows} rows...");
        }

        $this->command->info("IPQS coverage import completed. Total rows inserted: {$totalRows}");
    }
}