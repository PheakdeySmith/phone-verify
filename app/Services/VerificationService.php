<?php

namespace App\Services;

use Exception;
use App\Models\NetworkPrefix;
use App\Models\Verification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class VerificationService 
{
    private $baseUrl;
    private $apiKey;
    private $apiSecret;

    public function __construct()
    {
        $this->baseUrl = env('TMT_API_URL', 'https://api.tmtvelocity.com/live');
        $this->apiKey = env('API_KEY');
        $this->apiSecret = env('API_SECRET');
    }

    /**
     * Check phone number against network_prefixes table to determine if it has live coverage
     * and identify the network/carrier before making API calls
     */
    public function checkNetworkPrefix($phoneNumber)
    {
        try {
            // Clean phone number (remove non-digits)
            $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            if (empty($cleanNumber)) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format'
                ];
            }

            // Try to find matching prefix - start with longest possible prefix and work down
            $maxPrefixLength = 6; // Adjust based on your data
            $networkInfo = null;

            // First, try to find exact prefix match for complete numbers (with length validation)
            for ($i = $maxPrefixLength; $i >= 2; $i--) {
                $prefix = substr($cleanNumber, 0, $i);

                $networkInfo = NetworkPrefix::where('prefix', $prefix)
                    ->where('min_length', '<=', strlen($cleanNumber))
                    ->where('max_length', '>=', strlen($cleanNumber))
                    ->first();

                if ($networkInfo) {
                    break;
                }
            }

            // If no exact match with length validation, try to find prefix match for partial numbers
            if (!$networkInfo) {
                for ($i = $maxPrefixLength; $i >= 2; $i--) {
                    $prefix = substr($cleanNumber, 0, $i);

                    $networkInfo = NetworkPrefix::where('prefix', $prefix)->first();

                    if ($networkInfo) {
                        // Found a matching prefix, but need to check if this is a valid partial number
                        // If the input is longer than the prefix but shorter than expected, it's partial
                        if (strlen($cleanNumber) > strlen($prefix) && strlen($cleanNumber) < $networkInfo->min_length) {
                            return [
                                'success' => true,
                                'phone_number' => $cleanNumber,
                                'prefix' => $networkInfo->prefix,
                                'country_name' => $networkInfo->country_name,
                                'network_name' => $networkInfo->network_name,
                                'mcc' => $networkInfo->mcc,
                                'mnc' => $networkInfo->mnc,
                                'live_coverage' => $networkInfo->live_coverage,
                                'min_length' => $networkInfo->min_length,
                                'max_length' => $networkInfo->max_length,
                                'partial_match' => true,
                                'expected_format' => ''
                            ];
                        }
                        break;
                    }
                }
            }

            // If no exact match found and input is shorter than expected, try progressive prefix matching
            if (!$networkInfo && strlen($cleanNumber) < 8) {
                // For partial inputs, check if any existing prefix starts with this input
                $matchingPrefixes = NetworkPrefix::where('prefix', 'LIKE', $cleanNumber . '%')
                    ->orderBy('prefix', 'asc')
                    ->get();

                if ($matchingPrefixes->isNotEmpty()) {
                    // Check if the input exactly matches any existing prefix
                    $exactMatch = $matchingPrefixes->where('prefix', $cleanNumber)->first();

                    if ($exactMatch) {
                        // Input exactly matches a prefix - show full network info
                        $networkInfo = $exactMatch;

                        return [
                            'success' => true,
                            'phone_number' => $cleanNumber,
                            'prefix' => $networkInfo->prefix,
                            'country_name' => $networkInfo->country_name,
                            'network_name' => $networkInfo->network_name,
                            'mcc' => $networkInfo->mcc,
                            'mnc' => $networkInfo->mnc,
                            'live_coverage' => $networkInfo->live_coverage,
                            'min_length' => $networkInfo->min_length,
                            'max_length' => $networkInfo->max_length,
                            'partial_match' => true,
                            'expected_format' => ''
                        ];
                    } else {
                        // Check if any prefix actually starts with this input
                        $validPrefixFound = false;
                        $firstMatch = null;

                        foreach ($matchingPrefixes as $prefix) {
                            if (substr($prefix->prefix, 0, strlen($cleanNumber)) === $cleanNumber) {
                                if (!$firstMatch) {
                                    $firstMatch = $prefix;
                                }
                                $validPrefixFound = true;
                            }
                        }

                        if (!$validPrefixFound) {
                            return [
                                'success' => false,
                                'error' => 'No network prefix found starting with "' . $cleanNumber . '"',
                                'phone_number' => $cleanNumber
                            ];
                        }

                        // For short inputs (country code level), show generic country info
                        if (strlen($cleanNumber) <= 4) {
                            return [
                                'success' => true,
                                'phone_number' => $cleanNumber,
                                'prefix' => 'Multiple',
                                'country_name' => $firstMatch->country_name,
                                'network_name' => 'Multiple Networks Available',
                                'mcc' => $firstMatch->mcc,
                                'mnc' => 'XX',
                                'live_coverage' => true, // Assume at least one has coverage
                                'min_length' => $firstMatch->min_length,
                                'max_length' => $firstMatch->max_length,
                                'partial_match' => true,
                                'expected_format' => ''
                            ];
                        } else {
                            // For longer inputs, show specific network
                            return [
                                'success' => true,
                                'phone_number' => $cleanNumber,
                                'prefix' => $firstMatch->prefix,
                                'country_name' => $firstMatch->country_name,
                                'network_name' => $firstMatch->network_name,
                                'mcc' => $firstMatch->mcc,
                                'mnc' => $firstMatch->mnc,
                                'live_coverage' => $firstMatch->live_coverage,
                                'min_length' => $firstMatch->min_length,
                                'max_length' => $firstMatch->max_length,
                                'partial_match' => true,
                                'expected_format' => ''
                            ];
                        }
                    }
                } else {
                    // No prefixes found that start with this input
                    return [
                        'success' => false,
                        'error' => 'No network prefix found starting with "' . $cleanNumber . '"',
                        'phone_number' => $cleanNumber
                    ];
                }
            }

            if (!$networkInfo) {
                // Check if we can find the prefix but with wrong length
                for ($i = $maxPrefixLength; $i >= 2; $i--) {
                    $prefix = substr($cleanNumber, 0, $i);
                    $prefixOnly = NetworkPrefix::where('prefix', $prefix)->first();

                    if ($prefixOnly) {
                        return [
                            'success' => false,
                            'error' => "Phone number length invalid. Expected {$prefixOnly->min_length}-{$prefixOnly->max_length} digits, got " . strlen($cleanNumber),
                            'phone_number' => $cleanNumber,
                            'found_prefix' => $prefix,
                            'expected_length' => "{$prefixOnly->min_length}-{$prefixOnly->max_length}",
                            'actual_length' => strlen($cleanNumber)
                        ];
                    }
                }

                return [
                    'success' => false,
                    'error' => 'Phone number prefix not found in database',
                    'phone_number' => $cleanNumber
                ];
            }

            return [
                'success' => true,
                'phone_number' => $cleanNumber,
                'prefix' => $networkInfo->prefix,
                'country_name' => $networkInfo->country_name,
                'network_name' => $networkInfo->network_name,
                'mcc' => $networkInfo->mcc,
                'mnc' => $networkInfo->mnc,
                'live_coverage' => $networkInfo->live_coverage,
                'min_length' => $networkInfo->min_length,
                'max_length' => $networkInfo->max_length,
                'has_carrier_data' => true
            ];

        } catch (Exception $e) {
            Log::error('Network prefix check failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Database error occurred while checking network prefix'
            ];
        }
    }

    /**
     * Verify phone number with network prefix pre-check
     * Only calls API if live_coverage is true
     */
    public function verifyNumber($phoneNumber, $dataFreshness = null)
    {
        // First check network prefix
        $prefixCheck = $this->checkNetworkPrefix($phoneNumber);

        if (!$prefixCheck['success']) {
            return $prefixCheck;
        }

        // If no live coverage, return without making API call
        if (!$prefixCheck['live_coverage']) {
            Log::info('Skipping API call for phone number with no live coverage', [
                'phone' => $phoneNumber,
                'network' => $prefixCheck['network_name'],
                'country' => $prefixCheck['country_name']
            ]);

            return [
                'success' => false,
                'error' => 'Phone number has no live coverage - API verification skipped to save costs',
                'phone_number' => $phoneNumber,
                'network' => $prefixCheck['network_name'],
                'country_name' => $prefixCheck['country_name'],
                'mcc' => $prefixCheck['mcc'],
                'mnc' => $prefixCheck['mnc'],
                'type' => 'mobile',
                'status' => 999, // Custom status for no live coverage
                'status_message' => 'No Live Coverage',
                'ported' => false,
                'present' => 'no',
                'trxid' => null,
                'skip_reason' => 'no_live_coverage',
                'prefix_info' => $prefixCheck
            ];
        }

        // Determine if we should force fresh data
        $shouldForceFresh = $this->forceFresh($dataFreshness);

        if (!$shouldForceFresh) {
            // 1. Check Redis cache first
            $cacheKey = 'phone_verification:' . $phoneNumber;
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                // Check if cached data meets freshness requirements
                if ($this->isCacheFresh($cachedResult, $dataFreshness)) {
                    Log::info('Phone verification found in Redis cache', ['phone' => $phoneNumber]);
                    return $this->formatCached($cachedResult);
                } else {
                    // Cached data is too old, remove it
                    Cache::forget($cacheKey);
                    Log::info('Cached data too old, removed from cache', ['phone' => $phoneNumber]);
                }
            }

            // 2. Check database if not in cache
            $dbResult = Verification::where('number', $phoneNumber)->latest()->first();
            if ($dbResult && $this->isDbFresh($dbResult, $dataFreshness)) {
                Log::info('Phone verification found in database', ['phone' => $phoneNumber]);
                // Cache the database result for future requests
                Cache::put($cacheKey, $dbResult, now()->addHour());
                return $this->formatCached($dbResult);
            } elseif ($dbResult) {
                Log::info('Database data too old, forcing fresh API call', ['phone' => $phoneNumber]);
            }
        } else {
            Log::info('Forcing fresh data from API', ['phone' => $phoneNumber, 'data_freshness' => $dataFreshness]);
        }

        // 3. If not found in cache/database or data is too old, make API call
        return $this->makeApiCall($phoneNumber, $prefixCheck);
    }

    /**
     * Batch verification with network prefix pre-checking
     */
    public function verifyBatch(array $phoneNumbers, $dataFreshness = null)
    {
        $results = [];
        $cacheHits = 0;
        $dbHits = 0;
        $apiCalls = 0;
        $skippedNoCoverage = 0;

        // Determine if we should force fresh data
        $shouldForceFresh = $this->forceFresh($dataFreshness);

        foreach ($phoneNumbers as $phoneNumber) {
            // First check network prefix for each number
            $prefixCheck = $this->checkNetworkPrefix($phoneNumber);

            if (!$prefixCheck['success']) {
                $results[] = $prefixCheck;
                continue;
            }

            // If no live coverage, skip API call
            if (!$prefixCheck['live_coverage']) {
                $skippedNoCoverage++;
                $result = [
                    'success' => false,
                    'error' => 'Phone number has no live coverage - API verification skipped to save costs',
                    'phone_number' => $phoneNumber,
                    'network' => $prefixCheck['network_name'],
                    'country_name' => $prefixCheck['country_name'],
                    'mcc' => $prefixCheck['mcc'],
                    'mnc' => $prefixCheck['mnc'],
                    'type' => 'mobile',
                    'status' => 999,
                    'status_message' => 'No Live Coverage',
                    'ported' => false,
                    'present' => 'no',
                    'trxid' => null,
                    'skip_reason' => 'no_live_coverage',
                    'source' => 'prefix_check'
                ];
                $results[] = $result;
                usleep(100000);
                continue;
            }

            if (!$shouldForceFresh) {
                // Check cache first
                $cacheKey = 'phone_verification:' . $phoneNumber;
                $cachedResult = Cache::get($cacheKey);

                if ($cachedResult && $this->isCacheFresh($cachedResult, $dataFreshness)) {
                    $cacheHits++;
                    $result = $this->formatCached($cachedResult);
                    $result['source'] = 'cache';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                } elseif ($cachedResult) {
                    // Cached data is too old, remove it
                    Cache::forget($cacheKey);
                }

                // Check database
                $dbResult = Verification::where('number', $phoneNumber)->latest()->first();
                if ($dbResult && $this->isDbFresh($dbResult, $dataFreshness)) {
                    $dbHits++;
                    Cache::put($cacheKey, $dbResult, now()->addHour());
                    $result = $this->formatCached($dbResult);
                    $result['source'] = 'database';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                }
            }

            // Make API call if no fresh cached/database data or forcing fresh
            $apiCalls++;
            $result = $this->makeApiCall($phoneNumber, $prefixCheck);
            $result['source'] = 'api';
            $results[] = $result;
            usleep(100000);
        }

        return [
            'results' => $results,
            'statistics' => [
                'total_numbers' => count($phoneNumbers),
                'cache_hits' => $cacheHits,
                'database_hits' => $dbHits,
                'api_calls' => $apiCalls,
                'skipped_no_coverage' => $skippedNoCoverage
            ]
        ];
    }

    /**
     * Make API call to TMT service (only called if live coverage exists)
     */
    private function makeApiCall($phoneNumber, $prefixInfo = null)
    {
        // Validate credentials
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return $this->handleApiError('TMT API credentials not configured', $phoneNumber, 'Configuration Error');
        }

        try {
            // Make the API call
            $url = "{$this->baseUrl}/format/{$this->apiKey}/{$this->apiSecret}/{$phoneNumber}";
            Log::info('TMT API Request - verified live coverage', [
                'phone' => $phoneNumber,
                'network' => $prefixInfo['network_name'] ?? 'Unknown'
            ]);

            $response = Http::get($url);

            // Handle unsuccessful HTTP responses
            if (!$response->successful()) {
                return $this->handleApiError(
                    'API request failed with status: ' . $response->status(),
                    $phoneNumber,
                    'API Error',
                    ['status' => $response->status(), 'response' => $response->body()]
                );
            }

            $data = $response->json();

            // Handle empty response
            if (empty($data)) {
                return $this->handleApiError(
                    'API returned an empty response. Please check API credentials.',
                    $phoneNumber,
                    'Empty API Response'
                );
            }

            // Format the data and persist it if successful
            $result = $this->formatResponse($data, $prefixInfo);

            if ($result['success']) {
                $this->saveResult($result);
            }

            return $result;

        } catch (Exception $e) {
            return $this->handleApiError($e->getMessage(), $phoneNumber, 'Exception Error');
        }
    }

    /**
     * Format API response with network prefix information
     */
    private function formatResponse($data, $prefixInfo = null)
    {
        $phoneNumber = is_array($data) ? (array_keys($data)[0] ?? null) : null;
        $responseData = $phoneNumber ? $data[$phoneNumber] : [];

        $result = [
            'success' => ($responseData['status'] ?? 1) === 0,
            'phone_number' => $responseData['number'] ?? $phoneNumber,
            'cic' => $responseData['cic'] ?? null,
            'error' => $responseData['error'] ?? 0,
            'imsi' => $responseData['imsi'] ?? null,
            'mcc' => $responseData['mcc'] ?? ($prefixInfo['mcc'] ?? null),
            'mnc' => $responseData['mnc'] ?? ($prefixInfo['mnc'] ?? null),
            'network' => $responseData['network'] ?? ($prefixInfo['network_name'] ?? null),
            'number' => $responseData['number'] ?? null,
            'ported' => $responseData['ported'] ?? false,
            'present' => $responseData['present'] ?? null,
            'status' => $responseData['status'] ?? 0,
            'status_message' => $responseData['status_message'] ?? 'Unknown',
            'type' => $responseData['type'] ?? null,
            'trxid' => $responseData['trxid'] ?? null,
        ];

        // Add prefix information if available
        if ($prefixInfo) {
            $result['prefix'] = $prefixInfo['prefix'];
            $result['country_name'] = $prefixInfo['country_name'];
            $result['min_length'] = $prefixInfo['min_length'];
            $result['max_length'] = $prefixInfo['max_length'];
        }

        return $result;
    }

    /**
     * Format cached results
     */
    private function formatCached($result)
    {
        if ($result instanceof Verification) {
            return [
                'success' => true,
                'phone_number' => $result->number,
                'cic' => $result->cic,
                'error' => $result->error,
                'imsi' => $result->imsi,
                'mcc' => $result->mcc,
                'mnc' => $result->mnc,
                'network' => $result->network_name,
                'number' => $result->number,
                'ported' => $result->ported,
                'present' => $result->present,
                'status' => $result->status,
                'status_message' => $result->status_message,
                'type' => $result->type,
                'trxid' => $result->trxid,
                'cached' => true
            ];
        }

        return array_merge($result, ['cached' => true]);
    }

    /**
     * Save verification result to database and cache
     */
    private function saveResult(array $resultData)
    {
        try {
            // Prepare data for Verification model - only store prefix for relationship
            $verificationData = [
                'cic' => $resultData['cic'] ?? null,
                'error' => $resultData['error'] ?? 0,
                'imsi' => $resultData['imsi'] ?? null,
                'mcc' => $resultData['mcc'] ?? null,
                'mnc' => $resultData['mnc'] ?? null,
                'network' => $resultData['network'] ?? null,
                'ported' => $resultData['ported'] ?? false,
                'present' => $resultData['present'] ?? null,
                'status' => $resultData['status'] ?? 0,
                'status_message' => $resultData['status_message'] ?? null,
                'type' => $resultData['type'] ?? null,
                'trxid' => $resultData['trxid'] ?? null,
                'prefix' => $resultData['prefix'] ?? null,
            ];

            $verification = Verification::updateOrCreate(
                ['number' => $resultData['number']],
                $verificationData
            );

            $cacheKey = 'phone_verification:' . $resultData['number'];
            Cache::put($cacheKey, $verification, now()->addHour());
            Log::info('Verification result saved and cached.', ['phone' => $resultData['number']]);

        } catch (Exception $e) {
            Log::error('Failed to save or cache verification result.', [
                'phone' => $resultData['number'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle API errors
     */
    private function handleApiError(string $errorMessage, string $phoneNumber, string $statusMessage, array $logContext = [], int $statusCode = 1)
    {
        Log::error($errorMessage, array_merge(['phone' => $phoneNumber], $logContext));

        return [
            'success' => false,
            'error' => $errorMessage,
            'phone_number' => $phoneNumber,
            'status' => $statusCode,
            'status_message' => $statusMessage,
        ];
    }

    private function forceFresh($dataFreshness)
    {
        return $dataFreshness === 'all';
    }

    private function isCacheFresh($cachedResult, $dataFreshness)
    {
        if (!$dataFreshness || $dataFreshness === '') {
            return true;
        }

        if ($dataFreshness === 'all') {
            return false;
        }

        $cachedTimestamp = null;
        if ($cachedResult instanceof Verification) {
            $cachedTimestamp = $cachedResult->created_at;
        } elseif (is_array($cachedResult) && isset($cachedResult['created_at'])) {
            $cachedTimestamp = $cachedResult['created_at'];
        }

        if (!$cachedTimestamp) {
            return false;
        }

        return $this->isWithinLimit($cachedTimestamp, $dataFreshness);
    }

    private function isDbFresh($dbResult, $dataFreshness)
    {
        if (!$dataFreshness || $dataFreshness === '') {
            return true;
        }

        if ($dataFreshness === 'all') {
            return false;
        }

        return $this->isWithinLimit($dbResult->created_at, $dataFreshness);
    }

    private function isWithinLimit($timestamp, $dataFreshness)
    {
        $days = (int) $dataFreshness;
        if ($days <= 0) {
            return true;
        }

        $cutoffDate = now()->subDays($days);
        $dataDate = is_string($timestamp) ? \Carbon\Carbon::parse($timestamp) : $timestamp;

        return $dataDate->isAfter($cutoffDate);
    }
}