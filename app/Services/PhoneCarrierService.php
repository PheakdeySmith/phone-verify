<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\CountryCode;
use App\Models\CarrierPrefixMapping;

class PhoneCarrierService
{
    private function getCarrierMappings()
    {
        return Cache::remember('carrier_mappings', 3600, function () {
            $mappings = [];
            $results = DB::table('carrier_prefix_mappings')->get();

            foreach ($results as $row) {
                if (!isset($mappings[$row->country_code])) {
                    $mappings[$row->country_code] = [
                        'iso2' => $row->iso2,
                        'prefixes' => []
                    ];
                }
                $mappings[$row->country_code]['prefixes'][$row->prefix] = $row->carrier_keyword;
            }

            return $mappings;
        });
    }

    public function identifyCarrier(string $phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // First, check if country code exists in country_codes table
        $countryCode = null;
        $iso2 = null;
        $countryName = null;

        // Try to find country code (1-4 digits for international codes)
        for ($i = 4; $i >= 1; $i--) {
            $possibleCode = substr($phoneNumber, 0, $i);
            $country = \App\Models\CountryCode::where('dial_code', $possibleCode)->first();
            if ($country) {
                $countryCode = $possibleCode;
                $iso2 = $country->iso2;
                $countryName = $country->country_name;
                break;
            }
        }

        if (!$countryCode) {
            return [
                'success' => false,
                'error' => 'Country code not found'
            ];
        }

        $remainingNumber = substr($phoneNumber, strlen($countryCode));

        // Now check if we have carrier mappings for this country
        $mappings = $this->getCarrierMappings();

        $carrierKeyword = null;
        $carrierPrefix = null;
        $networkCarrier = null;

        // Check if we have carrier mappings for this country
        if (isset($mappings[$countryCode])) {
            // We have carrier data for this country - check prefixes
            for ($i = 3; $i >= 2; $i--) {
                $possiblePrefix = substr($remainingNumber, 0, $i);
                if (isset($mappings[$countryCode]['prefixes'][$possiblePrefix])) {
                    $carrierPrefix = $possiblePrefix;
                    $carrierKeyword = $mappings[$countryCode]['prefixes'][$possiblePrefix];
                    break;
                }
            }

            if ($carrierKeyword) {
                // Try to find network carrier info
                $networkCarrier = DB::table('network_carried')
                    ->where('iso2', $iso2)
                    ->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($carrierKeyword) . '%'])
                    ->first();

                if (!$networkCarrier) {
                    $networkIdPattern = $countryCode . '%';
                    $networkCarrier = DB::table('network_carried')
                        ->where('iso2', $iso2)
                        ->where('network_id', 'LIKE', $networkIdPattern)
                        ->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($carrierKeyword) . '%'])
                        ->first();
                }
            }
        }

        // Return success with available information
        return [
            'success' => true,
            'phone_number' => $phoneNumber,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'iso2' => $iso2,
            'carrier_prefix' => $carrierPrefix,
            'carrier_name' => $networkCarrier ? $networkCarrier->full_name : ($carrierKeyword ? ucfirst($carrierKeyword) : 'Unknown'),
            'network_id' => $networkCarrier ? $networkCarrier->network_id : null,
            'mcc' => $networkCarrier ? $networkCarrier->mcc : null,
            'mnc' => $networkCarrier ? $networkCarrier->mnc : null,
            'live_coverage' => $networkCarrier ? (bool)$networkCarrier->live_coverage : false,
            'should_send_to_api' => $networkCarrier && $networkCarrier->live_coverage,
            'has_carrier_data' => $carrierKeyword !== null
        ];
    }

    public function clearCache()
    {
        Cache::forget('carrier_mappings');
    }
}