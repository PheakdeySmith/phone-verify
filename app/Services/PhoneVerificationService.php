<?php

namespace App\Services;

use App\Models\{Verification, TmtCoverage, IpqsCoverage, ApiProvider};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PhoneVerificationService
{
    public function verify(string $phoneNumber)
    {
        // Clean phone number
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);
        
        // IMPORTANT: Check coverage FIRST before creating any records
        $providerData = $this->selectProvider($cleanNumber);
        
        if (!$providerData) {
            // No provider supports this number - return error immediately
            return [
                'success' => false,
                'message' => 'This phone number is not supported by any provider',
                'phone_number' => $phoneNumber,
                'note' => 'Number not found in coverage tables'
            ];
        }

        // Call the selected provider and create record in one go
        $result = $this->callProvider($phoneNumber, $cleanNumber, $providerData);

        return $result;
    }

    protected function cleanPhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    protected function selectProvider(string $cleanNumber): ?array
    {
        $prefixes = $this->generatePrefixes($cleanNumber);
        
        $cheapestProvider = null;
        $lowestCost = PHP_FLOAT_MAX;

        // Check TMT coverage
        foreach ($prefixes as $prefix) {
            $tmtCoverage = TmtCoverage::where('prefix', $prefix)
                ->where('live_coverage', true)
                ->first();

            if ($tmtCoverage && $tmtCoverage->rate < $lowestCost) {
                $lowestCost = $tmtCoverage->rate;
                $cheapestProvider = [
                    'provider' => 'TMT',
                    'cost' => $tmtCoverage->rate,
                    'coverage' => $tmtCoverage
                ];
            }
        }

        // Check IPQS coverage
        foreach ($prefixes as $prefix) {
            $ipqsCoverage = IpqsCoverage::where('number_prefix', $prefix)
                ->where('support_provider', true)
                ->first();

            if ($ipqsCoverage && $ipqsCoverage->price < $lowestCost) {
                $lowestCost = $ipqsCoverage->price;
                $cheapestProvider = [
                    'provider' => 'IPQS',
                    'cost' => $ipqsCoverage->price,
                    'coverage' => $ipqsCoverage
                ];
            }
        }

        return $cheapestProvider;
    }

    protected function generatePrefixes(string $number): array
    {
        $prefixes = [];
        $length = strlen($number);
        
        // Generate prefixes from longest to shortest
        for ($i = $length; $i >= 3; $i--) {
            $prefixes[] = substr($number, 0, $i);
        }
        
        return $prefixes;
    }

    protected function callProvider(string $phoneNumber, string $cleanNumber, array $providerData)
    {
        $provider = $providerData['provider'];

        if ($provider === 'TMT') {
            return $this->callTMT($phoneNumber, $cleanNumber, $providerData);
        } elseif ($provider === 'IPQS') {
            return $this->callIPQS($phoneNumber, $cleanNumber, $providerData);
        }

        return [
            'success' => false,
            'message' => 'Unknown provider'
        ];
    }

    protected function callTMT(string $phoneNumber, string $cleanNumber, array $providerData)
    {
        $apiProvider = ApiProvider::where('name', 'TMT')->first();
        
        if (!$apiProvider) {
            return ['success' => false, 'message' => 'TMT provider not configured'];
        }

        try {
            // Simulate API call (replace with actual API call)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiProvider->api_key
            ])->get($apiProvider->base_url . '/verify', [
                'phone' => $phoneNumber
            ]);

            // Create verification record with TMT data
            $verification = Verification::create([
                'phone_number' => $phoneNumber,
                'provider' => 'TMT',
                'cost' => $providerData['cost'],
                // TMT fields
                'tmt_prefix' => $providerData['coverage']->prefix,
                'tmt_network' => $response->json('network', 'Unknown'),
                'tmt_mcc' => $providerData['coverage']->mcc,
                'tmt_mnc' => $providerData['coverage']->mnc,
                'tmt_present' => $response->json('present', 'yes'),
                'tmt_status' => $response->json('status', 0),
                'tmt_ported' => $response->json('ported', false),
                'tmt_cic' => $response->json('cic'),
                'tmt_imsi' => $response->json('imsi'),
                'tmt_trxid' => Str::random(8),
                // IPQS fields will be NULL
            ]);

            return [
                'success' => true,
                'verification' => $verification,
                'provider_data' => $verification->getProviderData(),
                'cost' => $providerData['cost']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'TMT API call failed: ' . $e->getMessage()
            ];
        }
    }

    protected function callIPQS(string $phoneNumber, string $cleanNumber, array $providerData)
    {
        $apiProvider = ApiProvider::where('name', 'IPQS')->first();

        if (!$apiProvider) {
            return ['success' => false, 'message' => 'IPQS provider not configured'];
        }

        try {
            // Simulate API call (replace with actual API call)
            $response = Http::get($apiProvider->base_url . '/phone', [
                'key' => $apiProvider->api_key,
                'phone' => $phoneNumber
            ]);

            // Create verification record with IPQS data
            $verification = Verification::create([
                'phone_number' => $phoneNumber,
                'provider' => 'IPQS',
                'cost' => $providerData['cost'],
                // TMT fields will be NULL
                // IPQS fields
                'ipqs_formatted' => $response->json('formatted'),
                'ipqs_local_format' => $response->json('local_format'),
                'ipqs_valid' => $response->json('valid', false),
                'ipqs_active' => $response->json('active', false),
                'ipqs_fraud_score' => $response->json('fraud_score', 0),
                'ipqs_recent_abuse' => $response->json('recent_abuse', false),
                'ipqs_voip' => $response->json('VOIP', false),
                'ipqs_prepaid' => $response->json('prepaid', false),
                'ipqs_risky' => $response->json('risky', false),
                'ipqs_name' => $response->json('name'),
                'ipqs_associated_emails' => json_encode($response->json('associated_email_addresses', [])),
                'ipqs_carrier' => $response->json('carrier'),
                'ipqs_line_type' => $response->json('line_type'),
                'ipqs_leaked_online' => $response->json('leaked', false),
                'ipqs_spammer' => $response->json('spammer', false),
                'ipqs_country' => $response->json('country'),
                'ipqs_city' => $response->json('city'),
                'ipqs_region' => $response->json('region'),
                'ipqs_zip_code' => $response->json('zip_code'),
                'ipqs_timezone' => $response->json('timezone'),
                'ipqs_dialing_code' => $response->json('dialing_code'),
                'ipqs_active_status_enhanced' => $response->json('active_status'),
                'ipqs_request_id' => Str::random(10)
            ]);

            return [
                'success' => true,
                'verification' => $verification,
                'provider_data' => $verification->getProviderData(),
                'cost' => $providerData['cost']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'IPQS API call failed: ' . $e->getMessage()
            ];
        }
    }

    public function checkNetworkPrefix(string $phoneNumber): array
    {
        // Clean phone number
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

        // Generate prefixes to check
        $prefixes = $this->generatePrefixes($cleanNumber);

        $supportedProviders = [];
        $bestProvider = null;
        $lowestCost = PHP_FLOAT_MAX;

        // Check TMT coverage
        foreach ($prefixes as $prefix) {
            $tmtCoverage = TmtCoverage::where('prefix', $prefix)
                ->where('live_coverage', true)
                ->first();

            if ($tmtCoverage) {
                $provider = [
                    'provider' => 'TMT',
                    'country' => $tmtCoverage->iso2,
                    'network_name' => $tmtCoverage->network_name,
                    'cost' => number_format((float) $tmtCoverage->rate, 6),
                    'prefix_matched' => $prefix,
                    'live_coverage' => $tmtCoverage->live_coverage,
                    'mcc' => $tmtCoverage->mcc,
                    'mnc' => $tmtCoverage->mnc,
                    'country_code' => $tmtCoverage->country_code
                ];

                $supportedProviders[] = $provider;

                if ((float) $tmtCoverage->rate < $lowestCost) {
                    $lowestCost = (float) $tmtCoverage->rate;
                    $bestProvider = $provider;
                }
                break; // Take first (longest) match
            }
        }

        // Check IPQS coverage
        foreach ($prefixes as $prefix) {
            $ipqsCoverage = IpqsCoverage::where('number_prefix', $prefix)
                ->where('support_provider', true)
                ->first();

            if ($ipqsCoverage) {
                $provider = [
                    'provider' => 'IPQS',
                    'country' => $ipqsCoverage->country,
                    'network_name' => $ipqsCoverage->carrier_name,
                    'cost' => number_format((float) $ipqsCoverage->price, 6),
                    'prefix_matched' => $prefix,
                    'live_coverage' => $ipqsCoverage->support_provider,
                    'country_code' => $ipqsCoverage->cc,
                    'operator_id' => $ipqsCoverage->operator_id
                ];

                $supportedProviders[] = $provider;

                if ((float) $ipqsCoverage->price < $lowestCost) {
                    $lowestCost = (float) $ipqsCoverage->price;
                    $bestProvider = $provider;
                }
                break; // Take first (longest) match
            }
        }

        if (empty($supportedProviders)) {
            return [
                'success' => false,
                'error' => 'This phone number is not supported by any provider',
                'phone_number' => $phoneNumber,
                'clean_number' => $cleanNumber,
                'prefixes_checked' => $prefixes
            ];
        }

        // Use the best (cheapest) provider for the main response format expected by frontend
        return [
            'success' => true,
            'phone_number' => $phoneNumber,
            'clean_number' => $cleanNumber,
            'country_name' => $bestProvider['country'],
            'network_name' => $bestProvider['network_name'],
            'live_coverage' => $bestProvider['live_coverage'],
            'prefix' => $bestProvider['prefix_matched'],
            'provider' => $bestProvider['provider'],
            'cost' => $bestProvider['cost'],
            'supported_providers' => $supportedProviders,
            'total_providers_found' => count($supportedProviders)
        ];
    }
}