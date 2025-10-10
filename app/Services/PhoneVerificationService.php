<?php

namespace App\Services;

use App\Models\{Verification, TmtCoverage, IpqsCoverage, ApiProvider};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhoneVerificationService
{
    /**
     * BASIC QUERY - Just check coverage (FREE, no API call, no DB storage)
     * Returns basic info from coverage tables only
     */
    public function basicQuery(string $phoneNumber)
    {
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);
        $prefixes = $this->generatePrefixes($cleanNumber);
        
        $availableProviders = [];
        
        // Check TMT coverage
        foreach ($prefixes as $prefix) {
            $tmtCoverage = TmtCoverage::where('prefix', $prefix)
                ->where('live_coverage', true)
                ->first();
            
            if ($tmtCoverage) {
                $availableProviders[] = [
                    'provider' => 'TMT',
                    'country' => $tmtCoverage->iso2,
                    'country_code' => $tmtCoverage->country_code,
                    'network_name' => $tmtCoverage->network_name,
                    'network_id' => $tmtCoverage->network_id,
                    'prefix' => $tmtCoverage->prefix,
                    'mcc' => $tmtCoverage->mcc,
                    'mnc' => $tmtCoverage->mnc,
                    'live_coverage' => $tmtCoverage->live_coverage,
                    'rate' => $tmtCoverage->rate
                ];
                break; // Found match, stop searching
            }
        }
        
        // Check IPQS coverage
        foreach ($prefixes as $prefix) {
            $ipqsCoverage = IpqsCoverage::where('number_prefix', $prefix)
                ->where('support_provider', true)
                ->first();
            
            if ($ipqsCoverage) {
                $availableProviders[] = [
                    'provider' => 'IPQS',
                    'country' => $ipqsCoverage->country,
                    'country_code' => $ipqsCoverage->cc,
                    'carrier_name' => $ipqsCoverage->carrier_name,
                    'operator_id' => $ipqsCoverage->operator_id,
                    'prefix' => $ipqsCoverage->number_prefix,
                    'support_provider' => $ipqsCoverage->support_provider,
                    'price' => $ipqsCoverage->price
                ];
                break; // Found match, stop searching
            }
        }
        
        if (empty($availableProviders)) {
            return [
                'success' => false,
                'message' => 'Phone number not supported by any provider',
                'phone_number' => $phoneNumber,
                'supported' => false
            ];
        }
        
        // Find cheapest provider
        $cheapest = collect($availableProviders)->sortBy(function($provider) {
            return $provider['rate'] ?? $provider['price'];
        })->first();
        
        return [
            'success' => true,
            'query_type' => 'basic',
            'phone_number' => $phoneNumber,
            'supported' => true,
            'available_providers' => $availableProviders,
            'recommended_provider' => $cheapest['provider'],
            'estimated_cost' => $cheapest['rate'] ?? $cheapest['price'],
            'coverage_info' => $cheapest,
            'note' => 'This is a basic query showing coverage only. Use advanced verification for detailed results.'
        ];
    }

    /**
     * ADVANCED QUERY - Full verification with caching and API call
     * Costs money but returns detailed verification data
     *
     * @param string $phoneNumber The phone number to verify
     * @param bool|string $dataFreshness Can be:
     *   - false: Use cache if available (default)
     *   - true: Force re-verification (ignore cache)
     *   - 'all': Force re-verification for all numbers
     *   - '30', '60', '90': Re-verify only if cached data is older than N days
     */
    public function advancedVerify(string $phoneNumber, $dataFreshness = false)
    {
        $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

        // STEP 1: Check if this number already exists in our database (CACHE)
        $existingVerification = Verification::where('phone_number', $phoneNumber)->first();

        // STEP 2: Determine if we need fresh data based on dataFreshness parameter
        $forceReverify = false;

        if ($dataFreshness === true || $dataFreshness === 'all') {
            // Force re-verification
            $forceReverify = true;
        } elseif (in_array($dataFreshness, ['30', '60', '90'])) {
            // Check if existing data is older than specified days
            if ($existingVerification) {
                $daysOld = now()->diffInDays($existingVerification->updated_at);
                $maxAgeDays = (int)$dataFreshness;

                if ($daysOld >= $maxAgeDays) {
                    $forceReverify = true; // Data is too old
                }
            }
        }

        // STEP 3: Return cached result if available and not forcing re-verification
        if (!$forceReverify && $existingVerification) {
            // Found in database! Return cached result without calling API
            return [
                'success' => true,
                'query_type' => 'advanced',
                'verification' => $existingVerification,
                'provider_data' => $existingVerification->getProviderData(),
                'cost' => $existingVerification->cost,
                'cached' => true,
                'data_age_days' => now()->diffInDays($existingVerification->updated_at),
                'message' => 'Result retrieved from database (no API call made)'
            ];
        }
        
        // STEP 4: Not in database (or forcing re-verification), check coverage tables
        $providerData = $this->selectProvider($cleanNumber);
        
        if (!$providerData) {
            return [
                'success' => false,
                'query_type' => 'advanced',
                'message' => 'This phone number is not supported by any provider',
                'phone_number' => $phoneNumber,
                'note' => 'Number not found in coverage tables'
            ];
        }

        // STEP 5: Call the provider API and save to database
        $result = $this->callProvider($phoneNumber, $cleanNumber, $providerData);
        
        if ($result['success']) {
            $result['query_type'] = 'advanced';
            $result['cached'] = false;
            $result['message'] = 'Fresh verification from API provider';
        }

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

        // Generate prefixes from longest to shortest (down to 1 digit for country codes)
        // This supports: 1-4 digit country codes and 8-10 digit full prefixes
        for ($i = $length; $i >= 1; $i--) {
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
            return $this->callIPQS($phoneNumber, $providerData);
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
            // TMT API call using the correct format: /live/format/apikey/apisecret/number
            $apiUrl = $apiProvider->base_url . '/format/' . $apiProvider->api_key . '/' . $apiProvider->api_secret . '/' . $cleanNumber;

            Log::info('Making TMT API call', [
                'phone_number' => $phoneNumber,
                'api_url' => $apiUrl
            ]);

            $response = Http::timeout(30)->get($apiUrl);

            // Check if API call was successful
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'TMT API call failed with status: ' . $response->status()
                ];
            }

            $responseData = $response->json();

            // Parse TMT response data
            $phoneData = $responseData[$cleanNumber] ?? null;

            if (!$phoneData) {
                return [
                    'success' => false,
                    'message' => 'No data returned for this phone number'
                ];
            }

            $verification = Verification::create([
                'phone_number' => $phoneNumber,
                'provider' => 'TMT',
                'cost' => $providerData['cost'],
                'tmt_prefix' => $providerData['coverage']->prefix,
                'tmt_network' => $phoneData['network'] ?? 'Unknown',
                'tmt_mcc' => $phoneData['mcc'] ?? $providerData['coverage']->mcc,
                'tmt_mnc' => $phoneData['mnc'] ?? $providerData['coverage']->mnc,
                'tmt_present' => $phoneData['present'] ?? 'unknown',
                'tmt_status' => $phoneData['status'] ?? 1,
                'tmt_ported' => $phoneData['ported'] ?? false,
                'tmt_cic' => $phoneData['cic'] ?? null,
                'tmt_imsi' => $phoneData['imsi'] ?? null,
                'tmt_trxid' => $phoneData['trxid'] ?? Str::random(8),
            ]);

            Log::info('TMT verification successful', [
                'phone_number' => $phoneNumber,
                'verification_id' => $verification->id,
                'status' => $phoneData['status'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'verification' => $verification,
                'provider_data' => $verification->getProviderData(),
                'cost' => $providerData['cost'],
                'coverage_country' => $providerData['coverage']->iso2 ?? 'Unknown',
                'coverage_country_name' => $this->getCountryName($providerData['coverage']->iso2 ?? null)
            ];

        } catch (\Exception $e) {
            Log::error('TMT API call failed', [
                'phone_number' => $phoneNumber,
                'api_url' => $apiUrl ?? 'URL not constructed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'TMT API call failed: ' . $e->getMessage()
            ];
        }
    }

    protected function callIPQS(string $phoneNumber, array $providerData)
    {
        $apiProvider = ApiProvider::where('name', 'IPQS')->first();

        if (!$apiProvider) {
            return ['success' => false, 'message' => 'IPQS provider not configured'];
        }

        try {
            $response = Http::get($apiProvider->base_url . '/phone', [
                'key' => $apiProvider->api_key,
                'phone' => $phoneNumber
            ]);

            $verification = Verification::create([
                'phone_number' => $phoneNumber,
                'provider' => 'IPQS',
                'cost' => $providerData['cost'],
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

    /**
     * Check network prefix for a phone number (for real-time validation UI)
     * Returns prefix matching info, coverage status, and estimated costs
     * Progressive detection: shows country first, then network as user types
     */
    public function checkNetworkPrefix(string $phoneNumber): array
    {
        try {
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            if (empty($cleanNumber)) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format'
                ];
            }

            // FIRST: Try to detect country by country code mapping
            $countryCodeResult = $this->detectByCountryCode($cleanNumber);
            if ($countryCodeResult) {
                return $countryCodeResult;
            }

            // If no valid country code found, DON'T search database
            // This prevents "2" from matching "20", "21", "212", etc.
            return [
                'success' => false,
                'error' => 'Invalid or incomplete country code. Please enter a valid country code.'
            ];

        } catch (\Exception $e) {
            Log::error('Error in checkNetworkPrefix', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error checking network prefix: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Batch verification method for backward compatibility
     */
    public function verifyBatch(array $phoneNumbers, ?string $dataFreshness = null)
    {
        $results = [];
        $stats = [
            'cache_hits' => 0,
            'database_hits' => 0,
            'api_calls' => 0
        ];

        $processed = 0;
        $liveCoverageCount = 0;
        $noCoverageCount = 0;
        $errorCount = 0;

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                // First check if it has coverage
                $coverageCheck = $this->checkNetworkPrefix($phoneNumber);

                if (!$coverageCheck['success'] || !$coverageCheck['live_coverage']) {
                    $noCoverageCount++;
                    $processed++;
                    continue; // Skip numbers without live coverage
                }

                // Try advanced verification with data freshness parameter
                // The advancedVerify method will handle the freshness logic internally
                $result = $this->advancedVerify($phoneNumber, $dataFreshness);

                if ($result['success']) {
                    $liveCoverageCount++;

                    if (isset($result['cached']) && $result['cached']) {
                        $stats['cache_hits']++;
                    } else {
                        $stats['api_calls']++;
                    }

                    $results[] = $result['verification'];
                } else {
                    $errorCount++;
                }

                $processed++;

            } catch (\Exception $e) {
                $errorCount++;
                $processed++;
            }
        }

        return [
            'success' => true,
            'processed' => $processed,
            'data' => $results,
            'live_coverage_count' => $liveCoverageCount,
            'no_coverage_count' => $noCoverageCount,
            'error_count' => $errorCount,
            'statistics' => $stats,
            'cache_message' => "Processed {$processed} numbers with {$stats['api_calls']} API calls and {$stats['cache_hits']} cache hits"
        ];
    }

    /**
     * Calculate cost preview for batch verification
     * Finds the cheapest provider for each number and groups by country
     */
    public function calculateBatchCost(array $phoneNumbers): array
    {
        $costBreakdown = [];
        $totalCost = 0;
        $numbersWithCoverage = 0;
        $numbersWithoutCoverage = 0;
        $providerCounts = ['TMT' => 0, 'IPQS' => 0];
        $countryBreakdown = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);
            $prefixes = $this->generatePrefixes($cleanNumber);

            $cheapestOption = null;
            $lowestCost = PHP_FLOAT_MAX;

            // Check TMT coverage
            foreach ($prefixes as $prefix) {
                $tmtCoverage = TmtCoverage::where('prefix', $prefix)
                    ->where('live_coverage', true)
                    ->first();

                if ($tmtCoverage && $tmtCoverage->rate < $lowestCost) {
                    $lowestCost = $tmtCoverage->rate;
                    $cheapestOption = [
                        'provider' => 'TMT',
                        'cost' => $tmtCoverage->rate,
                        'country' => $this->getCountryName($tmtCoverage->iso2),
                        'country_code' => $tmtCoverage->iso2,
                        'network' => $tmtCoverage->network_name,
                        'prefix' => $prefix
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
                    $cheapestOption = [
                        'provider' => 'IPQS',
                        'cost' => $ipqsCoverage->price,
                        'country' => $ipqsCoverage->country,
                        'country_code' => $ipqsCoverage->country,
                        'network' => $ipqsCoverage->carrier_name,
                        'prefix' => $prefix
                    ];
                }
            }

            if ($cheapestOption) {
                $numbersWithCoverage++;
                $totalCost += $cheapestOption['cost'];
                $providerCounts[$cheapestOption['provider']]++;

                // Group by country for breakdown
                $countryKey = $cheapestOption['country'];
                if (!isset($countryBreakdown[$countryKey])) {
                    $countryBreakdown[$countryKey] = [
                        'country' => $cheapestOption['country'],
                        'count' => 0,
                        'total_cost' => 0,
                        'avg_cost' => 0,
                        'providers' => []
                    ];
                }

                $countryBreakdown[$countryKey]['count']++;
                $countryBreakdown[$countryKey]['total_cost'] += $cheapestOption['cost'];

                // Track provider usage per country
                $provider = $cheapestOption['provider'];
                if (!isset($countryBreakdown[$countryKey]['providers'][$provider])) {
                    $countryBreakdown[$countryKey]['providers'][$provider] = 0;
                }
                $countryBreakdown[$countryKey]['providers'][$provider]++;

                $costBreakdown[] = [
                    'phone_number' => $phoneNumber,
                    'provider' => $cheapestOption['provider'],
                    'cost' => $cheapestOption['cost'],
                    'country' => $cheapestOption['country'],
                    'network' => $cheapestOption['network']
                ];
            } else {
                $numbersWithoutCoverage++;
                $costBreakdown[] = [
                    'phone_number' => $phoneNumber,
                    'provider' => 'None',
                    'cost' => 0,
                    'country' => 'Unknown',
                    'network' => 'No Coverage',
                    'warning' => 'This number is not supported by any provider'
                ];
            }
        }

        // Calculate average cost per country
        foreach ($countryBreakdown as $key => $data) {
            $countryBreakdown[$key]['avg_cost'] = $data['total_cost'] / $data['count'];
        }

        // Sort country breakdown by count (descending)
        uasort($countryBreakdown, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return [
            'success' => true,
            'total_numbers' => count($phoneNumbers),
            'numbers_with_coverage' => $numbersWithCoverage,
            'numbers_without_coverage' => $numbersWithoutCoverage,
            'total_cost' => $totalCost,
            'avg_cost_per_number' => $numbersWithCoverage > 0 ? $totalCost / $numbersWithCoverage : 0,
            'provider_usage' => $providerCounts,
            'country_breakdown' => array_values($countryBreakdown),
            'details' => $costBreakdown,
            'cost_message' => sprintf(
                'Estimated cost: $%s for %d numbers with coverage. %d numbers without coverage will be skipped.',
                number_format($totalCost, 6),
                $numbersWithCoverage,
                $numbersWithoutCoverage
            )
        ];
    }

    /**
     * Detect country by international calling code
     * This ensures exact matching - only shows country when the FULL country code is entered
     */
    private function detectByCountryCode(string $cleanNumber): ?array
    {
        // Complete mapping of country codes (from ITU E.164 standard)
        // Format: 'code' => ['countries' => [...], 'needs_area_code' => bool]
        $countryCodeMap = [
            // Single digit codes
            '1' => ['needs_area_code' => true], // NANP - handled separately
            '7' => ['countries' => ['Russia', 'Kazakhstan'], 'default' => 'Russia'],

            // Two digit codes (20-99)
            '20' => ['countries' => ['Egypt']],
            '27' => ['countries' => ['South Africa']],
            '30' => ['countries' => ['Greece']],
            '31' => ['countries' => ['Netherlands']],
            '32' => ['countries' => ['Belgium']],
            '33' => ['countries' => ['France']],
            '34' => ['countries' => ['Spain']],
            '36' => ['countries' => ['Hungary']],
            '39' => ['countries' => ['Italy']],
            '40' => ['countries' => ['Romania']],
            '41' => ['countries' => ['Switzerland']],
            '43' => ['countries' => ['Austria']],
            '44' => ['countries' => ['United Kingdom']],
            '45' => ['countries' => ['Denmark']],
            '46' => ['countries' => ['Sweden']],
            '47' => ['countries' => ['Norway']],
            '48' => ['countries' => ['Poland']],
            '49' => ['countries' => ['Germany']],
            '51' => ['countries' => ['Peru']],
            '52' => ['countries' => ['Mexico']],
            '53' => ['countries' => ['Cuba']],
            '54' => ['countries' => ['Argentina']],
            '55' => ['countries' => ['Brazil']],
            '56' => ['countries' => ['Chile']],
            '57' => ['countries' => ['Colombia']],
            '58' => ['countries' => ['Venezuela']],
            '60' => ['countries' => ['Malaysia']],
            '61' => ['countries' => ['Australia']],
            '62' => ['countries' => ['Indonesia']],
            '63' => ['countries' => ['Philippines']],
            '64' => ['countries' => ['New Zealand']],
            '65' => ['countries' => ['Singapore']],
            '66' => ['countries' => ['Thailand']],
            '81' => ['countries' => ['Japan']],
            '82' => ['countries' => ['South Korea']],
            '84' => ['countries' => ['Vietnam']],
            '86' => ['countries' => ['China']],
            '90' => ['countries' => ['Turkey']],
            '91' => ['countries' => ['India']],
            '92' => ['countries' => ['Pakistan']],
            '93' => ['countries' => ['Afghanistan']],
            '94' => ['countries' => ['Sri Lanka']],
            '95' => ['countries' => ['Myanmar']],
            '98' => ['countries' => ['Iran']],

            // Three digit codes (200-999)
            '212' => ['countries' => ['Morocco']],
            '213' => ['countries' => ['Algeria']],
            '216' => ['countries' => ['Tunisia']],
            '218' => ['countries' => ['Libya']],
            '220' => ['countries' => ['Gambia']],
            '221' => ['countries' => ['Senegal']],
            '222' => ['countries' => ['Mauritania']],
            '223' => ['countries' => ['Mali']],
            '224' => ['countries' => ['Guinea']],
            '225' => ['countries' => ['Ivory Coast']],
            '226' => ['countries' => ['Burkina Faso']],
            '227' => ['countries' => ['Niger']],
            '228' => ['countries' => ['Togo']],
            '229' => ['countries' => ['Benin']],
            '230' => ['countries' => ['Mauritius']],
            '231' => ['countries' => ['Liberia']],
            '232' => ['countries' => ['Sierra Leone']],
            '233' => ['countries' => ['Ghana']],
            '234' => ['countries' => ['Nigeria']],
            '235' => ['countries' => ['Chad']],
            '236' => ['countries' => ['Central African Republic']],
            '237' => ['countries' => ['Cameroon']],
            '238' => ['countries' => ['Cape Verde']],
            '239' => ['countries' => ['São Tomé and Príncipe']],
            '240' => ['countries' => ['Equatorial Guinea']],
            '241' => ['countries' => ['Gabon']],
            '242' => ['countries' => ['Republic of the Congo']],
            '243' => ['countries' => ['Democratic Republic of the Congo']],
            '244' => ['countries' => ['Angola']],
            '245' => ['countries' => ['Guinea-Bissau']],
            '246' => ['countries' => ['British Indian Ocean Territory']],
            '248' => ['countries' => ['Seychelles']],
            '249' => ['countries' => ['Sudan']],
            '250' => ['countries' => ['Rwanda']],
            '251' => ['countries' => ['Ethiopia']],
            '252' => ['countries' => ['Somalia']],
            '253' => ['countries' => ['Djibouti']],
            '254' => ['countries' => ['Kenya']],
            '255' => ['countries' => ['Tanzania']],
            '256' => ['countries' => ['Uganda']],
            '257' => ['countries' => ['Burundi']],
            '258' => ['countries' => ['Mozambique']],
            '260' => ['countries' => ['Zambia']],
            '261' => ['countries' => ['Madagascar']],
            '262' => ['countries' => ['Réunion', 'Mayotte']],
            '263' => ['countries' => ['Zimbabwe']],
            '264' => ['countries' => ['Namibia']],
            '265' => ['countries' => ['Malawi']],
            '266' => ['countries' => ['Lesotho']],
            '267' => ['countries' => ['Botswana']],
            '268' => ['countries' => ['Eswatini']],
            '269' => ['countries' => ['Comoros']],
            '290' => ['countries' => ['Saint Helena']],
            '291' => ['countries' => ['Eritrea']],
            '297' => ['countries' => ['Aruba']],
            '298' => ['countries' => ['Faroe Islands']],
            '299' => ['countries' => ['Greenland']],
            '350' => ['countries' => ['Gibraltar']],
            '351' => ['countries' => ['Portugal']],
            '352' => ['countries' => ['Luxembourg']],
            '353' => ['countries' => ['Ireland']],
            '354' => ['countries' => ['Iceland']],
            '355' => ['countries' => ['Albania']],
            '356' => ['countries' => ['Malta']],
            '357' => ['countries' => ['Cyprus']],
            '358' => ['countries' => ['Finland']],
            '359' => ['countries' => ['Bulgaria']],
            '370' => ['countries' => ['Lithuania']],
            '371' => ['countries' => ['Latvia']],
            '372' => ['countries' => ['Estonia']],
            '373' => ['countries' => ['Moldova']],
            '374' => ['countries' => ['Armenia']],
            '375' => ['countries' => ['Belarus']],
            '376' => ['countries' => ['Andorra']],
            '377' => ['countries' => ['Monaco']],
            '378' => ['countries' => ['San Marino']],
            '380' => ['countries' => ['Ukraine']],
            '381' => ['countries' => ['Serbia']],
            '382' => ['countries' => ['Montenegro']],
            '383' => ['countries' => ['Kosovo']],
            '385' => ['countries' => ['Croatia']],
            '386' => ['countries' => ['Slovenia']],
            '387' => ['countries' => ['Bosnia and Herzegovina']],
            '389' => ['countries' => ['North Macedonia']],
            '420' => ['countries' => ['Czech Republic']],
            '421' => ['countries' => ['Slovakia']],
            '423' => ['countries' => ['Liechtenstein']],
            '500' => ['countries' => ['Falkland Islands']],
            '501' => ['countries' => ['Belize']],
            '502' => ['countries' => ['Guatemala']],
            '503' => ['countries' => ['El Salvador']],
            '504' => ['countries' => ['Honduras']],
            '505' => ['countries' => ['Nicaragua']],
            '506' => ['countries' => ['Costa Rica']],
            '507' => ['countries' => ['Panama']],
            '508' => ['countries' => ['Saint Pierre and Miquelon']],
            '509' => ['countries' => ['Haiti']],
            '590' => ['countries' => ['Guadeloupe', 'Saint Martin']],
            '591' => ['countries' => ['Bolivia']],
            '592' => ['countries' => ['Guyana']],
            '593' => ['countries' => ['Ecuador']],
            '594' => ['countries' => ['French Guiana']],
            '595' => ['countries' => ['Paraguay']],
            '596' => ['countries' => ['Martinique']],
            '597' => ['countries' => ['Suriname']],
            '598' => ['countries' => ['Uruguay']],
            '599' => ['countries' => ['Curaçao', 'Caribbean Netherlands']],
            '670' => ['countries' => ['East Timor']],
            '672' => ['countries' => ['Norfolk Island']],
            '673' => ['countries' => ['Brunei']],
            '674' => ['countries' => ['Nauru']],
            '675' => ['countries' => ['Papua New Guinea']],
            '676' => ['countries' => ['Tonga']],
            '677' => ['countries' => ['Solomon Islands']],
            '678' => ['countries' => ['Vanuatu']],
            '679' => ['countries' => ['Fiji']],
            '680' => ['countries' => ['Palau']],
            '681' => ['countries' => ['Wallis and Futuna']],
            '682' => ['countries' => ['Cook Islands']],
            '683' => ['countries' => ['Niue']],
            '685' => ['countries' => ['Samoa']],
            '686' => ['countries' => ['Kiribati']],
            '687' => ['countries' => ['New Caledonia']],
            '688' => ['countries' => ['Tuvalu']],
            '689' => ['countries' => ['French Polynesia']],
            '690' => ['countries' => ['Tokelau']],
            '691' => ['countries' => ['Micronesia']],
            '692' => ['countries' => ['Marshall Islands']],
            '850' => ['countries' => ['North Korea']],
            '852' => ['countries' => ['Hong Kong']],
            '853' => ['countries' => ['Macau']],
            '855' => ['countries' => ['Cambodia']],
            '856' => ['countries' => ['Laos']],
            '880' => ['countries' => ['Bangladesh']],
            '886' => ['countries' => ['Taiwan']],
            '960' => ['countries' => ['Maldives']],
            '961' => ['countries' => ['Lebanon']],
            '962' => ['countries' => ['Jordan']],
            '963' => ['countries' => ['Syria']],
            '964' => ['countries' => ['Iraq']],
            '965' => ['countries' => ['Kuwait']],
            '966' => ['countries' => ['Saudi Arabia']],
            '967' => ['countries' => ['Yemen']],
            '968' => ['countries' => ['Oman']],
            '970' => ['countries' => ['Palestine']],
            '971' => ['countries' => ['United Arab Emirates']],
            '972' => ['countries' => ['Israel']],
            '973' => ['countries' => ['Bahrain']],
            '974' => ['countries' => ['Qatar']],
            '975' => ['countries' => ['Bhutan']],
            '976' => ['countries' => ['Mongolia']],
            '977' => ['countries' => ['Nepal']],
            '992' => ['countries' => ['Tajikistan']],
            '993' => ['countries' => ['Turkmenistan']],
            '994' => ['countries' => ['Azerbaijan']],
            '995' => ['countries' => ['Georgia']],
            '996' => ['countries' => ['Kyrgyzstan']],
            '998' => ['countries' => ['Uzbekistan']],
        ];

        // Try to match country codes from longest to shortest (4, 3, 2, 1 digit)
        // IMPORTANT: We search longest-first to match "212" before "21"
        for ($length = 4; $length >= 1; $length--) {
            if (strlen($cleanNumber) >= $length) {
                $code = substr($cleanNumber, 0, $length);

                // CRITICAL CHECK: Only proceed if this EXACT code exists in our map
                if (isset($countryCodeMap[$code])) {
                    $mapping = $countryCodeMap[$code];

                    // Special handling for NANP (code 1)
                    if ($code === '1' && isset($mapping['needs_area_code'])) {
                        return $this->detectNANPCountry($cleanNumber);
                    }

                    // Found an exact match in country code map!
                    $countries = $mapping['countries'];
                    $countryName = count($countries) > 1
                        ? implode(' / ', $countries)
                        : $countries[0];

                    // Now check if user has typed more digits to detect network/operator
                    if (strlen($cleanNumber) > $length) {
                        // User has typed more than just the country code
                        // Check database for network/operator prefix match
                        $networkInfo = $this->findNetworkByPrefix($cleanNumber);

                        if ($networkInfo) {
                            // Found network/operator in database!
                            return [
                                'success' => true,
                                'prefix' => $networkInfo['prefix'],
                                'country_name' => $networkInfo['country_name'],
                                'country_code' => $code,
                                'iso2' => $networkInfo['iso2'],
                                'network_name' => $networkInfo['network_name'],
                                'network_id' => $networkInfo['network_id'],
                                'live_coverage' => $networkInfo['live_coverage'],
                                'cost' => $networkInfo['cost'],
                                'provider' => $networkInfo['provider'],
                                'partial_match' => strlen($cleanNumber) < 10,
                                'prefix_length' => strlen($networkInfo['prefix'])
                            ];
                        }
                    }

                    // Just country code, no network detected yet
                    return [
                        'success' => true,
                        'prefix' => $code,
                        'country_name' => $countryName,
                        'country_code' => $code,
                        'iso2' => null,
                        'network_name' => 'Country Code +' . $code,
                        'network_id' => null,
                        'live_coverage' => true,
                        'cost' => null,
                        'provider' => 'Map',
                        'partial_match' => strlen($cleanNumber) < 10,
                        'prefix_length' => $length,
                        'note' => strlen($cleanNumber) < 10 ? 'Enter more digits for network detection' : 'Country detected from map'
                    ];
                }
            }
        }

        // No country code match found - return null (NO fallback)
        return null;
    }

    /**
     * Detect NANP (North American Numbering Plan) country based on area code
     * Country Code +1 is shared by USA, Canada, and Caribbean nations
     * We need to check the area code (first 3 digits after +1) to determine the specific country
     */
    private function detectNANPCountry(string $cleanNumber): ?array
    {
        // NANP Area Code Mapping (Country Code +1)
        $nanpAreaCodes = [
            // Caribbean and Territories (specific area codes)
            '242' => ['country' => 'Bahamas', 'iso2' => 'BS'],
            '246' => ['country' => 'Barbados', 'iso2' => 'BB'],
            '264' => ['country' => 'Anguilla', 'iso2' => 'AI'],
            '268' => ['country' => 'Antigua and Barbuda', 'iso2' => 'AG'],
            '284' => ['country' => 'British Virgin Islands', 'iso2' => 'VG'],
            '340' => ['country' => 'U.S. Virgin Islands', 'iso2' => 'VI'],
            '345' => ['country' => 'Cayman Islands', 'iso2' => 'KY'],
            '441' => ['country' => 'Bermuda', 'iso2' => 'BM'],
            '473' => ['country' => 'Grenada', 'iso2' => 'GD'],
            '649' => ['country' => 'Turks and Caicos Islands', 'iso2' => 'TC'],
            '658' => ['country' => 'Jamaica', 'iso2' => 'JM'],
            '664' => ['country' => 'Montserrat', 'iso2' => 'MS'],
            '670' => ['country' => 'Northern Mariana Islands', 'iso2' => 'MP'],
            '671' => ['country' => 'Guam', 'iso2' => 'GU'],
            '684' => ['country' => 'American Samoa', 'iso2' => 'AS'],
            '721' => ['country' => 'Sint Maarten', 'iso2' => 'SX'],
            '758' => ['country' => 'Saint Lucia', 'iso2' => 'LC'],
            '767' => ['country' => 'Dominica', 'iso2' => 'DM'],
            '784' => ['country' => 'Saint Vincent and the Grenadines', 'iso2' => 'VC'],
            '787' => ['country' => 'Puerto Rico', 'iso2' => 'PR'],
            '809' => ['country' => 'Dominican Republic', 'iso2' => 'DO'],
            '829' => ['country' => 'Dominican Republic', 'iso2' => 'DO'],
            '849' => ['country' => 'Dominican Republic', 'iso2' => 'DO'],
            '868' => ['country' => 'Trinidad and Tobago', 'iso2' => 'TT'],
            '869' => ['country' => 'Saint Kitts and Nevis', 'iso2' => 'KN'],
            '876' => ['country' => 'Jamaica', 'iso2' => 'JM'],
            '939' => ['country' => 'Puerto Rico', 'iso2' => 'PR'],
        ];

        // If only "1" is entered, show "United States / Canada"
        if (strlen($cleanNumber) == 1) {
            return [
                'success' => true,
                'prefix' => '1',
                'country_name' => 'United States / Canada',
                'country_code' => '1',
                'iso2' => 'US/CA',
                'network_name' => 'NANP (North American Numbering Plan)',
                'network_id' => null,
                'live_coverage' => true,
                'cost' => null,
                'provider' => 'Multiple',
                'partial_match' => true,
                'prefix_length' => 1,
                'note' => 'Enter area code to determine specific country'
            ];
        }

        // If we have at least 4 digits (1 + 3 digit area code)
        if (strlen($cleanNumber) >= 4) {
            $areaCode = substr($cleanNumber, 1, 3);

            // Check if this area code belongs to a specific Caribbean nation or territory
            if (isset($nanpAreaCodes[$areaCode])) {
                $countryInfo = $nanpAreaCodes[$areaCode];

                // Check if user has typed more digits for network detection
                if (strlen($cleanNumber) > 4) {
                    $networkInfo = $this->findNetworkByPrefix($cleanNumber);

                    if ($networkInfo) {
                        return [
                            'success' => true,
                            'prefix' => $networkInfo['prefix'],
                            'country_name' => $countryInfo['country'],
                            'country_code' => '1',
                            'iso2' => $countryInfo['iso2'],
                            'network_name' => $networkInfo['network_name'],
                            'network_id' => $networkInfo['network_id'],
                            'live_coverage' => $networkInfo['live_coverage'],
                            'cost' => $networkInfo['cost'],
                            'provider' => $networkInfo['provider'],
                            'partial_match' => strlen($cleanNumber) < 10,
                            'prefix_length' => strlen($networkInfo['prefix'])
                        ];
                    }
                }

                // Just area code, no network detected yet
                return [
                    'success' => true,
                    'prefix' => '1' . $areaCode,
                    'country_name' => $countryInfo['country'],
                    'country_code' => '1',
                    'iso2' => $countryInfo['iso2'],
                    'network_name' => 'Area Code ' . $areaCode,
                    'network_id' => null,
                    'live_coverage' => true,
                    'cost' => null,
                    'provider' => 'Map',
                    'partial_match' => strlen($cleanNumber) < 10,
                    'prefix_length' => 4,
                    'note' => strlen($cleanNumber) < 10 ? 'Enter more digits for network detection' : 'Country detected from NANP map'
                ];
            }

            // Area code not in Caribbean list, so it's USA or Canada
            // Check if user has typed more digits for network detection
            if (strlen($cleanNumber) > 4) {
                $networkInfo = $this->findNetworkByPrefix($cleanNumber);

                if ($networkInfo) {
                    // Determine if US or Canada from network info
                    $country = ($networkInfo['iso2'] == 'CA') ? 'Canada' : 'United States';
                    return [
                        'success' => true,
                        'prefix' => $networkInfo['prefix'],
                        'country_name' => $country,
                        'country_code' => '1',
                        'iso2' => $networkInfo['iso2'],
                        'network_name' => $networkInfo['network_name'],
                        'network_id' => $networkInfo['network_id'],
                        'live_coverage' => $networkInfo['live_coverage'],
                        'cost' => $networkInfo['cost'],
                        'provider' => $networkInfo['provider'],
                        'partial_match' => strlen($cleanNumber) < 10,
                        'prefix_length' => strlen($networkInfo['prefix'])
                    ];
                }
            }

            // Just area code, no network detected yet
            return [
                'success' => true,
                'prefix' => '1' . $areaCode,
                'country_name' => 'United States / Canada',
                'country_code' => '1',
                'iso2' => 'US/CA',
                'network_name' => 'Area Code ' . $areaCode,
                'network_id' => null,
                'live_coverage' => true,
                'cost' => null,
                'provider' => 'Map',
                'partial_match' => strlen($cleanNumber) < 10,
                'prefix_length' => 4,
                'note' => strlen($cleanNumber) < 10 ? 'Enter more digits for network detection' : 'US/Canada number detected from NANP map'
            ];
        }

        // If we have 2-3 digits (e.g., "12" or "123"), show partial area code
        if (strlen($cleanNumber) >= 2) {
            $partialAreaCode = substr($cleanNumber, 1);
            return [
                'success' => true,
                'prefix' => $cleanNumber,
                'country_name' => 'United States / Canada / Caribbean',
                'country_code' => '1',
                'iso2' => 'NANP',
                'network_name' => 'Partial Area Code: ' . $partialAreaCode,
                'network_id' => null,
                'live_coverage' => true,
                'cost' => null,
                'provider' => 'Multiple',
                'partial_match' => true,
                'prefix_length' => strlen($cleanNumber),
                'note' => 'Enter complete 3-digit area code'
            ];
        }

        return null;
    }

    /**
     * Find network/operator by prefix in coverage tables
     * This searches from longest to shortest prefix to find the best match
     */
    private function findNetworkByPrefix(string $cleanNumber): ?array
    {
        // Generate prefixes from longest to shortest
        $prefixes = $this->generatePrefixes($cleanNumber);

        // Search TMT coverage first (usually better coverage)
        foreach ($prefixes as $prefix) {
            $tmtCoverage = TmtCoverage::where('prefix', $prefix)
                ->where('live_coverage', true)
                ->first();

            if ($tmtCoverage) {
                return [
                    'prefix' => $tmtCoverage->prefix,
                    'country_name' => $this->getCountryName($tmtCoverage->iso2),
                    'iso2' => $tmtCoverage->iso2,
                    'network_name' => $tmtCoverage->network_name,
                    'network_id' => $tmtCoverage->network_id,
                    'live_coverage' => $tmtCoverage->live_coverage,
                    'cost' => $tmtCoverage->rate,
                    'provider' => 'TMT'
                ];
            }
        }

        // Check IPQS coverage as fallback
        foreach ($prefixes as $prefix) {
            $ipqsCoverage = IpqsCoverage::where('number_prefix', $prefix)
                ->where('support_provider', true)
                ->first();

            if ($ipqsCoverage) {
                return [
                    'prefix' => $ipqsCoverage->number_prefix,
                    'country_name' => $ipqsCoverage->country,
                    'iso2' => $ipqsCoverage->cc,
                    'network_name' => $ipqsCoverage->carrier_name,
                    'network_id' => $ipqsCoverage->operator_id,
                    'live_coverage' => $ipqsCoverage->support_provider,
                    'cost' => $ipqsCoverage->price,
                    'provider' => 'IPQS'
                ];
            }
        }

        return null;
    }

    /**
     * Get country name from ISO2 code
     */
    private function getCountryName(?string $iso2): string
    {
        if (!$iso2) return 'Unknown';

        $countries = [
            // Asia
            'AE' => 'United Arab Emirates',
            'AF' => 'Afghanistan',
            'AM' => 'Armenia',
            'AZ' => 'Azerbaijan',
            'BD' => 'Bangladesh',
            'BH' => 'Bahrain',
            'BN' => 'Brunei',
            'BT' => 'Bhutan',
            'CN' => 'China',
            'GE' => 'Georgia',
            'HK' => 'Hong Kong',
            'ID' => 'Indonesia',
            'IL' => 'Israel',
            'IN' => 'India',
            'IQ' => 'Iraq',
            'IR' => 'Iran',
            'JO' => 'Jordan',
            'JP' => 'Japan',
            'KG' => 'Kyrgyzstan',
            'KH' => 'Cambodia',
            'KP' => 'North Korea',
            'KR' => 'South Korea',
            'KW' => 'Kuwait',
            'KZ' => 'Kazakhstan',
            'LA' => 'Laos',
            'LB' => 'Lebanon',
            'LK' => 'Sri Lanka',
            'MM' => 'Myanmar',
            'MN' => 'Mongolia',
            'MO' => 'Macau',
            'MV' => 'Maldives',
            'MY' => 'Malaysia',
            'NP' => 'Nepal',
            'OM' => 'Oman',
            'PH' => 'Philippines',
            'PK' => 'Pakistan',
            'PS' => 'Palestine',
            'QA' => 'Qatar',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'SY' => 'Syria',
            'TH' => 'Thailand',
            'TJ' => 'Tajikistan',
            'TL' => 'Timor-Leste',
            'TM' => 'Turkmenistan',
            'TW' => 'Taiwan',
            'UZ' => 'Uzbekistan',
            'VN' => 'Vietnam',
            'YE' => 'Yemen',

            // Europe
            'AL' => 'Albania',
            'AD' => 'Andorra',
            'AT' => 'Austria',
            'BA' => 'Bosnia and Herzegovina',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'BY' => 'Belarus',
            'CH' => 'Switzerland',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'MC' => 'Monaco',
            'MD' => 'Moldova',
            'ME' => 'Montenegro',
            'MK' => 'North Macedonia',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RS' => 'Serbia',
            'RU' => 'Russia',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia',
            'SM' => 'San Marino',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'VA' => 'Vatican City',
            'XK' => 'Kosovo',

            // North America & Caribbean
            'AG' => 'Antigua and Barbuda',
            'AI' => 'Anguilla',
            'AW' => 'Aruba',
            'BB' => 'Barbados',
            'BM' => 'Bermuda',
            'BS' => 'Bahamas',
            'BZ' => 'Belize',
            'CA' => 'Canada',
            'CR' => 'Costa Rica',
            'CU' => 'Cuba',
            'CW' => 'Curaçao',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'GD' => 'Grenada',
            'GL' => 'Greenland',
            'GP' => 'Guadeloupe',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'HT' => 'Haiti',
            'JM' => 'Jamaica',
            'KN' => 'Saint Kitts and Nevis',
            'KY' => 'Cayman Islands',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'MQ' => 'Martinique',
            'MS' => 'Montserrat',
            'MX' => 'Mexico',
            'NI' => 'Nicaragua',
            'PA' => 'Panama',
            'PM' => 'Saint Pierre and Miquelon',
            'PR' => 'Puerto Rico',
            'SV' => 'El Salvador',
            'SX' => 'Sint Maarten',
            'TC' => 'Turks and Caicos Islands',
            'TT' => 'Trinidad and Tobago',
            'US' => 'United States',
            'VC' => 'Saint Vincent and the Grenadines',
            'VG' => 'British Virgin Islands',
            'VI' => 'U.S. Virgin Islands',

            // South America
            'AR' => 'Argentina',
            'BO' => 'Bolivia',
            'BR' => 'Brazil',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'EC' => 'Ecuador',
            'GY' => 'Guyana',
            'PE' => 'Peru',
            'PY' => 'Paraguay',
            'SR' => 'Suriname',
            'UY' => 'Uruguay',
            'VE' => 'Venezuela',

            // Africa
            'AO' => 'Angola',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'BJ' => 'Benin',
            'BW' => 'Botswana',
            'CD' => 'Democratic Republic of the Congo',
            'CF' => 'Central African Republic',
            'CG' => 'Republic of the Congo',
            'CI' => 'Ivory Coast',
            'CM' => 'Cameroon',
            'CV' => 'Cape Verde',
            'DJ' => 'Djibouti',
            'DZ' => 'Algeria',
            'EG' => 'Egypt',
            'EH' => 'Western Sahara',
            'ER' => 'Eritrea',
            'ET' => 'Ethiopia',
            'GA' => 'Gabon',
            'GH' => 'Ghana',
            'GM' => 'Gambia',
            'GN' => 'Guinea',
            'GQ' => 'Equatorial Guinea',
            'GW' => 'Guinea-Bissau',
            'KE' => 'Kenya',
            'KM' => 'Comoros',
            'LR' => 'Liberia',
            'LS' => 'Lesotho',
            'LY' => 'Libya',
            'MA' => 'Morocco',
            'MG' => 'Madagascar',
            'ML' => 'Mali',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MW' => 'Malawi',
            'MZ' => 'Mozambique',
            'NA' => 'Namibia',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'RW' => 'Rwanda',
            'SC' => 'Seychelles',
            'SD' => 'Sudan',
            'SL' => 'Sierra Leone',
            'SN' => 'Senegal',
            'SO' => 'Somalia',
            'SS' => 'South Sudan',
            'ST' => 'Sao Tome and Principe',
            'SZ' => 'Eswatini',
            'TD' => 'Chad',
            'TG' => 'Togo',
            'TN' => 'Tunisia',
            'TZ' => 'Tanzania',
            'UG' => 'Uganda',
            'ZA' => 'South Africa',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',

            // Oceania
            'AU' => 'Australia',
            'FJ' => 'Fiji',
            'FM' => 'Micronesia',
            'KI' => 'Kiribati',
            'MH' => 'Marshall Islands',
            'NR' => 'Nauru',
            'NZ' => 'New Zealand',
            'PG' => 'Papua New Guinea',
            'PW' => 'Palau',
            'SB' => 'Solomon Islands',
            'TO' => 'Tonga',
            'TV' => 'Tuvalu',
            'VU' => 'Vanuatu',
            'WS' => 'Samoa',
        ];

        return $countries[$iso2] ?? strtoupper($iso2);
    }
}