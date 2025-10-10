<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApiProvider;

class ApiProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // TMT API Provider
        ApiProvider::updateOrCreate(
            ['name' => 'TMT'],
            [
                'name' => 'TMT',
                'base_url' => env('TMT_API_URL', 'https://api.tmtvelocity.com/live'),
                'api_key' => env('API_KEY', '66dc5490a189237be3990dd2d038b6e4f6cee8f9a214fdd373'),
                'api_secret' => env('API_SECRET', '8ce8cd8f5f6bd5'),
                'status' => 'active',
                'priority' => 1,
                'default_price' => 0.005
            ]
        );

        // IPQS API Provider (placeholder)
        ApiProvider::updateOrCreate(
            ['name' => 'IPQS'],
            [
                'name' => 'IPQS',
                'base_url' => env('IPQS_API_URL', 'https://ipqualityscore.com/api/json/phone'),
                'api_key' => env('IPQS_API_KEY', 'placeholder'),
                'status' => 'inactive', // Set to inactive until configured
                'priority' => 2,
                'default_price' => 0.008
            ]
        );

        $this->command->info('API Providers seeded successfully!');
    }
}