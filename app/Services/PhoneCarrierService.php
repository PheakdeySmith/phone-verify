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

        $mappings = $this->getCarrierMappings();

        $countryCode = null;
        $iso2 = null;
        $remainingNumber = null;

        for ($i = 3; $i >= 1; $i--) {
            $possibleCode = substr($phoneNumber, 0, $i);
            if (isset($mappings[$possibleCode])) {
                $countryCode = $possibleCode;
                $iso2 = $mappings[$possibleCode]['iso2'];
                $remainingNumber = substr($phoneNumber, $i);
                break;
            }
        }

        if (!$countryCode) {
            return [
                'success' => false,
                'error' => 'Country code not found'
            ];
        }

        $carrierKeyword = null;
        $carrierPrefix = null;

        for ($i = 3; $i >= 2; $i--) {
            $possiblePrefix = substr($remainingNumber, 0, $i);
            if (isset($mappings[$countryCode]['prefixes'][$possiblePrefix])) {
                $carrierPrefix = $possiblePrefix;
                $carrierKeyword = $mappings[$countryCode]['prefixes'][$possiblePrefix];
                break;
            }
        }

        if (!$carrierKeyword) {
            return [
                'success' => false,
                'error' => 'Carrier prefix not found',
                'country_code' => $countryCode,
                'iso2' => $iso2
            ];
        }

        $networkCarrier = DB::table('network_carried')
            ->where('iso2', $iso2)
            ->whereRaw('LOWER(full_name) LIKE ?', ['%' . $carrierKeyword . '%'])
            ->first();

        if (!$networkCarrier) {
            $networkIdPattern = $countryCode . '%';
            $networkCarrier = DB::table('network_carried')
                ->where('iso2', $iso2)
                ->where('network_id', 'LIKE', $networkIdPattern)
                ->whereRaw('LOWER(full_name) LIKE ?', ['%' . $carrierKeyword . '%'])
                ->first();
        }

        return [
            'success' => true,
            'phone_number' => $phoneNumber,
            'country_code' => $countryCode,
            'iso2' => $iso2,
            'carrier_prefix' => $carrierPrefix,
            'carrier_name' => $networkCarrier ? $networkCarrier->full_name : ucfirst($carrierKeyword),
            'network_id' => $networkCarrier ? $networkCarrier->network_id : null,
            'mcc' => $networkCarrier ? $networkCarrier->mcc : null,
            'mnc' => $networkCarrier ? $networkCarrier->mnc : null,
            'live_coverage' => $networkCarrier ? (bool)$networkCarrier->live_coverage : false,
            'should_send_to_api' => $networkCarrier && $networkCarrier->live_coverage
        ];
    }

    public function clearCache()
    {
        Cache::forget('carrier_mappings');
    }
}